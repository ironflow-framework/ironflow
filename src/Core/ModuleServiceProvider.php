<?php

declare(strict_types=1);

namespace IronFlow\Core;

use Illuminate\Support\ServiceProvider;
use IronFlow\Contracts\{
    ViewableInterface,
    RoutableInterface,
    MigratableInterface,
    ConfigurableInterface
};

abstract class ModuleServiceProvider extends ServiceProvider
{
    protected BaseModule $module;

    public function __construct($app, BaseModule $module)
    {
        parent::__construct($app);
        $this->module = $module;
    }

    public function register(): void
    {
        // Register config if module is configurable
        if ($this->module instanceof ConfigurableInterface) {
            $configPath = $this->module->getConfigPath();
            $configKey = $this->module->getConfigKey();

            if (file_exists($configPath)) {
                $this->mergeConfigFrom($configPath, $configKey);
            }
        }
    }

    public function boot(): void
    {
        // Load routes
        if ($this->module instanceof RoutableInterface) {
            $this->loadRoutes();
        }

        // Load views
        if ($this->module instanceof ViewableInterface) {
            $this->loadViews();
        }

        // Load migrations
        if ($this->module instanceof MigratableInterface) {
            $this->loadMigrations();
        }

        // Publish config
        if ($this->module instanceof ConfigurableInterface) {
            $this->publishConfiguration();
        }
    }

    protected function loadRoutes(): void
    {
        $routesPath = $this->module->getRoutesPath();

        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }
    }

    protected function loadViews(): void
    {
        $viewsPath = $this->module->getViewsPath();
        $namespace = $this->module->getViewNamespace();

        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, $namespace);
        }
    }

    protected function loadMigrations(): void
    {
        $migrationsPath = $this->module->getMigrationsPath();

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    protected function publishConfiguration(): void
    {
        $configPath = $this->module->getConfigPath();
        $configKey = $this->module->getConfigKey();

        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path("{$configKey}.php"),
            ], "{$configKey}-config");
        }
    }
}
