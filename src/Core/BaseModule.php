<?php

namespace IronFlow\Core;

use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\{Artisan, Route, View};
use Illuminate\Support\Str;
use IronFlow\Contracts\{
    ModuleInterface,
    RoutableInterface,
    ViewableInterface,
    ConfigurableInterface,
    ExposableInterface
};

/**
 * Class BaseModule
 *
 * The abstract foundation for all IronFlow modules.
 * Provides lifecycle hooks (register, boot), dependency container access,
 * and standardized resource registration (config, views, routes, migrations).
 *
 * Each module extends this class to integrate seamlessly with the IronFlow core.
 *
 * @package IronFlow\Core
 */
abstract class BaseModule implements ModuleInterface
{
    /**
     * The application container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected Container $app;

    /**
     * The absolute file system path to the module.
     *
     * @var string
     */
    protected string $modulePath;

    /**
     * The fully qualified PHP namespace of the module.
     *
     * @var string
     */
    protected string $moduleNamespace;

    /**
     * Cached metadata instance for this module.
     *
     * @var ModuleMetadata|null
     */
    protected ?ModuleMetadata $_metadata = null;

    /**
     * Create a new module instance.
     *
     * @param \Illuminate\Contracts\Container\Container|null $app
     *        The application container, defaults to the global Laravel container.
     */
    public function __construct(?Container $app = null)
    {
        $this->app = $app ?? Application::getInstance();
        $this->modulePath = $this->resolveModulePath();
        $this->moduleNamespace = $this->resolveModuleNamespace();
    }

    /**
     * Define module metadata (must be implemented by each module).
     *
     * @return ModuleMetadata
     */
    abstract public function metadata(): ModuleMetadata;

    /**
     * Register all services, configurations, and resources for the module.
     *
     * Called during the application's service registration phase.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerMigrations();
    }

    /**
     * Boot runtime elements such as routes or publishable configurations.
     *
     * Called after all modules have been registered.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->bootRoutes();
        $this->bootConfig();
    }

    // ---------------------------------------------------------------------
    // Container Access
    // ---------------------------------------------------------------------

    /**
     * Get the application container instance.
     *
     * @return \Illuminate\Contracts\Container\Container
     */
    public function container(): Container
    {
        return $this->app;
    }

    // ---------------------------------------------------------------------
    // Configuration Handling
    // ---------------------------------------------------------------------

    /**
     * Register the module configuration into the application container.
     *
     * @return void
     */
    protected function registerConfig(): void
    {
        if (!$this instanceof ConfigurableInterface) {
            return;
        }

        $path = $this->configPath();

        if (file_exists($path)) {
            $this->app['config']->set(
                $this->configKey(),
                array_merge(
                    require $path,
                    $this->app['config']->get($this->configKey(), [])
                )
            );
        }
    }

    /**
     * Boot the module configuration by publishing it to the config directory.
     *
     * @return void
     */
    protected function bootConfig(): void
    {
        if (!$this instanceof ConfigurableInterface) {
            return;
        }

        $path = $this->configPath();

        if (file_exists($path)) {
            $this->app['files']->ensureDirectoryExists(config_path());

            $destination = config_path($this->configKey() . '.php');

            if (!file_exists($destination)) {
                @copy($path, $destination);
            }
        }
    }

    // ---------------------------------------------------------------------
    // View Registration
    // ---------------------------------------------------------------------

    /**
     * Register the module views with the Laravel View Factory.
     *
     * @return void
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

    // ---------------------------------------------------------------------
    // Route Bootstrapping
    // ---------------------------------------------------------------------

    /**
     * Load and register the module routes.
     *
     * @return void
     */
    protected function bootRoutes(): void
    {
        if (!$this instanceof RoutableInterface) {
            return;
        }

        $webRoutes = $this->path('Routes/web.php');
        $apiRoutes = $this->path('Routes/api.php');

        if (file_exists($webRoutes)) {
            Route::middleware('web')->group($webRoutes);
        }

        if (file_exists($apiRoutes)) {
            Route::prefix('api')->middleware('api')->group($apiRoutes);
        }
    }

    // ---------------------------------------------------------------------
    // Migrations
    // ---------------------------------------------------------------------

    /**
     * Register the module migrations directory with the migrator.
     *
     * @return void
     */
    protected function registerMigrations(): void
    {
        $path = $this->path('Database/Migrations');

        if (is_dir($path)) {
            $this->app->make('migrator')->path($path);
        }
    }

    // ---------------------------------------------------------------------
    // Exposure
    // ---------------------------------------------------------------------

    /**
     * Retrieve publicly and internally exposed module resources.
     *
     * @return array{
     *     public: array,
     *     internal: array
     * }
     */
    public function getExposed(): array
    {
        if ($this instanceof ExposableInterface) {
            $exposed = $this->expose();

            return [
                'public'   => $exposed['public'] ?? [],
                'internal' => $exposed['internal'] ?? [],
            ];
        }

        return ['public' => [], 'internal' => []];
    }

    // ---------------------------------------------------------------------
    // Utility Methods
    // ---------------------------------------------------------------------

    /**
     * Resolve the absolute module path from the class file location.
     *
     * @return string
     */
    protected function resolveModulePath(): string
    {
        return dirname((new \ReflectionClass($this))->getFileName());
    }

    /**
     * Resolve the module's namespace from the class definition.
     *
     * @return string
     */
    protected function resolveModuleNamespace(): string
    {
        return (new \ReflectionClass($this))->getNamespaceName();
    }

    /**
     * Build an absolute path within the module directory.
     *
     * @param string $append
     * @return string
     */
    public function path(string $append = ''): string
    {
        return $this->modulePath . ($append ? DIRECTORY_SEPARATOR . $append : '');
    }

    /**
     * Get the fully qualified module namespace.
     *
     * @return string
     */
    public function namespace(): string
    {
        return $this->moduleNamespace;
    }

    /**
     * Get the default configuration file path for the module.
     *
     * @return string
     */
    public function configPath(): string
    {
        return $this->path('config/' . $this->getModuleSlug() . '.php');
    }

    /**
     * Get the configuration key used for this module.
     *
     * @return string
     */
    public function configKey(): string
    {
        return $this->getModuleSlug();
    }

    /**
     * Get the default views directory for the module.
     *
     * @return string
     */
    public function viewsPath(): string
    {
        return $this->path('Resources/views');
    }

    /**
     * Get the view namespace used when registering module views.
     *
     * @return string
     */
    public function viewNamespace(): string
    {
        return $this->getModuleSlug();
    }

    /**
     * Get the base module name (without the 'Module' suffix).
     *
     * @return string
     */
    protected function getModuleName(): string
    {
        return str_replace('Module', '', class_basename($this));
    }

    /**
     * Get the module slug (snake_case version of its name).
     *
     * @return string
     */
    protected function getModuleSlug(): string
    {
        return Str::snake($this->getModuleName());
    }

    /**
     * Execute an Artisan command within the module context.
     *
     * @param string $command
     * @param array<string, mixed> $args
     * @return void
     */
    public function call(string $command, array $args = []): void
    {
        Artisan::call($command, $args);
    }

    /**
     * Retrieve and cache the module metadata.
     *
     * @return ModuleMetadata
     */
    public function getMetadata(): ModuleMetadata
    {
        return $this->_metadata ??= $this->metadata();
    }
}
