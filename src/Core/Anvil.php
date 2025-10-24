<?php

declare(strict_types=1);

namespace IronFlow\Core;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\{Route, View, Log};
use IronFlow\Core\Discovery\{ConflictDetector, ModuleDiscovery, ManifestCache};
use IronFlow\Services\{ServiceRegistry, LazyLoader, DependencyResolver};
use IronFlow\Events\ModuleEventDispatcher;
use IronFlow\Exceptions\{ModuleException, ExceptionHandler};
use IronFlow\Events\Events\{ModuleRegistered, ModuleBooting, ModuleBooted, ModuleFailed};

/**
 * Anvil - Module Manager and Orchestrator
 *
 * Manages module discovery, bootstrapping, service registration, and event dispatching.
 *
 * @author Aure Dulvresse
 * @package IronFlow/Core
 */
class Anvil
{
    protected Application $app;
    protected array $modules = [];
    protected bool $booted = false;

    // Services
    protected ModuleDiscovery $discovery;
    protected ManifestCache $cache;
    protected ServiceRegistry $serviceRegistry;
    protected LazyLoader $lazyLoader;
    protected DependencyResolver $dependencyResolver;
    protected ConflictDetector $conflictDetector;
    protected ModuleEventDispatcher $eventDispatcher;
    protected ExceptionHandler $exceptionHandler;

    protected array $routesManifest = [];
    protected array $viewsManifest = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->discovery = new ModuleDiscovery($app);
        $this->cache = new ManifestCache($app);
        $this->serviceRegistry = new ServiceRegistry($app);
        $this->lazyLoader = new LazyLoader($app);
        $this->dependencyResolver = new DependencyResolver();
        $this->conflictDetector = new ConflictDetector($app);
        $this->eventDispatcher = new ModuleEventDispatcher($app);
        $this->exceptionHandler = new ExceptionHandler($app);
    }

    /**
     * Bootstrap IronFlow - discover and boot modules
     */
    public function bootstrap(): void
    {
        if ($this->booted) {
            return;
        }

        try {
            // Discover modules
            if ($this->loadFromManifest()) {
                Log::info('IronFlow loaded from manifest cache');
            } else {
                if (config('ironflow.auto_discover', true)) {
                    $this->discover();
                }
            }

            // Boot modules
            $this->bootModules();

            $this->booted = true;

            Log::info('IronFlow bootstrapped successfully', [
                'modules_count' => count($this->modules),
            ]);
        } catch (\Throwable $e) {
            $this->exceptionHandler->handleBootstrapException($e);
            throw $e;
        }
    }

    /**
     * Discover all modules
     */
    public function discover(): void
    {
        // Try to load from cache
        if (config('ironflow.cache.enabled') && $this->cache->exists()) {
            $this->loadFromCache();
            return;
        }

        // Scan and register modules

        $discoveredModules = $this->discovery->scan();

        foreach ($discoveredModules as $moduleName => $moduleClass) {
            $this->registerModule($moduleName, $moduleClass);
        }

        // Cache manifest if enabled
        if (config('ironflow.cache.enabled')) {
            $this->saveManifest();
        }
    }

    /**
     * Register module
     */
    public function registerModule(string $moduleName, string $moduleClass): void
    {
        if (isset($this->modules[$moduleName])) {
            Log::warning("Module {$moduleName} already registered");
            return;
        }

        try {
            // 1. Instancier le module
            /** @var BaseModule $module */
            $module = new $moduleClass();
            $module->setState(ModuleState::REGISTERED);

            // 2. Call register method
            $module->register();

            // 3. Load module views
            $this->registerModuleViewsImmediately($module);

            // 4. Load module routes
            $this->registerModuleRoutesImmediately($module);

            // 5. Load module config
            $this->registerModuleConfigImmediately($module);

            // 6. Instancier et enregistrer le ModuleServiceProvider si présent
            $this->registerModuleServiceProviderImmediately($module);

            // 7. Stocker le module
            $this->modules[$moduleName] = $module;

            // 8. Dispatch event
            $this->eventDispatcher->dispatch(new ModuleRegistered($module));

            Log::info("Module {$moduleName} registered with immediate loading", [
                'has_views' => $module instanceof \IronFlow\Contracts\ViewableInterface,
                'has_routes' => $module instanceof \IronFlow\Contracts\RoutableInterface,
            ]);
        } catch (\Throwable $e) {
            $this->exceptionHandler->handleModuleException($moduleName, $e, 'registration');
            throw new ModuleException(
                "Failed to register module {$moduleName}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Register module views
     */
    protected function registerModuleViewsImmediately(BaseModule $module): void
    {
        if (!$module instanceof \IronFlow\Contracts\ViewableInterface) {
            return;
        }

        $viewsPath = $module->getViewsPath();
        $namespace = $module->getViewNamespace();

        if (!is_dir($viewsPath)) {
            Log::debug("Views directory not found for module {$module->getName()}: {$viewsPath}");
            return;
        }

        // Enregistrement immédiat dans le View Factory de Laravel
        View::addNamespace($namespace, $viewsPath);

        // Enregistrer dans le manifest pour cache
        $this->viewsManifest[$module->getName()] = [
            'namespace' => $namespace,
            'path' => $viewsPath,
        ];

        Log::debug("Views registered immediately for module {$module->getName()}", [
            'namespace' => $namespace,
            'path' => $viewsPath,
        ]);
    }

    /**
     * Register a module routes
     */
    protected function registerModuleRoutesImmediately(BaseModule $module): void
    {
        if (!$module instanceof \IronFlow\Contracts\RoutableInterface) {
            return;
        }

        $routesPath = $module->getRoutesPath();

        foreach ($routesPath as $path) {
            if (!file_exists($path)) {
                Log::debug("Routes file not found for module {$module->getName()}: {$path}");
                return;
            }
    
            $middleware = $module->getRouteMiddleware();
    
            Route::middleware($middleware)
                ->group(function () use ($path, $module) {
                    // load Routes
                    require $path;
                });
    
            // Save in the manifest
            $this->routesManifest[$module->getName()] = [
                'path' =>  $path,
                'middleware' => $middleware,
            ];
        }

        Log::debug("Routes registered immediately for module {$module->getName()}", [
            'path' => $path,
            'middleware' => $middleware,
        ]);
    }

    protected function registerModuleConfigImmediately(BaseModule $module): void
    {
        if (!$module instanceof \IronFlow\Contracts\ConfigurableInterface) {
            return;
        }

        $configPath = $module->getConfigPath();
        $configKey = $module->getConfigKey();

        if (!file_exists($configPath)) {
            return;
        }

        // Merge config in Laravel
        $config = require $configPath;
        config()->set($configKey, array_merge(
            config($configKey, []),
            $config
        ));

        $module->setConfig($config);

        Log::debug("Config registered immediately for module {$module->getName()}", [
            'key' => $configKey,
        ]);
    }

    protected function registerModuleServiceProviderImmediately(BaseModule $module): void
    {
        $moduleName = $module->getName();
        $providerClass = $module->getNamespace() . '\\' . $moduleName . 'ServiceProvider';

        if (!class_exists($providerClass)) {
            return;
        }

        try {
            $provider = new $providerClass($this->app, $module);

            if (method_exists($provider, 'register')) {
                $provider->register();
            }

            // Register ServiceProvider in Laravel
            if ($this->app->hasBeenBootstrapped() && method_exists($provider, 'boot')) {
                $provider->boot();
            } else {
                $this->app->register($provider);
            }

            Log::debug("ModuleServiceProvider registered for {$moduleName}", [
                'provider' => $providerClass,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Failed to register ModuleServiceProvider for {$moduleName}", [
                'provider' => $providerClass,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Boot all modules with transaction safety
     */
    public function bootModules(): void
    {
        // Resolve dependency order
        $bootOrder = $this->dependencyResolver->resolve($this->modules);

        // Detect conflicts
        $conflicts = $this->conflictDetector->detect($this->modules);
        $this->conflictDetector->handle($conflicts);

        // Preload modules
        foreach ($bootOrder as $moduleName) {
            $module = $this->modules[$moduleName];
            if ($module->getState() === ModuleState::REGISTERED) {
                $module->setState(ModuleState::PRELOADED);
            }
        }

        // Boot modules in dependency order with transaction
        $bootedModules = [];

        try {
            foreach ($bootOrder as $moduleName) {
                Log::channel('ironflow')->info('Starting module boot', [
                    'module' => $moduleName,
                    'memory_before' => memory_get_usage(true),
                ]);

                $start = microtime(true);

                $this->bootModule($moduleName);
                $bootedModules[] = $moduleName;

                $duration = round((microtime(true) - $start) * 1000, 2);

                Log::channel('ironflow')->info('Module booted successfully', [
                    'module' => $moduleName,
                    'duration_ms' => $duration,
                    'memory_after' => memory_get_usage(true),
                ]);
            }
        } catch (\Throwable $e) {
            Log::channel('ironflow')->error('Module boot sequence failed', [
                'failed_modules' => array_diff($bootOrder, $bootedModules),
                'error' => $e->getMessage(),
            ]);

            // Rollback bootés si configuré
            if (config('ironflow.exceptions.rollback_on_boot_failure', true)) {
                foreach (array_reverse($bootedModules) as $moduleName) {
                    try {
                        $this->rollbackModule($moduleName);
                    } catch (\Throwable $rollbackError) {
                        Log::error("Rollback failed for {$moduleName}: {$rollbackError->getMessage()}");
                    }
                }
            }

            throw $e;
        }
    }

    /**
     * Boot a single module
     */
    public function bootModule(string $moduleName): void
    {
        if (!isset($this->modules[$moduleName])) {
            $available = implode(', ', array_keys($this->modules));
            throw new ModuleException(
                "Module '{$moduleName}' not found. Available modules: {$available}"
            );
        }

        $module = $this->modules[$moduleName];

        // Skip if already booted or failed
        if ($module->isBooted() || $module->getState() === ModuleState::FAILED) {
            return;
        }

        try {
            $this->eventDispatcher->dispatch(new ModuleBooting($module));

            $module->setState(ModuleState::BOOTING);

            $module->bootModule();

            // Register services
            $this->registerModuleServices($module);

            // Lazy load components
            $this->lazyLoader->load($module);

            $module->setState(ModuleState::BOOTED);

            // Dispatch booted event
            $this->eventDispatcher->dispatch(new ModuleBooted($module));

            Log::info("Module {$moduleName} booted successfully");
        } catch (\Throwable $e) {
            $module->markAsFailed($e);
            $this->eventDispatcher->dispatch(new ModuleFailed($module, $e));
            $this->exceptionHandler->handleModuleException($moduleName, $e, 'boot');

            throw new ModuleException(
                "Failed to boot module '{$moduleName}': {$e->getMessage()}\n" .
                    "File: {$e->getFile()}:{$e->getLine()}",
                0,
                $e
            );
        }
    }

    /**
     * Register module services with the registry
     */
    protected function registerModuleServices(BaseModule $module): void
    {
        $publicServices = $module->expose();
        $linkedServices = $module->exposeLinked();

        $this->serviceRegistry->registerPublicServices($module->getName(), $publicServices);
        $this->serviceRegistry->registerLinkedServices($module->getName(), $linkedServices);
    }

    /**
     * Save manifest (modules + routes + views)
     */
    public function saveManifest(): void
    {
        $manifest = [
            'generated_at' => now()->toIso8601String(),
            'modules' => [],
            'routes_manifest' => $this->routesManifest,
            'views_manifest' => $this->viewsManifest,
        ];

        foreach ($this->modules as $name => $module) {
            $metadata = $module->getMetadata();
            $manifest['modules'][$name] = [
                'class' => get_class($module),
                'metadata' => $metadata->toArray(),
                'state' => $module->getState()->value,
                'services' => [
                    'public' => array_keys($module->expose()),
                    'linked' => array_keys($module->exposeLinked()),
                ],
            ];
        }

        $this->cache->save($manifest);

        Log::info("Manifest saved", [
            'modules_count' => count($manifest['modules']),
            'routes_count' => count($this->routesManifest),
            'views_count' => count($this->viewsManifest),
        ]);
    }

    /**
     * Load from manifest
     * Load modules, routes et views from cache
     */
    protected function loadFromManifest(): bool
    {
        if (!config('ironflow.cache.enabled') || !$this->cache->exists()) {
            return false;
        }

        try {
            $manifest = $this->cache->load();

            if (empty($manifest['modules'])) {
                return false;
            }

            if (!empty($manifest['views_manifest'])) {
                foreach ($manifest['views_manifest'] as $moduleName => $viewData) {
                    View::addNamespace($viewData['namespace'], $viewData['path']);
                }
                $this->viewsManifest = $manifest['views_manifest'];
            }

            if (!empty($manifest['routes_manifest'])) {
                foreach ($manifest['routes_manifest'] as $moduleName => $routeData) {
                    if (file_exists($routeData['path'])) {
                        Route::middleware($routeData['middleware'])
                            ->group(function () use ($routeData) {
                                require $routeData['path'];
                            });
                    }
                }
                $this->routesManifest = $manifest['routes_manifest'];
            }

            foreach ($manifest['modules'] as $name => $data) {
                $this->registerModule($name, $data['class']);
            }

            Log::info("Loaded from manifest", [
                'modules' => count($manifest['modules']),
                'routes' => count($this->routesManifest),
                'views' => count($this->viewsManifest),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning("Failed to load from manifest: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Clear the module cache
     */
    public function clearCache(): void
    {
        $this->cache->clear();
        $this->routesManifest = [];
        $this->viewsManifest = [];
        Log::info("Cache cleared");
    }

    /**
     * Rollback module after failed boot
     */
    protected function rollbackModule(string $moduleName): void
    {
        try {
            $module = $this->modules[$moduleName];

            // Remove registered services
            $this->serviceRegistry->unregisterModule($moduleName);

            // Mark as disabled
            $module->setState(ModuleState::DISABLED);

            Log::info("Module {$moduleName} rolled back successfully");
        } catch (\Throwable $e) {
            Log::error("Failed to rollback module {$moduleName}: {$e->getMessage()}");
        }
    }

    /**
     * Get a service from the registry
     */
    public function getService(string $serviceName, ?string $moduleContext = null): mixed
    {
        return $this->serviceRegistry->resolve($serviceName, $moduleContext);
    }

    /**
     * Get all registered modules
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Get a specific module
     */
    public function getModule(string $name): ?BaseModule
    {
        return $this->modules[$name] ?? null;
    }

    /**
     * Check if a module exists
     */
    public function hasModule(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    /**
     * Get routes manifest
     */
    public function getRoutesManifest(): array
    {
        return $this->routesManifest;
    }

    /**
     * Get views manifest
     */
    public function getViewsManifest(): array
    {
        return $this->viewsManifest;
    }

    /**
     * Cache the module manifest
     */
    public function cacheManifest(): void
    {
        $manifest = [];

        foreach ($this->modules as $name => $module) {
            $metadata = $module->getMetadata();
            $manifest[$name] = [
                'class' => get_class($module),
                'metadata' => $metadata->toArray(),
                'state' => $module->getState()->value,
                'services' => [
                    'public' => array_keys($module->expose()),
                    'linked' => array_keys($module->exposeLinked()),
                ],
            ];
        }

        $this->cache->save($manifest);
        Log::info("Module manifest cached successfully");
    }

    /**
     * Load modules from cache
     */
    protected function loadFromCache(): void
    {
        $manifest = $this->cache->load();

        foreach ($manifest as $name => $data) {
            $this->registerModule($name, $data['class']);
        }

        Log::info("Loaded " . count($manifest) . " modules from cache");
    }

    /**
     * Get dependency tree
     */
    public function getDependencyTree(): array
    {
        return $this->dependencyResolver->getTree($this->modules);
    }

    /**
     * Resolve a service binding
     */
    public function resolveService(string $abstract, callable $concrete): void
    {
        $this->app->bind($abstract, $concrete);
    }

    /**
     * Get event dispatcher
     */
    public function events(): ModuleEventDispatcher
    {
        return $this->eventDispatcher;
    }
}
