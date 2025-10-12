<?php

declare(strict_types=1);

namespace IronFlow;

use IronFlow\Core\Anvil;
use IronFlow\Http\Middleware\LazyLoadModules;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

use IronFlow\Support\LazyLoader;
use IronFlow\Support\ModuleRegistry;
use IronFlow\Support\DependencyResolver;
use IronFlow\Support\ServiceExposer;
use IronFlow\Support\ConflictDetector;

use IronFlow\Console\Commands\MakeModuleCommand;
use IronFlow\Console\Commands\PublishModuleCommand;
use IronFlow\Console\Commands\EnableModuleCommand;
use IronFlow\Console\Commands\DisableModuleCommand;
use IronFlow\Console\Commands\HotReloadStatsCommand;
use IronFlow\Console\Commands\HotReloadWatchCommand;
use IronFlow\Console\Commands\InfoModuleCommand;
use IronFlow\Console\Commands\InstallModuleCommand;
use IronFlow\Console\Commands\ListModulesCommand;
use IronFlow\Console\Commands\LazyLoadStatsCommand;
use IronFlow\Console\Commands\LazyLoadWarmupCommand;
use IronFlow\Console\Commands\LazyLoadClearCommand;
use IronFlow\Console\Commands\LazyLoadTestCommand;
use IronFlow\Console\Commands\LazyLoadBenchmarkCommand;
use IronFlow\Console\Commands\CacheClearCommand;
use IronFlow\Console\Commands\CacheModulesCommand;
use IronFlow\Console\Commands\DiscoverCommand;
use IronFlow\Console\Commands\MakeModuleControllerCommand;
use IronFlow\Console\Commands\MakeModuleFactoryCommand;
use IronFlow\Console\Commands\MakeModuleMigrationCommand;
use IronFlow\Console\Commands\MakeModuleModelCommand;
use IronFlow\Console\Commands\MakeModuleRepositoryCommand;
use IronFlow\Console\Commands\MakeModuleServiceCommand;
use IronFlow\Console\Commands\SeedModuleCommand;

/**
 * IronFlowServiceProvider
 *
 * Main service provider for IronFlow framework.
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

        // Register Anvil
        $this->app->singleton('ironflow.anvil', function ($app) {
            return new Anvil(
                $app->make(ModuleRegistry::class),
                $app->make(DependencyResolver::class),
                $app->make(ServiceExposer::class),
                $app->make(ConflictDetector::class)
            );
        });

        $this->app->alias('ironflow.anvil', Anvil::class);

        $this->app->singleton(LazyLoader::class, function ($app) {
            $loader = new LazyLoader($app->make('ironflow.anvil'));

            // Inject lazy loader into ServiceExposer
            $app->make(ServiceExposer::class)->setLazyLoader($loader);

            return $loader;
        });
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

            // Publish stubs
            $this->publishes([
                __DIR__ . '/../stubs' => resource_path('stubs/ironflow'),
            ], 'ironflow-stubs');

            // Register commands
            $this->commands([
                MakeModuleCommand::class,
                PublishModuleCommand::class,
                InstallModuleCommand::class,
                EnableModuleCommand::class,
                DisableModuleCommand::class,
                DiscoverCommand::class,
                ListModulesCommand::class,
                InfoModuleCommand::class,

                MakeModuleModelCommand::class,
                MakeModuleMigrationCommand::class,
                MakeModuleFactoryCommand::class,
                MakeModuleControllerCommand::class,
                MakeModuleServiceCommand::class,
                MakeModuleRepositoryCommand::class,

                CacheModulesCommand::class,
                CacheClearCommand::class,
                LazyLoadClearCommand::class,
                LazyLoadStatsCommand::class,
                LazyLoadWarmupCommand::class,
                LazyLoadTestCommand::class,
                LazyLoadBenchmarkCommand::class,
                HotReloadWatchCommand::class,
                HotReloadStatsCommand::class,
                SeedModuleCommand::class,
                
            ]);
        }

        $this->app['router']->aliasMiddleware('ironflow.lazy', LazyLoadModules::class);

        // Discover and boot modules
        if (config('ironflow.auto_discover', true)) {
            $anvil = $this->app->make('ironflow.anvil');
            $anvil->discover();

             // Register migrations for all modules BEFORE booting
            $this->registerAllModuleMigrations($anvil);

            // Use lazy loading if enabled
            if (config('ironflow.lazy_load.enabled', true)) {
                $lazyLoader = $this->app->make(LazyLoader::class);

                // Load eager modules only
                $lazyLoader->loadEager();

                // Register global middleware for route-based lazy loading
                if (!$this->app->runningInConsole()) {
                    $this->app['router']->pushMiddlewareToGroup('web', LazyLoadModules::class);
                }
            } else {
                // Boot all modules immediately (traditional way)
                $anvil->bootAll();
            }
        }
    }

    /**
     * Register migrations for all modules.
     *
     * @param Anvil $anvil
     * @return void
     */
    protected function registerAllModuleMigrations(Anvil $anvil): void
    {
        $modules = $anvil->getModules();

        foreach ($modules as $module) {
            // Check if module implements MigratableInterface
            if ($module instanceof \IronFlow\Contracts\MigratableInterface) {
                $migrationPath = $module->getMigrationPath();
                
                if (File::isDirectory($migrationPath)) {
                    $this->loadMigrationsFrom($migrationPath);
                }
            }
        }
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
        ];
    }
}
