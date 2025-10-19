<?php

declare(strict_types=1);

namespace IronFlow;

use Illuminate\Support\ServiceProvider;
use IronFlow\Core\Anvil;

class IronFlowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/ironflow.php', 'ironflow');

        // Register Anvil as singleton
        $this->app->singleton(
            Anvil::class,
            fn($app) => new Anvil($app)
        );

        // Register facade accessor
        $this->app->alias(Anvil::class, 'anvil');
    }

    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/ironflow.php' => config_path('ironflow.php'),
        ], 'ironflow-config');

        // Publish stubs
        $this->publishes([
            __DIR__ . '/Console/Stubs' => base_path('stubs/ironflow'),
        ], 'ironflow-stubs');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \IronFlow\Console\Commands\DiscoverCommand::class,
                \IronFlow\Console\Commands\CacheCommand::class,
                \IronFlow\Console\Commands\ClearCommand::class,
                \IronFlow\Console\Commands\MakeModuleCommand::class,
                \IronFlow\Console\Commands\MigrateCommand::class,
                \IronFlow\Console\Commands\ListCommand::class,
                \IronFlow\Console\Commands\InstallCommand::class,
                \IronFlow\Console\Commands\ActivatePermissionsCommand::class,
                \IronFlow\Console\Commands\SyncPermissionsCommand::class,
            ]);
        }

        // Bootstrap Anvil
        $this->app->make(Anvil::class)->bootstrap();
    }
}
