<?php

declare(strict_types=1);

namespace IronFlow\Core;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use IronFlow\Exceptions\ModuleException;
use IronFlow\Exceptions\DependencyException;
use IronFlow\Support\ModuleRegistry;
use IronFlow\Support\DependencyResolver;
use IronFlow\Support\ServiceExposer;
use IronFlow\Support\ConflictDetector;
use IronFlow\Contracts\ExposableInterface;

/**
 * Anvil - Module Manager and Orchestrator
 *
 * Manages the complete lifecycle of IronFlow modules including discovery,
 * registration, dependency resolution, service exposure, and conflict detection.
 *
 * @author Aure Dulvresse
 * @package IronFlow/Core
 * @since 1.0.0
 */
class Anvil
{
    /**
     * @var ModuleRegistry
     */
    protected ModuleRegistry $registry;

    /**
     * @var DependencyResolver
     */
    protected DependencyResolver $dependencyResolver;

    /**
     * @var ServiceExposer
     */
    protected ServiceExposer $serviceExposer;

    /**
     * @var ConflictDetector
     */
    protected ConflictDetector $conflictDetector;

    /**
     * @var Collection Registered modules
     */
    protected Collection $modules;

    /**
     * @var bool Whether modules have been discovered
     */
    protected bool $discovered = false;

    /**
     * Create a new Anvil instance.
     *
     * @param ModuleRegistry $registry
     * @param DependencyResolver $dependencyResolver
     * @param ServiceExposer $serviceExposer
     * @param ConflictDetector $conflictDetector
     */
    public function __construct(
        ModuleRegistry $registry,
        DependencyResolver $dependencyResolver,
        ServiceExposer $serviceExposer,
        ConflictDetector $conflictDetector
    ) {
        $this->registry = $registry;
        $this->dependencyResolver = $dependencyResolver;
        $this->serviceExposer = $serviceExposer;
        $this->conflictDetector = $conflictDetector;
        $this->modules = collect();
    }

    /**
     * Discover and register all modules.
     *
     * @return void
     * @throws ModuleException
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
     * Register a module from a path.
     *
     * @param string $path
     * @return void
     * @throws ModuleException
     */
    protected function registerModuleFromPath(string $path): void
    {
        $moduleName = basename($path);
        $moduleClass = $this->findModuleClass($path, $moduleName);

        if (!$moduleClass) {
            Log::warning("[IronFlow] Module class not found for: {$moduleName}");
            return;
        }

        if (!class_exists($moduleClass)) {
            Log::warning("[IronFlow] Module class does not exist: {$moduleClass}");
            return;
        }

        $this->registerModule($moduleClass);
    }

    /**
     * Find module class.
     *
     * @param string $path
     * @param string $moduleName
     * @return string|null
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
     * Register a module.
     *
     * @param string|BaseModule $module
     * @return void
     * @throws ModuleException
     */
    public function registerModule(string|BaseModule $module): void
    {
        if (is_string($module)) {
            $module = app($module);
        }

        if (!$module instanceof BaseModule) {
            throw new ModuleException("Module must extend BaseModule");
        }

        $metadata = $module->getMetadata();
        $moduleName = $metadata->getName();

        // Check if already registered
        if ($this->hasModule($moduleName)) {
            throw new ModuleException("Module {$moduleName} is already registered");
        }

        // Call register() method to transition state properly
        $module->register();

        // Register in registry
        $this->registry->register($moduleName, $module);
        $this->modules->put($moduleName, $module);

        Log::info("[IronFlow] Module registered: {$moduleName}");
    }

    /**
     * Boot all modules in dependency order.
     *
     * @return void
     * @throws DependencyException
     */
    public function bootAll(): void
    {
        // Resolve dependencies
        $bootOrder = $this->dependencyResolver->resolve($this->modules);

        // Detect conflicts before booting
        if (config('ironflow.conflict_detection.enabled', true)) {
            $conflicts = $this->conflictDetector->detect($this->modules);
            if (!empty($conflicts)) {
                Log::warning("[IronFlow] Conflicts detected", $conflicts);
            }
        }

        // Boot modules in order
        foreach ($bootOrder as $moduleName) {
            $module = $this->modules->get($moduleName);

            if (!$module->getMetadata()->isEnabled()) {
                continue;
            }

            try {
                $module->boot();

                // Expose services if module implements ExposableInterface
                if ($module instanceof ExposableInterface) {
                    $this->serviceExposer->expose($moduleName, $module->expose());
                }
            } catch (\Throwable $e) {
                Log::error("[IronFlow] Failed to boot module: {$moduleName}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }
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
}
