<?php

namespace IronFlow\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use IronFlow\Core\BaseModule;
use IronFlow\Core\Anvil;
use IronFlow\Exceptions\ModuleException;

/**
 * LazyLoader
 *
 * Implements lazy loading strategy for modules to improve performance.
 * Modules are only loaded when actually needed.
 */
class LazyLoader
{
    /**
     * @var Anvil
     */
    protected Anvil $anvil;

    /**
     * @var array Modules that are always loaded (eager)
     */
    protected array $eagerModules = [];

    /**
     * @var array Modules that can be lazy loaded
     */
    protected array $lazyModules = [];

    /**
     * @var array Currently loaded modules
     */
    protected array $loadedModules = [];

    /**
     * @var array Module loading strategies
     */
    protected array $strategies = [];

    /**
     * @var array Route to module mapping
     */
    protected array $routeModuleMap = [];

    /**
     * @var bool Whether lazy loading is enabled
     */
    protected bool $enabled = true;

    /**
     * Create a new LazyLoader instance.
     *
     * @param Anvil $anvil
     */
    public function __construct(Anvil $anvil)
    {
        $this->anvil = $anvil;
        $this->enabled = config('ironflow.lazy_load.enabled', true);
        $this->eagerModules = config('ironflow.lazy_load.eager', ['Core', 'Auth']);
        $this->lazyModules = config('ironflow.lazy_load.lazy', []);

        $this->loadStrategies();
        $this->buildRouteMap();
    }

    /**
     * Load strategies from configuration.
     *
     * @return void
     */
    protected function loadStrategies(): void
    {
        $this->strategies = config('ironflow.lazy_load.strategies', [
            'route' => true,      // Load on route match
            'service' => true,    // Load on service access
            'event' => true,      // Load on event trigger
            'command' => true,    // Load on artisan command
        ]);
    }

    /**
     * Build route to module mapping.
     *
     * @return void
     */
    protected function buildRouteMap(): void
    {
        $cached = Cache::get('ironflow.lazy_load.route_map');

        if ($cached && !config('app.debug')) {
            $this->routeModuleMap = $cached;
            return;
        }

        $this->routeModuleMap = $this->scanRouteModuleMapping();

        Cache::put('ironflow.lazy_load.route_map', $this->routeModuleMap, 3600);
    }

    /**
     * Scan all modules to build route mapping.
     *
     * @return array
     */
    protected function scanRouteModuleMapping(): array
    {
        $map = [];
        $modules = $this->anvil->getModules();

        foreach ($modules as $name => $module) {
            if (!$this->isLazyLoadable($name)) {
                continue;
            }

            if (method_exists($module, 'getRoutePrefix')) {
                $prefix = $module->getRoutePrefix();
                if ($prefix) {
                    $map[$prefix] = $name;
                }
            }
        }

        return $map;
    }

    /**
     * Check if a module is lazy loadable.
     *
     * @param string $moduleName
     * @return bool
     */
    public function isLazyLoadable(string $moduleName): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // Eager modules are never lazy loaded
        if (in_array($moduleName, $this->eagerModules)) {
            return false;
        }

        // If lazy modules list is empty, all non-eager modules are lazy
        if (empty($this->lazyModules)) {
            return true;
        }

        return in_array($moduleName, $this->lazyModules);
    }

    /**
     * Check if a module is already loaded.
     *
     * @param string $moduleName
     * @return bool
     */
    public function isLoaded(string $moduleName): bool
    {
        return isset($this->loadedModules[$moduleName]);
    }

    /**
     * Load a module lazily.
     *
     * @param string $moduleName
     * @param string $trigger
     * @return BaseModule|null
     * @throws ModuleException
     */
    public function load(string $moduleName, string $trigger = 'manual'): ?BaseModule
    {
        // Already loaded
        if ($this->isLoaded($moduleName)) {
            return $this->loadedModules[$moduleName];
        }

        // Not lazy loadable
        if (!$this->isLazyLoadable($moduleName)) {
            return null;
        }

        $startTime = microtime(true);

        try {
            $module = $this->anvil->getModule($moduleName);

            if (!$module) {
                throw new ModuleException("Module {$moduleName} not found");
            }

            // Check if module is enabled
            if (!$module->getMetadata()->isEnabled()) {
                Log::debug("[IronFlow LazyLoader] Module {$moduleName} is disabled, skipping");
                return null;
            }

            // Boot the module
            $module->boot();

            // Mark as loaded
            $this->loadedModules[$moduleName] = $module;

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info("[IronFlow LazyLoader] Loaded module: {$moduleName}", [
                'trigger' => $trigger,
                'duration' => "{$duration}ms",
            ]);

            // Dispatch event
            event('ironflow.module.lazy_loaded', [
                'module' => $moduleName,
                'trigger' => $trigger,
                'duration' => $duration,
            ]);

            return $module;
        } catch (\Throwable $e) {
            Log::error("[IronFlow LazyLoader] Failed to load module: {$moduleName}", [
                'error' => $e->getMessage(),
                'trigger' => $trigger,
            ]);

            throw new ModuleException(
                "Failed to lazy load module {$moduleName}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Load module by route.
     *
     * @param string $route
     * @return BaseModule|null
     */
    public function loadByRoute(string $route): ?BaseModule
    {
        if (!$this->strategies['route']) {
            return null;
        }

        // Remove query string
        $route = strtok($route, '?');

        // Try to match route prefix
        foreach ($this->routeModuleMap as $prefix => $moduleName) {
            if (str_starts_with($route, '/' . $prefix)) {
                return $this->load($moduleName, "route:{$route}");
            }
        }

        return null;
    }

    /**
     * Load module by service access.
     *
     * @param string $moduleName
     * @param string $serviceName
     * @return BaseModule|null
     */
    public function loadByService(string $moduleName, string $serviceName): ?BaseModule
    {
        if (!$this->strategies['service']) {
            return null;
        }

        return $this->load($moduleName, "service:{$serviceName}");
    }

    /**
     * Load module by event.
     *
     * @param string $moduleName
     * @param string $eventName
     * @return BaseModule|null
     */
    public function loadByEvent(string $moduleName, string $eventName): ?BaseModule
    {
        if (!$this->strategies['event']) {
            return null;
        }

        return $this->load($moduleName, "event:{$eventName}");
    }

    /**
     * Load module by command.
     *
     * @param string $moduleName
     * @param string $command
     * @return BaseModule|null
     */
    public function loadByCommand(string $moduleName, string $command): ?BaseModule
    {
        if (!$this->strategies['command']) {
            return null;
        }

        return $this->load($moduleName, "command:{$command}");
    }

    /**
     * Load all eager modules.
     *
     * @return Collection
     */
    public function loadEager(): Collection
    {
        $loaded = collect();

        foreach ($this->eagerModules as $moduleName) {
            try {
                $module = $this->anvil->getModule($moduleName);

                if ($module && $module->getMetadata()->isEnabled()) {
                    $module->boot();
                    $this->loadedModules[$moduleName] = $module;
                    $loaded->put($moduleName, $module);
                }
            } catch (\Throwable $e) {
                Log::error("[IronFlow LazyLoader] Failed to load eager module: {$moduleName}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("[IronFlow LazyLoader] Loaded eager modules", [
            'count' => $loaded->count(),
            'modules' => $loaded->keys()->toArray(),
        ]);

        return $loaded;
    }

    /**
     * Preload modules based on conditions.
     *
     * @param array $conditions
     * @return Collection
     */
    public function preload(array $conditions = []): Collection
    {
        $preloadConfig = config('ironflow.lazy_load.preload', []);
        $loaded = collect();

        // Preload by route patterns
        if (isset($preloadConfig['routes']) && isset($conditions['route'])) {
            foreach ($preloadConfig['routes'] as $pattern => $modules) {
                if (preg_match($pattern, $conditions['route'])) {
                    foreach ((array) $modules as $moduleName) {
                        if ($module = $this->load($moduleName, 'preload:route')) {
                            $loaded->put($moduleName, $module);
                        }
                    }
                }
            }
        }

        // Preload by time (e.g., morning, evening)
        if (isset($preloadConfig['time'])) {
            $hour = date('H');
            foreach ($preloadConfig['time'] as $timeRange => $modules) {
                [$start, $end] = explode('-', $timeRange);
                if ($hour >= $start && $hour <= $end) {
                    foreach ((array) $modules as $moduleName) {
                        if ($module = $this->load($moduleName, 'preload:time')) {
                            $loaded->put($moduleName, $module);
                        }
                    }
                }
            }
        }

        // Preload by user role
        if (isset($preloadConfig['roles']) && isset($conditions['role'])) {
            if (isset($preloadConfig['roles'][$conditions['role']])) {
                foreach ($preloadConfig['roles'][$conditions['role']] as $moduleName) {
                    if ($module = $this->load($moduleName, 'preload:role')) {
                        $loaded->put($moduleName, $module);
                    }
                }
            }
        }

        return $loaded;
    }

    /**
     * Get loaded modules.
     *
     * @return array
     */
    public function getLoadedModules(): array
    {
        return array_keys($this->loadedModules);
    }

    /**
     * Get lazy loadable modules that haven't been loaded yet.
     *
     * @return array
     */
    public function getPendingModules(): array
    {
        $all = $this->anvil->getModules()->keys()->toArray();
        $loaded = $this->getLoadedModules();
        $eager = $this->eagerModules;

        return array_diff($all, $loaded, $eager);
    }

    /**
     * Get lazy loading statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $total = $this->anvil->getModules()->count();
        $loaded = count($this->loadedModules);
        $eager = count($this->eagerModules);
        $pending = count($this->getPendingModules());

        return [
            'enabled' => $this->enabled,
            'total_modules' => $total,
            'eager_modules' => $eager,
            'loaded_modules' => $loaded,
            'pending_modules' => $pending,
            'memory_saved_estimate' => $this->estimateMemorySaved(),
            'loaded_list' => $this->getLoadedModules(),
            'pending_list' => $this->getPendingModules(),
        ];
    }

    /**
     * Estimate memory saved by lazy loading.
     *
     * @return string
     */
    protected function estimateMemorySaved(): string
    {
        // Rough estimate: 2MB per unloaded module
        $pending = count($this->getPendingModules());
        $saved = $pending * 2;

        return $saved > 0 ? "{$saved}MB (estimated)" : '0MB';
    }

    /**
     * Clear lazy loading cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget('ironflow.lazy_load.route_map');
        $this->buildRouteMap();
    }

    /**
     * Warm up the lazy loader (preload everything).
     *
     * @return void
     */
    public function warmUp(): void
    {
        $modules = $this->anvil->getModules();

        foreach ($modules as $name => $module) {
            if ($this->isLazyLoadable($name) && !$this->isLoaded($name)) {
                $this->load($name, 'warmup');
            }
        }

        Log::info("[IronFlow LazyLoader] Warmed up all modules", [
            'count' => count($this->loadedModules),
        ]);
    }
}
