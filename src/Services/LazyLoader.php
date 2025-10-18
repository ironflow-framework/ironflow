<?php

namespace IronFlow\Services;

use Illuminate\Contracts\Foundation\Application;
use IronFlow\Core\BaseModule;
use IronFlow\Contracts\{RoutableInterface, ViewableInterface, ConfigurableInterface, ExposableInterface};

class LazyLoader
{
    protected Application $app;
    protected array $eagerComponents;
    protected array $lazyComponents;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->eagerComponents = config('ironflow.lazy_loading.eager', ['routes', 'views', 'config']);
        $this->lazyComponents = config('ironflow.lazy_loading.lazy', ['services', 'events', 'commands']);
    }

    /**
     * Load module components based on lazy loading configuration
     */
    public function load(BaseModule $module): void
    {
        if (!config('ironflow.lazy_loading.enabled', true)) {
            $this->loadAll($module);
            return;
        }

        // Load eager components immediately
        foreach ($this->eagerComponents as $component) {
            $this->loadComponent($module, $component);
        }

        // Lazy components will be loaded on-demand
        // This is handled by Laravel's service container and deferred providers
    }

    /**
     * Load all components immediately
     */
    protected function loadAll(BaseModule $module): void
    {
        $allComponents = array_merge($this->eagerComponents, $this->lazyComponents);

        foreach ($allComponents as $component) {
            $this->loadComponent($module, $component);
        }
    }

    /**
     * Load a specific component
     */
    protected function loadComponent(BaseModule $module, string $component): void
    {
        match ($component) {
            'routes' => $this->loadRoutes($module),
            'views' => $this->loadViews($module),
            'config' => $this->loadConfig($module),
            'services' => $this->loadServices($module),
            'events' => $this->loadEvents($module),
            'commands' => $this->loadCommands($module),
            'middleware' => $this->loadMiddleware($module),
            default => null,
        };
    }

    /**
     * Load module routes
     */
    protected function loadRoutes(BaseModule $module): void
    {
        if (!$module instanceof RoutableInterface) {
            return;
        }

        $routesPath = $module->getRoutesPath();

        if (file_exists($routesPath)) {
            $module->registerRoutes();
        }
    }

    /**
     * Load module views
     */
    protected function loadViews(BaseModule $module): void
    {
        if (!$module instanceof ViewableInterface) {
            return;
        }

        $viewsPath = $module->getViewsPath();

        if (is_dir($viewsPath)) {
            $module->registerViews();
        }
    }

    /**
     * Load module configuration
     */
    protected function loadConfig(BaseModule $module): void
    {
        if (!$module instanceof ConfigurableInterface) {
            return;
        }

        $configPath = $module->getConfigPath();

        if (file_exists($configPath)) {
            $config = require $configPath;
            $module->setConfig($config);
        }
    }

    /**
     * Load module services
     */
    protected function loadServices(BaseModule $module): void
    {
        if (!$module instanceof ExposableInterface) {
            return;
        }

        $services = $module->expose();

        foreach ($services as $serviceName => $serviceClass) {
            // Save as lazy proxy
            $this->app->bindIf(
                $serviceClass,
                fn($app, $serviceClass) => $app->make($serviceClass)
            );
        }
    }

    /**
     * Load module events (placeholder for lazy loading)
     */
    protected function loadEvents(BaseModule $module): void
    {
        // Events are loaded via EventDispatcher
        // Implement if module has EventsInterface
    }

    /**
     * Load module commands (placeholder for lazy loading)
     */
    protected function loadCommands(BaseModule $module): void
    {
        // Commands are loaded via Console\Kernel
        // Implement if module has CommandsInterface
    }

    /**
     * Load module middleware (placeholder for lazy loading)
     */
    protected function loadMiddleware(BaseModule $module): void
    {
        // Middleware is loaded via Http\Kernel
        // Implement if module has MiddlewareInterface
    }
}
