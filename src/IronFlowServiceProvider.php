<?php

namespace IronFlow;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

use IronFlow\Core\Anvil;
use IronFlow\Core\BaseModule;
use IronFlow\Support\ModuleRegistry;
use IronFlow\Support\DependencyResolver;
use IronFlow\Support\ServiceExposer;
use IronFlow\Support\ConflictDetector;
use IronFlow\Support\LazyLoader;
use IronFlow\Contracts\ViewableInterface;
use IronFlow\Contracts\RoutableInterface;
use IronFlow\Contracts\MigratableInterface;
use IronFlow\Contracts\ConfigurableInterface;
use IronFlow\Contracts\PublishableInterface;
use IronFlow\Contracts\TranslatableInterface;
use IronFlow\Contracts\BootableInterface;
use IronFlow\Contracts\ExposableInterface;

/**
 * IronFlowServiceProvider
 *
 * Main service provider - handles ALL module loading and registration.
 */
class IronFlowServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge IronFlow configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/ironflow.php',
            'ironflow'
        );

        // Register support classes
        $this->registerSupportClasses();

        // Register Anvil
        $this->registerAnvil();

        // Register LazyLoader
        $this->registerLazyLoader();

        // Discover modules
        if (config('ironflow.auto_discover', true)) {
            $anvil = $this->app->make('ironflow.anvil');
            $anvil->discover();
        }

        // Register modules (their services)
        $this->registerModules();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/ironflow.php' => config_path('ironflow.php'),
            ], 'ironflow-config');

            $this->publishes([
                __DIR__ . '/../stubs' => resource_path('stubs/ironflow'),
            ], 'ironflow-stubs');

            // Register commands
            $this->registerCommands();
        }

        // Register middleware
        $this->registerMiddleware();

        // Boot modules based on lazy loading configuration
        if (config('ironflow.lazy_load.enabled', true)) {
            $this->bootModulesLazy();
        } else {
            $this->bootModulesEager();
        }
    }

    /**
     * Register support classes.
     *
     * @return void
     */
    protected function registerSupportClasses(): void
    {
        $this->app->singleton(ModuleRegistry::class, function () {
            return new ModuleRegistry();
        });

        $this->app->singleton(DependencyResolver::class, function () {
            return new DependencyResolver();
        });

        $this->app->singleton(ServiceExposer::class, function () {
            return new ServiceExposer();
        });

        $this->app->singleton(ConflictDetector::class, function () {
            return new ConflictDetector();
        });
    }

    /**
     * Register Anvil.
     *
     * @return void
     */
    protected function registerAnvil(): void
    {
        $this->app->singleton('ironflow.anvil', function ($app) {
            return new Anvil(
                $app->make(ModuleRegistry::class),
                $app->make(DependencyResolver::class),
                $app->make(ServiceExposer::class),
                $app->make(ConflictDetector::class)
            );
        });

        $this->app->alias('ironflow.anvil', Anvil::class);
    }

    /**
     * Register LazyLoader.
     *
     * @return void
     */
    protected function registerLazyLoader(): void
    {
        $this->app->singleton(LazyLoader::class, function ($app) {
            $loader = new LazyLoader($app->make('ironflow.anvil'));

            // Inject lazy loader into ServiceExposer
            $app->make(ServiceExposer::class)->setLazyLoader($loader);

            return $loader;
        });
    }

    /**
     * Register modules (call their register() method).
     *
     * @return void
     */
    protected function registerModules(): void
    {
        $anvil = $this->app->make('ironflow.anvil');
        $modules = $anvil->getModules();

        foreach ($modules as $name => $module) {
            if (!$module->getMetadata()->isEnabled()) {
                continue;
            }

            try {
                // Transition state
                $module->getState()->transitionTo(\IronFlow\Core\ModuleState::STATE_PRELOADED);

                // Call module's register method
                $module->register($this->app);

                // Log registration
                $this->logModuleEvent('registered', $name);
            } catch (\Throwable $e) {
                $module->getState()->markAsFailed($e);
                $this->logModuleEvent('failed', $name, 'error', $e->getMessage());
            }
        }
    }

    /**
     * Boot modules eagerly (all at once).
     *
     * @return void
     */
    protected function bootModulesEager(): void
    {
        $anvil = $this->app->make('ironflow.anvil');
        $modules = $anvil->getModules();

        // Resolve boot order
        $resolver = $this->app->make(DependencyResolver::class);
        $bootOrder = $resolver->resolve($modules);

        foreach ($bootOrder as $moduleName) {
            $module = $modules->get($moduleName);

            if (!$module || !$module->getMetadata()->isEnabled()) {
                continue;
            }

            $this->bootModule($module);
        }
    }

    /**
     * Boot modules lazily (only eager modules at startup).
     *
     * @return void
     */
    protected function bootModulesLazy(): void
    {
        $lazyLoader = $this->app->make(LazyLoader::class);

        // Load eager modules
        $eagerModules = $lazyLoader->loadEager();

        foreach ($eagerModules as $name => $module) {
            $this->bootModule($module);
        }

        // Register middleware for route-based lazy loading
        if (!$this->app->runningInConsole()) {
            $this->app['router']->pushMiddlewareToGroup('web', \IronFlow\Http\Middleware\LazyLoadModules::class);
        }
    }

    /**
     * Boot a single module.
     *
     * @param BaseModule $module
     * @return void
     */
    protected function bootModule(BaseModule $module): void
    {
        $name = $module->getName();

        try {
            // Transition state
            $module->getState()->transitionTo(\IronFlow\Core\ModuleState::STATE_BOOTING);

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

            // Execute custom boot logic
            if ($module instanceof BootableInterface) {
                $module->bootModule();
            }

            // Expose services
            if ($module instanceof ExposableInterface) {
                $serviceExposer = $this->app->make(ServiceExposer::class);
                $serviceExposer->expose($name, $module->expose());
            }

            // Transition to booted
            $module->getState()->transitionTo(\IronFlow\Core\ModuleState::STATE_BOOTED);

            // Log success
            $this->logModuleEvent('booted', $name);
        } catch (\Throwable $e) {
            $module->getState()->markAsFailed($e);
            $this->logModuleEvent('failed', $name, 'error', $e->getMessage());
        }
    }

    /**
     * Load module configuration.
     *
     * @param BaseModule $module
     * @return void
     */
    protected function loadModuleConfig(BaseModule $module): void
    {
        $configPath = $module->getConfigPath();

        if (File::exists($configPath)) {
            $this->mergeConfigFrom($configPath, $module->getConfigKey());
        }
    }

    /**
     * Load module translations.
     *
     * @param BaseModule $module
     * @return void
     */
    protected function loadModuleTranslations(BaseModule $module): void
    {
        $path = $module->getTranslationPath();

        if (File::isDirectory($path)) {
            $this->loadTranslationsFrom($path, $module->getTranslationNamespace());
        }
    }

    /**
     * Load module views.
     *
     * @param BaseModule $module
     * @return void
     */
    protected function loadModuleViews(BaseModule $module): void
    {
        $viewPaths = $module->getViewPaths();
        $namespace = $module->getViewNamespace();

        foreach ($viewPaths as $path) {
            if (File::isDirectory($path)) {
                $this->loadViewsFrom($path, $namespace);
            }
        }
    }

    /**
     * Load module routes.
     *
     * @param BaseModule $module
     * @return void
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
     *
     * @param BaseModule $module
     * @return void
     */
    protected function loadModuleMigrations(BaseModule $module): void
    {
        $path = $module->getMigrationPath();

        if (File::isDirectory($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    /**
     * Register module publishables.
     *
     * @param BaseModule $module
     * @return void
     */
    protected function registerModulePublishables(BaseModule $module): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $name = $module->getName();

        // Publish config
        $this->publishes($module->getPublishableConfig(), $name . '-config');

        // Publish views
        $this->publishes($module->getPublishableViews(), $name . '-views');

        // Publish assets
        $this->publishes($module->getPublishableAssets(), $name . '-assets');
    }

    /**
     * Register middleware.
     *
     * @return void
     */
    protected function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('ironflow.lazy', \IronFlow\Http\Middleware\LazyLoadModules::class);
    }

    /**
     * Register commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \IronFlow\Console\Commands\CacheClearCommand::class,
            \IronFlow\Console\Commands\CacheModulesCommand::class,
            \IronFlow\Console\Commands\DisableModuleCommand::class,
            \IronFlow\Console\Commands\DiscoverCommand::class,
            \IronFlow\Console\Commands\EnableModuleCommand::class,
            \IronFlow\Console\Commands\EventBusStatsCommand::class,
            \IronFlow\Console\Commands\HotReloadStatsCommand::class,
            \IronFlow\Console\Commands\HotReloadWatchCommand::class,
            \IronFlow\Console\Commands\InfoModuleCommand::class,
            \IronFlow\Console\Commands\InstallModuleCommand::class,
            \IronFlow\Console\Commands\LazyLoadBenchmarkCommand::class,
            \IronFlow\Console\Commands\LazyLoadClearCommand::class,
            \IronFlow\Console\Commands\LazyLoadStatsCommand::class,
            \IronFlow\Console\Commands\LazyLoadTestCommand::class,
            \IronFlow\Console\Commands\LazyLoadWarmupCommand::class,
            \IronFlow\Console\Commands\ListModulesCommand::class,
            \IronFlow\Console\Commands\MakeModuleCommand::class,
            \IronFlow\Console\Commands\MakeModuleControllerCommand::class,
            \IronFlow\Console\Commands\MakeModuleFactoryCommand::class,
            \IronFlow\Console\Commands\MakeModuleMigrationCommand::class,
            \IronFlow\Console\Commands\MakeModuleModelCommand::class,
            \IronFlow\Console\Commands\MakeModuleRepositoryCommand::class,
            \IronFlow\Console\Commands\MakeModuleRouteCommand::class,
            \IronFlow\Console\Commands\MakeModuleServiceCommand::class,
            \IronFlow\Console\Commands\PermissionsCommand::class,
            \IronFlow\Console\Commands\PublishModuleCommand::class,
            \IronFlow\Console\Commands\SeedModuleCommand::class,
        ]);
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

        $logEvents = config('ironflow.logging.log_events', []);
        if (!($logEvents[$event] ?? false)) {
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

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'ironflow.anvil',
            Anvil::class,
            ModuleRegistry::class,
            DependencyResolver::class,
            ServiceExposer::class,
            ConflictDetector::class,
            LazyLoader::class,
        ];
    }
}
