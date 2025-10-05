<?php

declare(strict_types=1);

namespace IronFlow;

use Illuminate\Support\ServiceProvider;
use IronFlow\Core\Anvil;
use IronFlow\Console\Commands\ModuleCreateCommand;
use IronFlow\Console\Commands\ModulePublishCommand;
use IronFlow\Console\Commands\ModuleInstallCommand;
use IronFlow\Console\Commands\MakeControllerCommand;
use IronFlow\Console\Commands\MakeModelCommand;
use IronFlow\Console\Commands\MakeServiceCommand;
use IronFlow\Console\Commands\MakeMigrationCommand;
use IronFlow\Console\Commands\MakeFactoryCommand;
use IronFlow\Console\Commands\DiscoverCommand;
use IronFlow\Console\Commands\ListCommand;
use IronFlow\Console\Commands\InfoCommand;
use IronFlow\Console\Commands\EnableCommand;
use IronFlow\Console\Commands\DisableCommand;
use IronFlow\Console\Commands\CacheClearCommand;
use IronFlow\Console\Commands\CacheModulesCommand;
use IronFlow\Console\Commands\BootOrderCommand;

class IronFlowServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/ironflow.php',
            'ironflow'
        );

        // Register Anvil as singleton
        $this->app->singleton(Anvil::class, function ($app) {
            return new Anvil();
        });

        // Register alias
        $this->app->alias(Anvil::class, 'ironflow.anvil');

        // Auto-discover modules if enabled
        if (config('ironflow.auto_discover', true)) {
            $this->discoverModules();
        }
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/ironflow.php' => config_path('ironflow.php'),
        ], 'ironflow-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ModuleCreateCommand::class,
                ModulePublishCommand::class,
                ModuleInstallCommand::class,
                MakeControllerCommand::class,
                MakeModelCommand::class,
                MakeServiceCommand::class,
                MakeMigrationCommand::class,
                MakeFactoryCommand::class,
                DiscoverCommand::class,
                ListCommand::class,
                InfoCommand::class,
                EnableCommand::class,
                DisableCommand::class,
                CacheClearCommand::class,
                CacheModulesCommand::class,
                BootOrderCommand::class,
            ]);
        }

        // Boot modules
        $anvil = $this->app->make(Anvil::class);

        if (config('ironflow.auto_boot', true)) {
            try {
                $anvil->load()->boot();
            } catch (\Exception $e) {
                if (config('ironflow.throw_on_boot_failure', false)) {
                    throw $e;
                }

                logger()->error('IronFlow boot failed: ' . $e->getMessage(), [
                    'exception' => $e,
                ]);
            }
        }

        // Extend Artisan commands to support modules
        $this->extendArtisanCommands();
    }

    /**
     * Discover and register modules
     */
    protected function discoverModules(): void
    {
        $anvil = $this->app->make(Anvil::class);
        $modulesPath = config('ironflow.modules_path', app_path('Modules'));

        if (!is_dir($modulesPath)) {
            return;
        }

        // Check for cached modules
        $cacheFile = storage_path('framework/cache/ironflow/modules.php');

        if (config('ironflow.cache_modules', true) && file_exists($cacheFile)) {
            $cachedModules = require $cacheFile;

            foreach ($cachedModules as $moduleData) {
                try {
                    $module = $this->app->make($moduleData['class']);
                    $anvil->register($module);
                } catch (\Exception $e) {
                    logger()->warning("Failed to load cached module: {$moduleData['class']}", [
                        'exception' => $e->getMessage(),
                    ]);
                }
            }

            return;
        }

        // Discover modules from filesystem
        $directories = glob($modulesPath . '/*', GLOB_ONLYDIR);

        foreach ($directories as $directory) {
            $moduleName = basename($directory);
            $moduleClass = "App\\Modules\\{$moduleName}\\{$moduleName}Module";

            if (class_exists($moduleClass)) {
                try {
                    $module = $this->app->make($moduleClass);
                    $anvil->register($module);
                } catch (\Exception $e) {
                    logger()->warning("Failed to register module: {$moduleName}", [
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Extend Artisan commands to support module paths
     */
    protected function extendArtisanCommands(): void
    {
        // Extend migrate command to include module migrations
        $this->app->booted(function () {
            if ($this->app->runningInConsole()) {
                $migrator = $this->app['migrator'];
                $anvil = $this->app->make(Anvil::class);

                foreach ($anvil->getModules() as $moduleData) {
                    $module = $moduleData['instance'];
                    $migrationsPath = $module->path('Database/migrations');

                    if (is_dir($migrationsPath)) {
                        $migrator->path($migrationsPath);
                    }
                }
            }
        });
    }

    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return [
            Anvil::class,
            'ironflow.anvil',
        ];
    }
}
