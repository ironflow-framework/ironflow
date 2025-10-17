<?php

namespace IronFlow;

use Illuminate\Support\ServiceProvider;

use IronFlow\Core\Anvil;
use IronFlow\Support\ModuleRegistry;
use IronFlow\Support\DependencyResolver;
use IronFlow\Support\ServiceExposer;
use IronFlow\Support\ConflictDetector;
use IronFlow\Support\LazyLoader;

/**
 * IronFlowServiceProvider
 *
 * Main service provider - handles ALL module loading and registration.
 */
class IronFlowServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/ironflow.php', 'ironflow');

        // Register support classes
        $this->registerSupportClasses();

        // Register Anvil with Application instance
        $this->registerAnvil();

        // Register LazyLoader
        $this->registerLazyLoader();

        // Discover modules
        if (config('ironflow.auto_discover', true)) {
            $this->app->make('ironflow.anvil')->discover();
        }

        // Delegate module registration to Anvil
        $this->app->make('ironflow.anvil')->registerAll();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishConfiguration();
            $this->registerCommands();
        }

        // Register middleware
        $this->registerMiddleware();

        // Listen to publishables event from Anvil
        $this->listenToModulePublishables();

        // Delegate module booting to Anvil (with lazy loading support)
        if (config('ironflow.lazy_load.enabled', true)) {
            $this->bootModulesLazy();
        } else {
            $this->bootModulesEager();
        }
    }

    /**
     * Register support classes.
     */
    protected function registerSupportClasses(): void
    {
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(DependencyResolver::class);
        $this->app->singleton(ServiceExposer::class);
        $this->app->singleton(ConflictDetector::class);
    }

    /**
     * Register Anvil.
     */
    protected function registerAnvil(): void
    {
        $this->app->singleton('ironflow.anvil', function ($app) {
            return new Anvil(
                $app, // Pass Application instance
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
     */
    protected function registerLazyLoader(): void
    {
        $this->app->singleton(LazyLoader::class, function ($app) {
            $loader = new LazyLoader($app->make('ironflow.anvil'));
            $app->make(ServiceExposer::class)->setLazyLoader($loader);
            return $loader;
        });
    }

    /**
     * Boot modules eagerly (delegate to Anvil).
     */
    protected function bootModulesEager(): void
    {
        $this->app->make('ironflow.anvil')->bootAll();
    }

    /**
     * Boot modules lazily (delegate to Anvil via LazyLoader).
     */
    protected function bootModulesLazy(): void
    {
        $lazyLoader = $this->app->make(LazyLoader::class);
        $anvil = $this->app->make('ironflow.anvil');

        // Load eager modules
        $eagerModules = $lazyLoader->loadEager();

        foreach ($eagerModules as $name => $module) {
            $anvil->bootModule($module);
        }

        // Register middleware for route-based lazy loading
        if (!$this->app->runningInConsole()) {
            $this->app['router']->pushMiddlewareToGroup('web', \IronFlow\Http\Middleware\LazyLoadModules::class);
        }
    }

    /**
     * Listen to module publishables event.
     */
    protected function listenToModulePublishables(): void
    {
        $this->app['events']->listen('ironflow.module.publishables', function ($name, $module) {
            // Publish config
            $this->publishes($module->getPublishableConfig(), $name . '-config');

            // Publish views
            $this->publishes($module->getPublishableViews(), $name . '-views');

            // Publish assets
            $this->publishes($module->getPublishableAssets(), $name . '-assets');
        });
    }

    /**
     * Publish configuration.
     */
    protected function publishConfiguration(): void
    {
        $this->publishes([
            __DIR__ . '/../config/ironflow.php' => config_path('ironflow.php'),
        ], 'ironflow-config');

        $this->publishes([
            __DIR__ . '/../stubs' => resource_path('stubs/ironflow'),
        ], 'ironflow-stubs');
    }

    /**
     * Register middleware.
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
