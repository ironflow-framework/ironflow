<?php

namespace IronFlow\Core;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Artisan;
use IronFlow\Contracts\ModuleInterface;
use IronFlow\Contracts\RoutableInterface;
use IronFlow\Contracts\ViewableInterface;
use IronFlow\Contracts\ConfigurableInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * BaseModule
 *
 * Abstract base class for IronFlow modules
 * Provides common functionality and sensible defaults
 */
abstract class BaseModule implements ModuleInterface
{
    protected string $modulePath;
    protected string $moduleNamespace;
    protected ?ModuleMetadata $_metadata = null;
    protected Container $app;

    public function __construct()
    {
        $this->modulePath = $this->resolveModulePath();
        $this->moduleNamespace = $this->resolveModuleNamespace();
        $this->app = app();
    }

    /**
     * Get module metadata
     * Override this method to provide custom metadata
     */
    abstract public function metadata(): ModuleMetadata;

    /**
     * Register module services
     */
    public function register(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerMigrations();
    }

    /**
     * Boot module
     */
    public function boot(): void
    {
        $this->bootRoutes();
        $this->bootConfig();
    }

    /**
     * Get module path
     */
    public function path(string $path = ''): string
    {
        return $this->modulePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Get module namespace
     */
    public function namespace(): string
    {
        return $this->moduleNamespace;
    }

    /**
     * Resolve module path from class location
     */
    protected function resolveModulePath(): string
    {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName());
    }

    /**
     * Resolve module namespace
     */
    protected function resolveModuleNamespace(): string
    {
        return (new \ReflectionClass($this))->getNamespaceName();
    }

    /**
     * Register module configuration
     */
    protected function registerConfig(): void
    {
        if (!$this instanceof ConfigurableInterface) {
            return;
        }

        $configPath = $this->configPath();
        if (file_exists($configPath)) {
            $this->mergeConfig();
        }
    }

    /**
     * Boot module configuration
     */
    protected function bootConfig(): void
    {
        if (!$this instanceof ConfigurableInterface) {
            return;
        }

        $configPath = $this->configPath();
        if (file_exists($configPath)) {
            $publishes = [
                $configPath => config_path($this->configKey() . '.php'),
            ];

            if (method_exists($this, 'publishes')) {
                $this->publishes($publishes, 'config');
            }
        }
    }

    /**
     * Register module views
     */
    protected function registerViews(): void
    {
        if (!$this instanceof ViewableInterface) {
            return;
        }

        $viewsPath = $this->viewsPath();
        if (is_dir($viewsPath)) {
            View::addNamespace($this->viewNamespace(), $viewsPath);
        }
    }

    /**
     * Boot module routes
     */
    protected function bootRoutes(): void
    {
        if (!$this instanceof RoutableInterface) {
            return;
        }

        $this->registerRoutes();
    }

    /**
     * Register module migrations
     */
    protected function registerMigrations(): void
    {
        $migrationsPath = $this->path('Database/migrations');

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    /**
     * Load migrations from path
     */
    protected function loadMigrationsFrom(string $path): void
    {
        if (method_exists($this, 'loadMigrationsFrom')) {
            app()->make('migrator')->path($path);
        }
    }

    /**
     * Helper to load routes
     */
    protected function loadRoutesFrom(string $path): void
    {
        if (file_exists($path)) {
            Route::middleware('web')->group($path);
        }
    }

    /**
     * Helper to load API routes
     */
    protected function loadApiRoutesFrom(string $path): void
    {
        if (file_exists($path)) {
            Route::prefix('api')
                ->middleware('api')
                ->group($path);
        }
    }

    public function call(string $command, array $args = []): void
    {
        Artisan::call($command, $args);
    }

    public function publishes(array $paths, ?string $group = null): void
    {
        if (function_exists('app') && method_exists(app(), 'make')) {
            $publisher = app()->make('Illuminate\\Foundation\\Console\\VendorPublishCommand');
            if ($publisher && method_exists($publisher, 'publish')) {
                $publisher->publish($paths, $group);
            }
        }
    }

    /**
     * Get module name from class name
     */
    protected function getModuleName(): string
    {
        $className = class_basename($this);
        return str_replace('Module', '', $className);
    }

    /**
     * Convert module name to snake case
     */
    protected function getModuleSlug(): string
    {
        return Str::snake($this->getModuleName());
    }

    /**
     * Default config path implementation
     */
    public function configPath(): string
    {
        return $this->path('config/' . $this->getModuleSlug() . '.php');
    }

    /**
     * Default config key implementation
     */
    public function configKey(): string
    {
        return $this->getModuleSlug();
    }

    /**
     * Default views path implementation
     */
    public function viewsPath(): string
    {
        return $this->path('Resources/views');
    }

    /**
     * Default view namespace implementation
     */
    public function viewNamespace(): string
    {
        return $this->getModuleSlug();
    }

    /**
     * Merge config helper
     */
    public function mergeConfig(): void
    {
        config()->set(
            $this->configKey(),
            array_merge(
                require $this->configPath(),
                config($this->configKey(), [])
            )
        );
    }
}
