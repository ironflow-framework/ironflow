<?php

declare(strict_types=1);

namespace IronFlow\Core;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use IronFlow\Contracts\ViewableInterface;
use IronFlow\Contracts\RoutableInterface;
use IronFlow\Contracts\MigratableInterface;
use IronFlow\Contracts\BootableInterface;
use IronFlow\Contracts\ConfigurableInterface;
use IronFlow\Contracts\PublishableInterface;
use IronFlow\Contracts\ExposableInterface;
use IronFlow\Contracts\ExportableInterface;

/**
 * BaseModule
 *
 * Base class for all IronFlow modules. Extends Laravel ServiceProvider
 * and provides concrete implementations for all activable interfaces.
 */
abstract class BaseModule extends ServiceProvider
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
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct($app)
    {
        parent::__construct($app);

        $this->moduleName = $this->getModuleName();
        $this->modulePath = $this->getModulePath();
        $this->metadata = $this->createMetadata();
        $this->state = new ModuleState();
    }

    /**
     * Register the module services.
     *
     * @return void
     */
    public function register(): void
    {
        try {
            $this->state->transitionTo(ModuleState::STATE_PRELOADED);

            // Register configuration if module is configurable
            if ($this instanceof ConfigurableInterface) {
                $this->registerConfig();
            }

            // Register module-specific services
            $this->registerServices();

            $this->logEvent('registered', "Module {$this->moduleName} registered successfully");
        } catch (\Throwable $e) {
            $this->state->markAsFailed($e);
            $this->logEvent('failed', "Module {$this->moduleName} failed to register: {$e->getMessage()}", 'error');
            throw $e;
        }
    }

    /**
     * Bootstrap the module services.
     *
     * @return void
     */
    public function boot(): void
    {
        if (!$this->metadata->isEnabled()) {
            return;
        }

        try {
            $this->state->transitionTo(ModuleState::STATE_BOOTING);

            // Register views if module is viewable
            if ($this instanceof ViewableInterface) {
                $this->registerViews();
            }

            // Register routes if module is routable
            if ($this instanceof RoutableInterface) {
                $this->registerRoutes();
            }

            // Register migrations if module is migratable
            if ($this instanceof MigratableInterface) {
                $this->registerMigrations();
            }

            // Register publishables if module is publishable
            if ($this instanceof PublishableInterface) {
                $this->registerPublishables();
            }

            // Execute custom boot logic if module is bootable
            if ($this instanceof BootableInterface) {
                $this->bootModule();
            }

            $this->state->transitionTo(ModuleState::STATE_BOOTED);
            $this->logEvent('booted', "Module {$this->moduleName} booted successfully");
        } catch (\Throwable $e) {
            $this->state->markAsFailed($e);
            $this->logEvent('failed', "Module {$this->moduleName} failed to boot: {$e->getMessage()}", 'error');
            throw $e;
        }
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
     * Register module-specific services.
     * Override this method in child modules.
     *
     * @return void
     */
    protected function registerServices(): void
    {
        // Override in child modules
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

    // ========================================================================
    // ViewableInterface Implementation
    // ========================================================================

    /**
     * Get view namespace.
     *
     * @return string
     */
    public function getViewNamespace(): string
    {
        return strtolower($this->moduleName);
    }

    /**
     * Get view paths.
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
     * Register views.
     *
     * @return void
     */
    public function registerViews(): void
    {
        $viewPaths = $this->getViewPaths();
        $namespace = $this->getViewNamespace();

        foreach ($viewPaths as $path) {
            if (File::isDirectory($path)) {
                $this->loadViewsFrom($path, $namespace);
            }
        }
    }

    // ========================================================================
    // RoutableInterface Implementation
    // ========================================================================

    /**
     * Get route files.
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
     * Get route middleware.
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
     * Get route prefix.
     *
     * @return string|null
     */
    public function getRoutePrefix(): ?string
    {
        return strtolower($this->moduleName);
    }

    /**
     * Register routes.
     *
     * @return void
     */
    public function registerRoutes(): void
    {
        $routeFiles = $this->getRouteFiles();
        $middleware = $this->getRouteMiddleware();
        $prefix = $this->getRoutePrefix();

        foreach ($routeFiles as $type => $file) {
            if (File::exists($file)) {
                Route::middleware($middleware[$type] ?? [])
                    ->prefix($type === 'api' ? 'api/' . $prefix : $prefix)
                    ->group($file);
            }
        }
    }

    // ========================================================================
    // MigratableInterface Implementation
    // ========================================================================

    /**
     * Get migration path.
     *
     * @return string
     */
    public function getMigrationPath(): string
    {
        return $this->modulePath . '/Database/Migrations';
    }

    /**
     * Get migration prefix.
     *
     * @return string
     */
    public function getMigrationPrefix(): string
    {
        return strtolower($this->moduleName) . '_';
    }

    /**
     * Register migrations.
     *
     * @return void
     */
    public function registerMigrations(): void
    {
        $path = $this->getMigrationPath();

        if (File::isDirectory($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    /**
     * Run migrations.
     *
     * @return void
     */
    public function runMigrations(): void
    {
        Artisan::call('migrate', [
            '--path' => $this->getMigrationPath(),
            '--force' => true,
        ]);
    }

    /**
     * Rollback migrations.
     *
     * @return void
     */
    public function rollbackMigrations(): void
    {
        Artisan::call('migrate:rollback', [
            '--path' => $this->getMigrationPath(),
            '--force' => true,
        ]);
    }

    // ========================================================================
    // ConfigurableInterface Implementation
    // ========================================================================

    /**
     * Get config path.
     *
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->modulePath . '/config/' . strtolower($this->moduleName) . '.php';
    }

    /**
     * Get config key.
     *
     * @return string
     */
    public function getConfigKey(): string
    {
        return strtolower($this->moduleName);
    }

    /**
     * Register config.
     *
     * @return void
     */
    public function registerConfig(): void
    {
        $configPath = $this->getConfigPath();

        if (File::exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->getConfigKey());
        }
    }

    /**
     * Merge config.
     *
     * @return void
     */
    public function mergeConfig(): void
    {
        $this->registerConfig();
    }

    // ========================================================================
    // PublishableInterface Implementation
    // ========================================================================

    /**
     * Get publishable assets.
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
     * Get publishable config.
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
     * Get publishable views.
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
     * Register publishables.
     *
     * @return void
     */
    public function registerPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes($this->getPublishableConfig(), $this->moduleName . '-config');

            // Publish views
            $this->publishes($this->getPublishableViews(), $this->moduleName . '-views');

            // Publish assets
            $this->publishes($this->getPublishableAssets(), $this->moduleName . '-assets');
        }
    }

    // ========================================================================
    // ExposableInterface Implementation
    // ========================================================================

    /**
     * Expose services.
     *
     * @return array
     */
    public function expose(): array
    {
        return [
            'public' => $this->getExposedPublic(),
            'linked' => [],
        ];
    }

    /**
     * Get exposed public services.
     *
     * @return array
     */
    public function getExposedPublic(): array
    {
        return [];
    }

    /**
     * Get exposed services for specific module.
     *
     * @param string $moduleName
     * @return array
     */
    public function getExposedForModule(string $moduleName): array
    {
        return [];
    }

    // ========================================================================
    // ExportableInterface Implementation
    // ========================================================================

    /**
     * Export module for Packagist.
     *
     * @return array
     */
    public function export(): array
    {
        return [
            'files' => [
                $this->modulePath . '/Http',
                $this->modulePath . '/Services',
                $this->modulePath . '/Models',
            ],
            'assets' => [
                $this->modulePath . '/Resources',
            ],
            'config' => [
                $this->getConfigPath(),
            ],
            'stubs' => [],
            'exclude' => [
                '*.log',
                '.DS_Store',
                'node_modules',
            ],
        ];
    }

    /**
     * Get package name.
     *
     * @return string
     */
    public function getPackageName(): string
    {
        return 'vendor/' . strtolower($this->moduleName);
    }

    /**
     * Get package description.
     *
     * @return string
     */
    public function getPackageDescription(): string
    {
        return $this->metadata->getDescription();
    }

    /**
     * Get package dependencies.
     *
     * @return array
     */
    public function getPackageDependencies(): array
    {
        return [
            'php' => '^8.2',
            'illuminate/support' => '^12.0',
            'ironflow/ironflow' => '^1.0',
        ];
    }

    /**
     * Get package autoload.
     *
     * @return array
     */
    public function getPackageAutoload(): array
    {
        return [
            'psr-4' => [
                'Modules\\' . $this->moduleName . '\\' => 'src/',
            ],
        ];
    }

    // ========================================================================
    // Lifecycle Methods
    // ========================================================================

    /**
     * Install the module.
     *
     * @return void
     */
    public function install(): void
    {
        if ($this instanceof MigratableInterface) {
            $this->runMigrations();
        }

        $this->logEvent('installed', "Module {$this->moduleName} installed");
    }

    /**
     * Enable the module.
     *
     * @return void
     */
    public function enable(): void
    {
        $this->metadata->enable();
        $this->logEvent('enabled', "Module {$this->moduleName} enabled");
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
        $this->logEvent('disabled', "Module {$this->moduleName} disabled");
    }

    /**
     * Update the module.
     *
     * @return void
     */
    public function update(): void
    {
        if ($this instanceof MigratableInterface) {
            $this->runMigrations();
        }

        $this->logEvent('updated', "Module {$this->moduleName} updated");
    }

    /**
     * Uninstall the module.
     *
     * @return void
     */
    public function uninstall(): void
    {
        if ($this instanceof MigratableInterface) {
            $this->rollbackMigrations();
        }

        $this->logEvent('uninstalled', "Module {$this->moduleName} uninstalled");
    }

    // ========================================================================
    // Logging
    // ========================================================================

    /**
     * Log module event.
     *
     * @param string $event
     * @param string $message
     * @param string $level
     * @return void
     */
    protected function logEvent(string $event, string $message, string $level = 'info'): void
    {
        if (!config('ironflow.logging.enabled', true)) {
            return;
        }

        $logEvents = config('ironflow.logging.log_events', []);
        if (!($logEvents[$event] ?? false)) {
            return;
        }

        $channel = config('ironflow.logging.channel', 'stack');
        Log::channel($channel)->$level("[IronFlow] {$message}", [
            'module' => $this->moduleName,
            'event' => $event,
            'state' => $this->state->getCurrentState(),
        ]);
    }

    /**
     * Get seeder path.
     *
     * @return string
     */
    public function getSeederPath(): string
    {
        return $this->modulePath . '/Database/Seeders';
    }

    /**
     * Get seeders.
     *
     * @return array
     */
    public function getSeeders(): array
    {
        return [];
    }

    /**
     * Get seeder priority.
     *
     * @return int
     */
    public function getSeederPriority(): int
    {
        return 50;
    }

    /**
     * Seed the module.
     *
     * @param string|null $seederClass
     * @return void
     */
    public function seed(?string $seederClass = null): void
    {
        $namespace = config('ironflow.namespace', 'Modules');

        if ($seederClass) {
            $fullClass = "{$namespace}\\{$this->moduleName}\\Database\\Seeders\\{$seederClass}";

            if (class_exists($fullClass)) {
                Artisan::call('db:seed', ['--class' => $fullClass, '--force' => true]);
            }

            return;
        }

        $seeders = $this->getSeeders();

        foreach ($seeders as $seeder) {
            $fullClass = "{$namespace}\\{$this->moduleName}\\Database\\Seeders\\{$seeder}";

            if (class_exists($fullClass)) {
                Artisan::call('db:seed', ['--class' => $fullClass, '--force' => true]);
            }
        }
    }
}
