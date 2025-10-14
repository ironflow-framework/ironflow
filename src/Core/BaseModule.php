<?php

declare(strict_types=1);

namespace IronFlow\Core;
/**
 * BaseModule
 *
 * Base class for all IronFlow modules.
 * This is purely a structural contract that describes module capabilities.
 */
abstract class BaseModule
{
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
     */
    public function __construct()
    {
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
     * Register module services (optional, called by IronflowServiceProvider).
     * This is where you bind services to the container.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    public function register($app): void
    {
        // Override in child modules if needed
    }

    /**
     * Boot module (optional, called by IronflowServiceProvider).
     * This is where you can execute boot logic.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    public function boot($app): void
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
     * Get module path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->modulePath;
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
        // Override in child modules if needed
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
        // Override in child modules if needed
    }

    /**
     * Uninstall the module.
     *
     * @return void
     */
    public function uninstall(): void
    {
        // Override in child modules if needed
    }

    // =======================================================================
    // Default Implementations for Interfaces
    // These provide sensible defaults that can be overridden
    // =======================================================================

    /**
     * Get view namespace (for ViewableInterface).
     *
     * @return string
     */
    public function getViewNamespace(): string
    {
        return strtolower($this->moduleName);
    }

    /**
     * Get view paths (for ViewableInterface).
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
     * Get route files (for RoutableInterface).
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
     * Get route middleware (for RoutableInterface).
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
     * Get route prefix (for RoutableInterface).
     *
     * @return string|null
     */
    public function getRoutePrefix(): ?string
    {
        return strtolower($this->moduleName);
    }

    /**
     * Get migration path (for MigratableInterface).
     *
     * @return string
     */
    public function getMigrationPath(): string
    {
        return $this->modulePath . '/Database/Migrations';
    }

    /**
     * Get migration prefix (for MigratableInterface).
     *
     * @return string
     */
    public function getMigrationPrefix(): string
    {
        return strtolower($this->moduleName) . '_';
    }

    /**
     * Get config path (for ConfigurableInterface).
     *
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->modulePath . '/config/' . strtolower($this->moduleName) . '.php';
    }

    /**
     * Get config key (for ConfigurableInterface).
     *
     * @return string
     */
    public function getConfigKey(): string
    {
        return strtolower($this->moduleName);
    }

    /**
     * Get translation path (for TranslatableInterface).
     *
     * @return string
     */
    public function getTranslationPath(): string
    {
        return $this->modulePath . '/Resources/lang';
    }

    /**
     * Get translation namespace (for TranslatableInterface).
     *
     * @return string
     */
    public function getTranslationNamespace(): string
    {
        return strtolower($this->moduleName);
    }

    /**
     * Get publishable assets (for PublishableInterface).
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
     * Get publishable config (for PublishableInterface).
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
     * Get publishable views (for PublishableInterface).
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
     * Get seeder path (for SeedableInterface).
     *
     * @return string
     */
    public function getSeederPath(): string
    {
        return $this->modulePath . '/Database/Seeders';
    }

    /**
     * Get seeders (for SeedableInterface).
     *
     * @return array
     */
    public function getSeeders(): array
    {
        return [];
    }

    /**
     * Get seeder priority (for SeedableInterface).
     *
     * @return int
     */
    public function getSeederPriority(): int
    {
        return 50;
    }

    /**
     * Get permissions (for PermissionableInterface).
     *
     * @return array
     */
    public function getPermissions(): array
    {
        return [];
    }

    /**
     * Expose services (for ExposableInterface).
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
}
