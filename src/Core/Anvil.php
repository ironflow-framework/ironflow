<?php

declare(strict_types=1);

namespace IronFlow\Core;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use IronFlow\Core\Discovery\{ModuleDiscovery, ManifestCache, ConflictDetector};
use IronFlow\Support\ModuleRegistry;
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
    protected ModuleRegistry $registry;
    protected array $modules = [];
    protected bool $booted = false;
    protected ModuleDiscovery $discovery;
    protected ManifestCache $cache;
    protected ServiceRegistry $serviceRegistry;
    protected LazyLoader $lazyLoader;
    protected DependencyResolver $dependencyResolver;
    protected ConflictDetector $conflictDetector;
    protected ModuleEventDispatcher $eventDispatcher;
    protected ExceptionHandler $exceptionHandler;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->discovery = new ModuleDiscovery($app);
        $this->registry = new ModuleRegistry();
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
            if (config('ironflow.auto_discover', true)) {
                $this->discover();
            }

            // Boot all modules
            $this->bootModules();

            $this->booted = true;
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
            $this->cacheManifest();
        }
    }

    /**
     * Register a module
     */
    public function registerModule(string $moduleName, string $moduleClass): void
    {
        if (isset($this->modules[$moduleName])) {
            Log::warning("Module {$moduleName} is already registered");
            return;
        }

        try {
            /** @var BaseModule $module */
            $module = new $moduleClass();
            $module->setState(ModuleState::REGISTERED);

            // Call register method
            $module->register();

            $this->registry->register($moduleName, $module);    
            $this->modules[$moduleName] = $module;

            // Dispatch event
            $this->eventDispatcher->dispatch(new ModuleRegistered($module));

            Log::info("Module {$moduleName} registered successfully");
        } catch (\Throwable $e) {
            $this->exceptionHandler->handleModuleException($moduleName, $e, 'registration');
            throw new ModuleException("Failed to register module {$moduleName}: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Boot all modules
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

        // Boot modules in dependency order
        foreach ($bootOrder as $moduleName) {
            $this->bootModule($moduleName);
        }
    }

    /**
     * Boot a single module
     */
    public function bootModule(string $moduleName): void
    {
        if (!isset($this->modules[$moduleName])) {
            throw new ModuleException("Module {$moduleName} not found");
        }

        $module = $this->modules[$moduleName];

        // Skip if already booted or failed
        if ($module->isBooted() || $module->getState() === ModuleState::FAILED) {
            return;
        }

        try {
            // Dispatch booting event
            $this->eventDispatcher->dispatch(new ModuleBooting($module));

            $module->setState(ModuleState::BOOTING);

            // Boot the module
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

            // Dispatch failed event
            $this->eventDispatcher->dispatch(new ModuleFailed($module, $e));

            $this->exceptionHandler->handleModuleException($moduleName, $e, 'boot');

            if (config('ironflow.exceptions.rollback_on_boot_failure', true)) {
                $this->rollbackModule($moduleName);
            }

            throw new ModuleException("Failed to boot module {$moduleName}: {$e->getMessage()}", 0, $e);
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
     * Clear the module cache
     */
    public function clearCache(): void
    {
        $this->cache->clear();
        Log::info("Module cache cleared");
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
