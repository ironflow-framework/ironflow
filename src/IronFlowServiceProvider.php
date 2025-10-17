<?php

namespace IronFlow;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use IronFlow\Core\Anvil;
use IronFlow\Core\BaseModule;
use IronFlow\Support\ModuleRegistry;
use IronFlow\Support\DependencyResolver;
use IronFlow\Support\ServiceExposer;
use IronFlow\Support\ConflictDetector;
use IronFlow\Support\LazyLoader;
use IronFlow\Support\HotReloader;
use IronFlow\Contracts\ConfigurableInterface;
use IronFlow\Contracts\ViewableInterface;
use IronFlow\Contracts\RoutableInterface;
use IronFlow\Contracts\MigratableInterface;
use IronFlow\Contracts\TranslatableInterface;
use IronFlow\Contracts\PublishableInterface;
use IronFlow\Contracts\BootableInterface;
use IronFlow\Contracts\ExposableInterface;
use IronFlow\Contracts\SchedulableInterface;
use IronFlow\Core\ModuleState;
use IronFlow\Events\ModuleEventBus;

/**
 * IronFlowServiceProvider
 *
 * Centralized provider that loads all module resources dynamically.
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
        // Merge configuration
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

        // Register HotReloader
        $this->registerHotReloader();

        // Discover and register modules
        if (config('ironflow.auto_discover', true)) {
            $this->discoverAndRegisterModules();
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration and stubs
        $this->publishAssets();

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }

        // Boot modules
        $this->bootModules();
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
     * Register HotReloader.
     *
     * @return void
     */
    protected function registerHotReloader(): void
    {
        if (config('ironflow.hot_reload.enabled', false)) {
            $this->app->singleton(HotReloader::class, function ($app) {
                return new HotReloader($app->make('ironflow.anvil'));
            });
        }
    }

    /**
     * Discover and register modules.
     *
     * @return void
     */
    protected function discoverAndRegisterModules(): void
    {
        $anvil = $this->app->make('ironflow.anvil');
        $anvil->discover();

        // Get all discovered modules
        $modules = $anvil->getModules();

        // Register each module in the service container
        foreach ($modules as $name => $module) {
            $this->registerModuleServices($module);
        }
    }

    /**
     * Register services for a single module.
     *
     * @param BaseModule $module
     * @return void
     */
    protected function registerModuleServices(BaseModule $module): void
    {
        try {
            $module->getState()->transitionTo(ModuleState::STATE_PRELOADED);

            // Call module's register method if it exists
            if (method_exists($module, 'register')) {
                $module->register();
            }

            // Register configuration if module is configurable
            if ($module instanceof ConfigurableInterface) {
                $this->registerModuleConfig($module);
            }

            $this->logModuleEvent($module, 'registered');
        } catch (\Throwable $e) {
            $module->getState()->markAsFailed($e);
            $this->logModuleError($module, 'registration failed', $e);
        }
    }

    /**
     * Boot all modules.
     *
     * @return void
     */
    protected function bootModules(): void
    {
        $anvil = $this->app->make('ironflow.anvil');

        // Use lazy loading if enabled
        if (config('ironflow.lazy_load.enabled', true)) {
            $this->bootModulesWithLazyLoading($anvil);
        } else {
            $this->bootAllModules($anvil);
        }
    }

    /**
     * Boot modules with lazy loading.
     *
     * @param Anvil $anvil
     * @return void
     */
    protected function bootModulesWithLazyLoading(Anvil $anvil): void
    {
        $lazyLoader = $this->app->make(LazyLoader::class);

        // Load eager modules
        $eagerModules = $lazyLoader->loadEager();

        foreach ($eagerModules as $name => $module) {
            $this->bootModule($module);
        }

        // Register lazy loading middleware if not in console
        if (!$this->app->runningInConsole()) {
            $this->app['router']->pushMiddlewareToGroup('web', \IronFlow\Http\Middleware\LazyLoadModules::class);
        }
    }

    /**
     * Boot all modules immediately.
     *
     * @param Anvil $anvil
     * @return void
     */
    protected function bootAllModules(Anvil $anvil): void
    {
        $modules = $anvil->getModules();

        // Resolve boot order by dependencies
        $dependencyResolver = $this->app->make(DependencyResolver::class);
        $bootOrder = $dependencyResolver->resolve($modules);

        // Boot modules in order
        foreach ($bootOrder as $moduleName) {
            $module = $modules->get($moduleName);

            if ($module && $module->getMetadata()->isEnabled()) {
                $this->bootModule($module);
            }
        }
    }

    /**
     * Boot a single module.
     *
     * @param BaseModule $module
     * @return void
     */
    public function bootModule(BaseModule $module): void
    {
        if (!$module->getMetadata()->isEnabled()) {
            return;
        }

        try {
            $module->getState()->transitionTo(ModuleState::STATE_BOOTING);

            // Load views if module is viewable
            if ($module instanceof ViewableInterface) {
                $this->loadModuleViews($module);
            }

            // Load routes if module is routable
            if ($module instanceof RoutableInterface) {
                $this->loadModuleRoutes($module);
            }

            // Load migrations if module is migratable
            if ($module instanceof MigratableInterface) {
                $this->loadModuleMigrations($module);
            }

            // Load translations if module is translatable
            if ($module instanceof TranslatableInterface) {
                $this->loadModuleTranslations($module);
            }

            // Register publishables if module is publishable
            if ($module instanceof PublishableInterface) {
                $this->registerModulePublishables($module);
            }

            // Call module's boot method if it exists
            if (method_exists($module, 'boot')) {
                $module->boot();
            }

            // Execute custom boot logic if module is bootable
            if ($module instanceof BootableInterface) {
                $module->bootModule();
            }

            // Expose services if module is exposable
            if ($module instanceof ExposableInterface) {
                $this->exposeModuleServices($module);
            }

            $module->getState()->transitionTo(ModuleState::STATE_BOOTED);
            $this->logModuleEvent($module, 'booted');
        } catch (\Throwable $e) {
            $module->getState()->markAsFailed($e);
            $this->logModuleError($module, 'boot failed', $e);
        }
    }

    /**
     * Register module configuration.
     *
     * @param BaseModule $module
     * @return void
     */
    protected function registerModuleConfig(BaseModule $module): void
    {
        $configPath = $module->getConfigPath();

        if (File::exists($configPath)) {
            $this->mergeConfigFrom($configPath, $module->getConfigKey());
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
            if (File::exists($file)) {
                Route::middleware($middleware[$type] ?? [])
                    ->prefix($type === 'api' ? 'api/' . $prefix : $prefix)
                    ->name(strtolower($module->getName()) . '.')
                    ->group($file);
            }
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
     * Load module translations.
     *
     * @param BaseModule $module
     * @return void
     */
    protected function loadModuleTranslations(BaseModule $module): void
    {
        $path = $module->getTranslationPath();

        if (File::isDirectory($path)) {
            $this->loadTranslationsFrom($path, strtolower($module->getName()));
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
        if ($this->app->runningInConsole()) {
            $moduleName = $module->getName();

            // Publish config
            $this->publishes($module->getPublishableConfig(), $moduleName . '-config');

            // Publish views
            $this->publishes($module->getPublishableViews(), $moduleName . '-views');

            // Publish assets
            $this->publishes($module->getPublishableAssets(), $moduleName . '-assets');
        }
    }

    /**
     * Expose module services.
     *
     * @param BaseModule $module
     * @return void
     */
    protected function exposeModuleServices(BaseModule $module): void
    {
        $serviceExposer = $this->app->make(ServiceExposer::class);
        $serviceExposer->expose($module->getName(), $module->expose());
    }

    /**
     * Publish assets.
     *
     * @return void
     */
    protected function publishAssets(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/ironflow.php' => config_path('ironflow.php'),
        ], 'ironflow-config');

        // Publish stubs
        $this->publishes([
            __DIR__ . '/../stubs' => resource_path('stubs/ironflow'),
        ], 'ironflow-stubs');
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
     * @param BaseModule $module
     * @param string $event
     * @return void
     */
    protected function logModuleEvent(BaseModule $module, string $event): void
    {
        if (!config('ironflow.logging.enabled', true)) {
            return;
        }

        $logEvents = config('ironflow.logging.log_events', []);
        if (!($logEvents[$event] ?? false)) {
            return;
        }

        $channel = config('ironflow.logging.channel', 'stack');

        ModuleEventBus::channel($channel)->info("[IronFlow] Module {$event}: {$module->getName()}");
    }

    /**
     * Log module error.
     *
     * @param BaseModule $module
     * @param string $message
     * @param \Throwable $e
     * @return void
     */
    protected function logModuleError(BaseModule $module, string $message, \Throwable $e): void
    {
        $channel = config('ironflow.logging.channel', 'stack');
        ModuleEventBus::channel($channel)->error("[IronFlow] Module {$message}: {$module->getName()}", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
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
            HotReloader::class,
        ];
    }
}
