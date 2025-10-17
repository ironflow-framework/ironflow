<?php

namespace IronFlow\Core;

use Illuminate\Contracts\Foundation\Application;

/**
 * BaseModule
 *
 * Base class for all IronFlow modules.
 * No longer extends ServiceProvider - acts as pure module descriptor.
 */
abstract class BaseModule
{
    /**
     * @var Application
     */
    protected Application $app;

    /**
     * @var ModuleMetaData Module metadata
     */
    protected ModuleMetaData $metadata;

    /**
     * @var ModuleState Module state
     */
    protected ModuleState $state;

    /**
     * @var string Module base path
     */
    protected string $modulePath;

    /**
     * @var string Module name
     */
    protected string $moduleName;

    /**
     * Create a new module instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->moduleName = $this->getModuleName();
        $this->modulePath = $this->getModulePath();
        $this->metadata = $this->createMetadata();
        $this->state = new ModuleState();
    }

    /**
     * Get module name (must be implemented by child).
     *
     * @return string
     */
    abstract protected function getModuleName(): string;

    /**
     * Create module metadata.
     *
     * @return ModuleMetaData
     */
    abstract protected function createMetadata(): ModuleMetaData;

    /**
     * Register module services (optional).
     * Called during service container registration phase.
     *
     * @return void
     */
    public function register(): void
    {
        // Override in child modules if needed
    }

    /**
     * Boot module (optional).
     * Called during application boot phase.
     *
     * @return void
     */
    public function boot(): void
    {
        // Override in child modules if needed
    }

    /**
     * Get module base path.
     *
     * @return string
     */
    protected function getModulePath(): string
    {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName());
    }

    /**
     * Get module metadata.
     *
     * @return ModuleMetaData
     */
    public function getMetadata(): ModuleMetaData
    {
        return $this->metadata;
    }

    /**
     * Get module state.
     *
     * @return ModuleState
     */
    public function getState(): ModuleState
    {
        return $this->state;
    }

    /**
     * Get module name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->moduleName;
    }

    /**
     * Get module path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->modulePath;
    }

    /**
     * Get application instance.
     *
     * @return Application
     */
    public function getApp(): Application
    {
        return $this->app;
    }

    // =======================================================================
    // Default Implementations for Interfaces
    // =======================================================================

    /**
     * Get view namespace (ViewableInterface).
     *
     * @return string
     */
    public function getViewNamespace(): string
    {
        return strtolower($this->moduleName);
    }

    /**
     * Get view paths (ViewableInterface).
     *
     * @return array
     */
    public function getViewPaths(): array
    {
        return [
            $this->modulePath . '/Resources/views',
        ];
    }

    /**
     * Get route files (RoutableInterface).
     *
     * @return array
     */
    public function getRouteFiles(): array
    {
        return [
            'web' => $this->modulePath . '/Routes/web.php',
            'api' => $this->modulePath . '/Routes/api.php',
        ];
    }

    /**
     * Get route middleware (RoutableInterface).
     *
     * @return array
     */
    public function getRouteMiddleware(): array
    {
        return [
            'web' => ['web'],
            'api' => ['api'],
        ];
    }

    /**
     * Get route prefix (RoutableInterface).
     *
     * @return string|null
     */
    public function getRoutePrefix(): ?string
    {
        return strtolower($this->moduleName);
    }

    /**
     * Get migration path (MigratableInterface).
     *
     * @return string
     */
    public function getMigrationPath(): string
    {
        return $this->modulePath . '/Database/Migrations';
    }

    /**
     * Get migration prefix (MigratableInterface).
     *
     * @return string
     */
    public function getMigrationPrefix(): string
    {
        return strtolower($this->moduleName) . '_';
    }

    /**
     * Get config path (ConfigurableInterface).
     *
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->modulePath . '/config/' . strtolower($this->moduleName) . '.php';
    }

    /**
     * Get config key (ConfigurableInterface).
     *
     * @return string
     */
    public function getConfigKey(): string
    {
        return strtolower($this->moduleName);
    }

    /**
     * Get translation path (TranslatableInterface).
     *
     * @return string
     */
    public function getTranslationPath(): string
    {
        return $this->modulePath . '/Resources/lang';
    }

    /**
     * Get publishable assets (PublishableInterface).
     *
     * @return array
     */
    public function getPublishableAssets(): array
    {
        return [
            $this->modulePath . '/Resources/css' => public_path('vendor/' . strtolower($this->moduleName) . '/css'),
            $this->modulePath . '/Resources/js' => public_path('vendor/' . strtolower($this->moduleName) . '/js'),
        ];
    }

    /**
     * Get publishable config (PublishableInterface).
     *
     * @return array
     */
    public function getPublishableConfig(): array
    {
        return [
            $this->getConfigPath() => config_path(strtolower($this->moduleName) . '.php'),
        ];
    }

    /**
     * Get publishable views (PublishableInterface).
     *
     * @return array
     */
    public function getPublishableViews(): array
    {
        return [
            $this->modulePath . '/Resources/views' => resource_path('views/vendor/' . strtolower($this->moduleName)),
        ];
    }

    /**
     * Expose services (ExposableInterface).
     *
     * @return array
     */
    public function expose(): array
    {
        return [
            'public' => [],
            'linked' => [],
        ];
    }

    /**
     * Get seeders (SeedableInterface).
     *
     * @return array
     */
    public function getSeeders(): array
    {
        return [];
    }

    /**
     * Get seeder path (SeedableInterface).
     *
     * @return string
     */
    public function getSeederPath(): string
    {
        return $this->modulePath . '/Database/Seeders';
    }

    /**
     * Get seeder priority (SeedableInterface).
     *
     * @return int
     */
    public function getSeederPriority(): int
    {
        return 50;
    }

    // =======================================================================
    // Lifecycle Methods
    // =======================================================================

    /**
     * Install the module.
     *
     * @return void
     */
    public function install(): void
    {
        // Override in child if needed
    }

    /**
     * Enable the module.
     *
     * @return void
     */
    public function enable(): void
    {
        $this->metadata->enable();
    }

    /**
     * Disable the module.
     *
     * @return void
     */
    public function disable(): void
    {
        $this->metadata->disable();
        $this->state->transitionTo(ModuleState::STATE_DISABLED);
    }

    /**
     * Update the module.
     *
     * @return void
     */
    public function update(): void
    {
        // Override in child if needed
    }

    /**
     * Uninstall the module.
     *
     * @return void
     */
    public function uninstall(): void
    {
        // Override in child if needed
    }
}
