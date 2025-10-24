<?php

namespace IronFlow\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use IronFlow\Core\BaseModule;
use IronFlow\Contracts\{ExposableInterface, RoutableInterface, ViewableInterface};

class LazyLoader
{
    protected Application $app;
    protected array $eagerComponents;
    protected array $lazyComponents;
    protected array $loadedServices = [];

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

        // Register lazy components
        $this->registerLazyComponents($module);
    }

    /**
     * Register lazy components
     */
    protected function registerLazyComponents(BaseModule $module): void
    {
        if (!$module instanceof ExposableInterface) {
            return;
        }

        $services = $module->expose();

        foreach ($services as $serviceName => $serviceClass) {
            $fullServiceName = strtolower($module->getName()) . '.' . $serviceName;

            if (isset($this->loadedServices[$fullServiceName])) {
                continue;
            }

            // Enregistrer comme singleton lazy
            $this->app->singleton($serviceClass, function ($app) use ($serviceClass, $fullServiceName) {
                Log::debug("Lazy loading service: {$fullServiceName}");

                $this->loadedServices[$fullServiceName] = true;

                return $app->make($serviceClass);
            });
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

        foreach ($routesPath as $key => $path) {
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
        }

        Log::debug("Routes registered immediately for module {$module->getName()}", [
            'path' => $path,
            'middleware' => $middleware,
        ]);
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
        $namespace = $module->getViewNamespace();

        if (!is_dir($viewsPath)) {
            Log::debug("Views directory not found for module {$module->getName()}: {$viewsPath}");
            return;
        }

        View::addNamespace($namespace, $viewsPath);
    }

    /**
     * Load module configuration
     */
    protected function loadConfig(BaseModule $module): void
    {
        if (!$module instanceof \IronFlow\Contracts\ConfigurableInterface) {
            return;
        }

        $configPath = $module->getConfigPath();

        if (file_exists($configPath)) {
            $config = require $configPath;
            $module->setConfig($config);
        }
    }

    /**
     * Load services
     */
    protected function loadServices(BaseModule $module): void
    {
        if (!$module instanceof ExposableInterface) {
            return;
        }

        $services = $module->expose();

        foreach ($services as $serviceName => $serviceClass) {
            $fullName = strtolower($module->getName()) . '.' . $serviceName;

            // Register as lazy with proxy
            $this->app->bindIf($fullName, function ($app) use ($serviceClass, $fullName) {
                Log::debug("Lazy instantiating service: {$fullName}");
                return $app->make($serviceClass);
            });
        }
    }

    protected function loadEvents(BaseModule $module): void
    {
        // TODO : Implement this method
    }

    protected function loadCommands(BaseModule $module): void
    {
        // TODO : Implement this method
    }

    protected function loadMiddleware(BaseModule $module): void
    {
        // TODO : Implement this method
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
}
