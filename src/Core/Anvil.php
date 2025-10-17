<?php

declare(strict_types=1);

namespace IronFlow\Core;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use IronFlow\Contracts\BootableInterface;
use IronFlow\Exceptions\ModuleException;
use IronFlow\Support\ModuleRegistry;
use IronFlow\Support\DependencyResolver;
use IronFlow\Support\ServiceExposer;
use IronFlow\Support\ConflictDetector;
use IronFlow\Contracts\ConfigurableInterface;
use IronFlow\Contracts\ExposableInterface;
use IronFlow\Contracts\MigratableInterface;
use IronFlow\Contracts\PublishableInterface;
use IronFlow\Contracts\RoutableInterface;
use IronFlow\Contracts\TranslatableInterface;
use IronFlow\Contracts\ViewableInterface;

/**
 * Anvil - Module Manager and Orchestrator
 *
 * Now responsible for register() and boot() operations on modules.
 */
class Anvil
{
    protected ModuleRegistry $registry;
    protected DependencyResolver $dependencyResolver;
    protected ServiceExposer $serviceExposer;
    protected ConflictDetector $conflictDetector;
    protected Application $app;
    protected Collection $modules;
    protected bool $discovered = false;
    protected bool $registered = false;
    protected bool $booted = false;

    public function __construct(
        Application $app,
        ModuleRegistry $registry,
        DependencyResolver $dependencyResolver,
        ServiceExposer $serviceExposer,
        ConflictDetector $conflictDetector
    ) {
        $this->app = $app;
        $this->registry = $registry;
        $this->dependencyResolver = $dependencyResolver;
        $this->serviceExposer = $serviceExposer;
        $this->conflictDetector = $conflictDetector;
        $this->modules = collect();
    }

    /**
     * Discover all modules.
     */
    public function discover(): void
    {
        if ($this->discovered) {
            return;
        }

        $modulePath = config('ironflow.path');

        if (!File::isDirectory($modulePath)) {
            Log::warning("[IronFlow] Module path does not exist: {$modulePath}");
            $this->discovered = true;
            return;
        }

        $moduleDirs = File::directories($modulePath);

        foreach ($moduleDirs as $dir) {
            $this->registerModuleFromPath($dir);
        }

        $this->discovered = true;
    }

    /**
     * Register module from path.
     */
    protected function registerModuleFromPath(string $path): void
    {
        $moduleName = basename($path);
        $moduleClass = $this->findModuleClass($path, $moduleName);

        if (!$moduleClass || !class_exists($moduleClass)) {
            return;
        }

        $this->registerModule($moduleClass);
    }

    /**
     * Find module class.
     */
    protected function findModuleClass(string $path, string $moduleName): ?string
    {
        $namespace = config('ironflow.namespace', 'Modules');
        $possibleClasses = [
            "{$namespace}\\{$moduleName}\\{$moduleName}Module",
            "{$namespace}\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider",
        ];

        foreach ($possibleClasses as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Register a module instance.
     */
    public function registerModule(string|BaseModule $module): void
    {
        if (is_string($module)) {
            $module = new $module();
        }

        if (!$module instanceof BaseModule) {
            throw new ModuleException("Module must extend BaseModule");
        }

        $metadata = $module->getMetadata();
        $moduleName = $metadata->getName();

        if ($this->hasModule($moduleName)) {
            throw new ModuleException("Module {$moduleName} is already registered");
        }

        $this->registry->register($moduleName, $module);
        $this->modules->put($moduleName, $module);

        Log::info("[IronFlow] Module discovered: {$moduleName}");
    }

    /**
     * Register all modules (call their register() method).
     */
    public function registerAll(): void
    {
        if ($this->registered) {
            return;
        }

        foreach ($this->modules as $name => $module) {
            $this->registerModuleServices($module);
        }

        $this->registered = true;
    }

    /**
     * Register a single module's services.
     */
    protected function registerModuleServices(BaseModule $module): void
    {
        $name = $module->getName();

        if (!$module->getMetadata()->isEnabled()) {
            return;
        }

        try {
            $module->getState()->transitionTo(ModuleState::STATE_PRELOADED);

            // Call module's register method
            $module->register($this->app);

            $this->logModuleEvent('registered', $name);
        } catch (\Throwable $e) {
            $module->getState()->markAsFailed($e);
            $this->logModuleEvent('failed', $name, 'error', $e->getMessage());
        }
    }

    /**
     * Boot all modules.
     */
    public function bootAll(): void
    {
        if ($this->booted) {
            return;
        }

        // Resolve boot order
        $bootOrder = $this->dependencyResolver->resolve($this->modules);

        // Detect conflicts
        if (config('ironflow.conflict_detection.enabled', true)) {
            $conflicts = $this->conflictDetector->detect($this->modules);
            if (!empty($conflicts)) {
                Log::warning("[IronFlow] Conflicts detected", $conflicts);
            }
        }

        // Boot modules in order
        foreach ($bootOrder as $moduleName) {
            $module = $this->modules->get($moduleName);

            if (!$module || !$module->getMetadata()->isEnabled()) {
                continue;
            }

            $this->bootModule($module);
        }

        $this->booted = true;
    }

    /**
     * Boot a single module.
     */
    public function bootModule(BaseModule $module): void
    {
        $name = $module->getName();

        try {
            $module->getState()->transitionTo(ModuleState::STATE_BOOTING);

            // Load configuration
            if ($module instanceof ConfigurableInterface) {
                $this->loadModuleConfig($module);
            }

            // Load translations
            if ($module instanceof TranslatableInterface) {
                $this->loadModuleTranslations($module);
            }

            // Load views
            if ($module instanceof ViewableInterface) {
                $this->loadModuleViews($module);
            }

            // Load routes
            if ($module instanceof RoutableInterface) {
                $this->loadModuleRoutes($module);
            }

            // Load migrations
            if ($module instanceof MigratableInterface) {
                $this->loadModuleMigrations($module);
            }

            // Register publishables
            if ($module instanceof PublishableInterface) {
                $this->registerModulePublishables($module);
            }

            // Call module's boot method
            $module->boot($this->app);

            // Custom boot logic
            if ($module instanceof BootableInterface) {
                $module->bootModule();
            }

            // Expose services
            if ($module instanceof ExposableInterface) {
                $this->serviceExposer->expose($name, $module->expose());
            }

            $module->getState()->transitionTo(ModuleState::STATE_BOOTED);
            $this->logModuleEvent('booted', $name);
        } catch (\Throwable $e) {
            $module->getState()->markAsFailed($e);
            $this->logModuleEvent('failed', $name, 'error', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Load module configuration.
     */
    protected function loadModuleConfig(BaseModule $module): void
    {
        $configPath = $module->getConfigPath();

        if (File::exists($configPath)) {
            $config = require $configPath;
            config([$module->getConfigKey() => array_merge(
                config($module->getConfigKey(), []),
                $config
            )]);
        }
    }

    /**
     * Load module translations.
     */
    protected function loadModuleTranslations(BaseModule $module): void
    {
        $path = $module->getTranslationPath();

        if (File::isDirectory($path)) {
            $this->app['translator']->addNamespace($module->getTranslationNamespace(), $path);
        }
    }

    /**
     * Load module views.
     */
    protected function loadModuleViews(BaseModule $module): void
    {
        $viewPaths = $module->getViewPaths();
        $namespace = $module->getViewNamespace();

        foreach ($viewPaths as $path) {
            if (File::isDirectory($path)) {
                $this->app['view']->addNamespace($namespace, $path);
            }
        }
    }

    /**
     * Load module routes.
     */
    protected function loadModuleRoutes(BaseModule $module): void
    {
        $routeFiles = $module->getRouteFiles();
        $middleware = $module->getRouteMiddleware();
        $prefix = $module->getRoutePrefix();

        foreach ($routeFiles as $type => $file) {
            if (!File::exists($file)) {
                continue;
            }

            Route::middleware($middleware[$type] ?? [])
                ->prefix($type === 'api' ? 'api/' . $prefix : $prefix)
                ->name($module->getViewNamespace() . '.')
                ->group($file);
        }
    }

    /**
     * Load module migrations.
     */
    protected function loadModuleMigrations(BaseModule $module): void
    {
        $path = $module->getMigrationPath();

        if (File::isDirectory($path)) {
            $this->app['migrator']->path($path);
        }
    }

    /**
     * Register module publishables.
     */
    protected function registerModulePublishables(BaseModule $module): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $name = $module->getName();

        // This requires access to ServiceProvider's publishes method
        // We'll need to call this from IronFlowServiceProvider
        event('ironflow.module.publishables', [$name, $module]);
    }

     /**
     * Get a registered module.
     *
     * @param string $name
     * @return BaseModule|null
     */
    public function getModule(string $name): ?BaseModule
    {
        return $this->modules->get($name);
    }

    /**
     * Check if module exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasModule(string $name): bool
    {
        return $this->modules->has($name);
    }

    /**
     * Get all modules.
     *
     * @return Collection
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    /**
     * Get enabled modules.
     *
     * @return Collection
     */
    public function getEnabledModules(): Collection
    {
        return $this->modules->filter(function (BaseModule $module) {
            return $module->getMetadata()->isEnabled();
        });
    }

    /**
     * Get disabled modules.
     *
     * @return Collection
     */
    public function getDisabledModules(): Collection
    {
        return $this->modules->filter(function (BaseModule $module) {
            return !$module->getMetadata()->isEnabled();
        });
    }

    /**
     * Enable a module.
     *
     * @param string $name
     * @return void
     * @throws ModuleException
     */
    public function enable(string $name): void
    {
        $module = $this->getModule($name);

        if (!$module) {
            throw new ModuleException("Module {$name} not found");
        }

        $module->enable();
        $this->clearCache();
    }

    /**
     * Disable a module.
     *
     * @param string $name
     * @return void
     * @throws ModuleException
     */
    public function disable(string $name): void
    {
        $module = $this->getModule($name);

        if (!$module) {
            throw new ModuleException("Module {$name} not found");
        }

        $module->disable();
        $this->clearCache();
    }

    /**
     * Install a module.
     *
     * @param string $name
     * @return void
     * @throws ModuleException
     */
    public function install(string $name): void
    {
        $module = $this->getModule($name);

        if (!$module) {
            throw new ModuleException("Module {$name} not found");
        }

        $module->install();
    }

    /**
     * Uninstall a module.
     *
     * @param string $name
     * @return void
     * @throws ModuleException
     */
    public function uninstall(string $name): void
    {
        $module = $this->getModule($name);

        if (!$module) {
            throw new ModuleException("Module {$name} not found");
        }

        $module->uninstall();
    }

    /**
     * Get exposed service from a module.
     *
     * @param string $moduleName
     * @param string $serviceName
     * @param string|null $requesterModule
     * @return mixed
     * @throws ModuleException
     */
    public function getService(string $moduleName, string $serviceName, ?string $requesterModule = null): mixed
    {
        return $this->serviceExposer->getService($moduleName, $serviceName, $requesterModule);
    }

    /**
     * Check if module provides a service.
     *
     * @param string $moduleName
     * @param string $serviceName
     * @return bool
     */
    public function hasService(string $moduleName, string $serviceName): bool
    {
        return $this->serviceExposer->hasService($moduleName, $serviceName);
    }

    /**
     * Get module dependencies.
     *
     * @param string $name
     * @return array
     */
    public function getDependencies(string $name): array
    {
        $module = $this->getModule($name);
        return $module ? $module->getMetadata()->getDependencies() : [];
    }

    /**
     * Get modules that depend on given module.
     *
     * @param string $name
     * @return Collection
     */
    public function getDependents(string $name): Collection
    {
        return $this->modules->filter(function (BaseModule $module) use ($name) {
            return $module->getMetadata()->hasDependency($name);
        });
    }

    /**
     * Clear module cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        if (config('ironflow.cache.enabled', true)) {
            Cache::forget(config('ironflow.cache.key', 'ironflow.modules'));
        }
    }

    /**
     * Get module statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->modules->count(),
            'enabled' => $this->getEnabledModules()->count(),
            'disabled' => $this->getDisabledModules()->count(),
            'failed' => $this->modules->filter(fn($m) => $m->getState()->isFailed())->count(),
            'booted' => $this->modules->filter(fn($m) => $m->getState()->isBooted())->count(),
        ];
    }

     /**
     * Log module event.
     *
     * @param string $event
     * @param string $moduleName
     * @param string $level
     * @param string|null $message
     * @return void
     */
    protected function logModuleEvent(string $event, string $moduleName, string $level = 'info', ?string $message = null): void
    {
        if (!config('ironflow.logging.enabled', true)) {
            return;
        }

        $logModuleEvents = config('ironflow.logging.log_events', []);
        if (!($logModuleEvents[$event] ?? false)) {
            return;
        }

        $logMessage = "[IronFlow] Module {$moduleName} {$event}";
        if ($message) {
            $logMessage .= ": {$message}";
        }

        $channel = config('ironflow.logging.channel', 'stack');
        Log::channel($channel)->$level($logMessage, [
            'module' => $moduleName,
            'event' => $event,
        ]);
    }
}

