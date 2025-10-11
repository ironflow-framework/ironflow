# IronFlow Framework

<p align="start">
  <img src="https://img.shields.io/badge/version-2.0.0-blue" alt="Version 2.0.0">
  <img src="https://img.shields.io/badge/PHP-%3E%3D8.2-777bb3" alt="PHP >= 8.2">
  <img src="https://img.shields.io/badge/license-MIT-green" alt="License MIT">
</p>

> **A powerful, modular architecture framework for Laravel 12+**

IronFlow is a plug-and-play modular framework that provides complete module isolation, extensibility, and maintainability for Laravel applications. Think of it as Laravel Modules on steroids, with advanced features like service exposure, conflict detection, and lifecycle management.

---

## Philosophy

IronFlow follows these core principles:

- **Complete Isolation**: Each module is self-contained with its own routes, views, migrations, and services
- **Activable Interfaces**: Modules opt-in to features via interfaces (ViewableInterface, RoutableInterface, etc.)
- **Lifecycle Management**: Full control over module installation, enabling, disabling, and uninstallation
- **Service Exposure**: Controlled service sharing between modules (public or linked-only)
- **Dependency Resolution**: Automatic dependency resolution and boot ordering
- **Conflict Detection**: Prevents route, migration, view, and config conflicts
- **Packagist Ready**: Export modules as standalone packages with `ExportableInterface`

> "Structure, autonomy, and reusability — without losing the soul of Laravel."

---


## Requirements

- PHP 8.3 or higher
- Laravel 12.x

## Installation

### Install in Existing Laravel Project

```bash
composer require ironflow/ironflow
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=ironflow-config
php artisan vendor:publish --tag=ironflow-stubs
```

### Create New Project with Skeleton

```bash
composer create-project ironflow/skeleton my-app
cd my-app
php artisan serve
```
---

## Core Architecture

### BaseModule

All modules extend `BaseModule`, which provides:

- **Native Laravel Integration**: Extends `ServiceProvider` for seamless integration
- **Complete Lifecycle**: `install()`, `enable()`, `disable()`, `update()`, `uninstall()`
- **Interface Implementations**: Concrete implementations for all activable interfaces
- **Automatic Logging**: Tracks registered, booted, and failed states

### ModuleMetaData

Encapsulates module information:

```php
new ModuleMetaData([
    'name' => 'Blog',
    'version' => '1.0.0',
    'description' => 'Blog module',
    'authors' => [['name' => 'John Doe', 'email' => 'john@example.com']],
    'dependencies' => ['Core', 'Auth'],
    'required' => ['Core'],
    'enabled' => true,
    'priority' => 50, // Higher = boots first
    'provides' => ['PostService', 'CommentService'],
    'allowOverride' => false,
    'linkedModules' => ['Admin', 'Analytics'],
]);
```

### ModuleState

Manages module lifecycle with validated transitions:

- `registered` → `preloaded` → `booting` → `booted`
- `failed` (can transition from any state)
- `disabled` (reversible)

Tracks history, errors, and duration in each state.

### Anvil - The Orchestrator

Anvil is the core engine that:

- **Discovers** modules automatically from `modules/` directory
- **Resolves** dependencies and determines boot order
- **Detects** conflicts between modules
- **Exposes** services for inter-module communication
- **Manages** lifecycle operations (enable, disable, install, uninstall)

Access via facade:

```php
use IronFlow\Facades\Anvil;

Anvil::getModule('Blog');
Anvil::enable('Blog');
Anvil::getService('Blog', 'PostService', 'Admin');
```

---

## Activable Interfaces

Modules activate features by implementing interfaces:

### ViewableInterface

```php
implements ViewableInterface

public function getViewNamespace(): string
{
    return 'blog'; // Access views via blog::index
}

public function getViewPaths(): array
{
    return [$this->modulePath . '/Resources/views'];
}
```

### RoutableInterface

```php
implements RoutableInterface

public function getRouteFiles(): array
{
    return [
        'web' => $this->modulePath . '/Routes/web.php',
        'api' => $this->modulePath . '/Routes/api.php',
    ];
}

public function getRoutePrefix(): ?string
{
    return 'blog'; // Routes prefixed with /blog
}
```

### MigratableInterface

```php
implements MigratableInterface

public function getMigrationPath(): string
{
    return $this->modulePath . '/Database/Migrations';
}

public function getMigrationPrefix(): string
{
    return 'blog_'; // Prevents table name conflicts
}
```

### ConfigurableInterface

```php
implements ConfigurableInterface

public function getConfigPath(): string
{
    return $this->modulePath . '/config/blog.php';
}

public function getConfigKey(): string
{
    return 'blog'; // Access via config('blog.key')
}
```

### PublishableInterface

```php
implements PublishableInterface

public function getPublishableAssets(): array
{
    return [
        $this->modulePath . '/Resources/css' => public_path('vendor/blog/css'),
        $this->modulePath . '/Resources/js' => public_path('vendor/blog/js'),
    ];
}
```

### ExposableInterface

Control how your module shares services:

```php
implements ExposableInterface

public function expose(): array
{
    return [
        'public' => [
            'PostService' => app(PostService::class),
            'stats' => ['total' => 100],
        ],
        'linked' => [
            'Admin' => [
                'PostService' => app(PostService::class),
                'CommentService' => app(CommentService::class),
            ],
        ],
    ];
}

// Access exposed services from another module
$postService = Anvil::getService('Blog', 'PostService', 'Admin');
```

**Strict Mode**: When enabled (default), services are only accessible to linked modules.

### ExportableInterface

Prepare your module for Packagist:

```php
implements ExportableInterface

public function export(): array
{
    return [
        'files' => [$this->modulePath . '/Http', $this->modulePath . '/Models'],
        'assets' => [$this->modulePath . '/Resources'],
        'config' => [$this->getConfigPath()],
        'stubs' => [],
        'exclude' => ['*.log', 'node_modules'],
    ];
}

public function getPackageName(): string
{
    return 'vendor/blog-module';
}
```

---

## 🚀 Quick Start

### Performance Mode: Lazy Loading

IronFlow includes **automatic lazy loading** for optimal performance:

```php
// config/ironflow.php
'lazy_load' => [
    'enabled' => true, // Active par défaut
    'eager' => ['Core', 'Auth'], // Toujours chargés
    'lazy' => [], // Tous les autres sont lazy
],
```

**Gains typiques** :

- **60-70% faster** boot time
- **65-75% less** memory usage
- Modules chargés uniquement quand nécessaires

### Create a Module

```bash
php artisan ironflow:module:make Blog \
    --view \
    --route \
    --migration \
    --config \
    --asset \
    --model \
    --exposable
```

This generates:

```markdown
modules/Blog/
├── BlogModule.php
├── Http/Controllers/
├── Services/
├── Models/Post.php
├── Routes/
│   ├── web.php
│   └── api.php
├── Database/Migrations/
├── Resources/
│   ├── views/
│   ├── css/
│   └── js/
├── config/blog.php
└── README.md
```

### Basic Module Structure

```php
<?php

namespace Modules\Blog;

use IronFlow\Core\BaseModule;
use IronFlow\Core\ModuleMetaData;
use IronFlow\Contracts\ViewableInterface;
use IronFlow\Contracts\RoutableInterface;

class BlogModule extends BaseModule implements ViewableInterface, RoutableInterface
{
    protected function getModuleName(): string
    {
        return 'Blog';
    }

    protected function createMetadata(): ModuleMetaData
    {
        return new ModuleMetaData([
            'name' => 'Blog',
            'version' => '1.0.0',
            'description' => 'Blog module',
            'enabled' => true,
            'priority' => 50,
        ]);
    }

    protected function registerServices(): void
    {
        $this->app->singleton(PostService::class);
    }
}
```

### Module Management

```bash
# List all modules
php artisan ironflow:module:list

# Enable a module
php artisan ironflow:module:enable Blog

# Disable a module
php artisan ironflow:module:disable Blog

# Export module for Packagist
php artisan ironflow:module:publish Blog

# Lazy loading commands
php artisan ironflow:lazy:stats       # View statistics
php artisan ironflow:lazy:warmup      # Preload all modules
php artisan ironflow:lazy:test Blog   # Test lazy loading
php artisan ironflow:lazy:benchmark   # Benchmark performance
```

---

## Module Lifecycle

### 1. Registration Phase

```php
public function register(): void
{
    // State: registered → preloaded
    // Register services in the container
    $this->app->singleton(PostService::class);
}
```

### 2. Boot Phase

```php
public function boot(): void
{
    // State: preloaded → booting → booted
    // Register routes, views, migrations, etc.
    $this->registerViews();
    $this->registerRoutes();
}
```

### 3. Lifecycle Methods

```php
// Install module (run migrations, seed data)
$module->install();

// Enable module
$module->enable();

// Disable module
$module->disable();

// Update module
$module->update();

// Uninstall module (rollback migrations)
$module->uninstall();
```

---

## Inter-Module Communication

### Exposing Services

```php
// In BlogModule
public function expose(): array
{
    return [
        'public' => [
            'PostService' => app(PostService::class),
        ],
        'linked' => [
            'Admin' => [
                'PostService' => app(PostService::class),
                'moderation' => app(ModerationService::class),
            ],
        ],
    ];
}
```

### Consuming Services

```php
// From another module
use IronFlow\Facades\Anvil;

// Access public service
$postService = Anvil::getService('Blog', 'PostService');

// Access linked service (only if your module is linked)
$moderation = Anvil::getService('Blog', 'moderation', 'Admin');
```

### Linked Modules

Declare module relationships in metadata:

```php
protected function createMetadata(): ModuleMetaData
{
    return new ModuleMetaData([
        'name' => 'Admin',
        'linkedModules' => ['Blog', 'User', 'Analytics'],
    ]);
}
```

---

## ⚠️ Conflict Detection

IronFlow automatically detects:

### Route Conflicts

```php
// If two modules use same route prefix
Blog: /blog
Shop: /blog  // ⚠️ CONFLICT DETECTED
```

### Migration Conflicts

```php
// If two modules create same table
Blog: create_posts_table
Forum: create_posts_table  // ⚠️ CONFLICT DETECTED
```

**Solution**: Use migration prefixes

```php
public function getMigrationPrefix(): string
{
    return 'blog_'; // Tables: blog_posts, blog_comments
}
```

### View Namespace Conflicts

```php
// If two modules use same namespace
Blog: blog::index
News: blog::index  // ⚠️ CONFLICT DETECTED
```

### Config Key Conflicts

```php
// If two modules use same config key
config('blog.key')
config('blog.key')  // ⚠️ CONFLICT DETECTED
```

---

## Dependency Management

### Declaring Dependencies

```php
protected function createMetadata(): ModuleMetaData
{
    return new ModuleMetaData([
        'name' => 'Blog',
        'dependencies' => ['Core', 'Auth'], // Soft dependencies
        'required' => ['Core'], // Hard dependencies (must exist)
    ]);
}
```

### Boot Order

Modules boot in this order:

1. By dependencies (dependencies boot first)
2. By priority (higher priority = boots first)

```php
// Core priority: 100 (boots first)
// Auth priority: 90
// Blog priority: 50 (boots last)
```

### Circular Dependency Detection

```php
// Blog depends on Comment
// Comment depends on Blog
// ⚠️ CIRCULAR DEPENDENCY DETECTED
```

---

## Configuration

### config/ironflow.php

```php
return [
    // Module discovery path
    'path' => base_path('modules'),
    
    // Base namespace
    'namespace' => 'Modules',
    
    // Auto-discover modules
    'auto_discover' => true,
    
    // Module priorities (override)
    'priorities' => [
        'Core' => 100,
        'Auth' => 90,
    ],
    
    // Logging configuration
    'logging' => [
        'enabled' => true,
        'channel' => 'stack',
        'log_events' => [
            'registered' => true,
            'booted' => true,
            'failed' => true,
        ],
    ],
    
    // Conflict detection
    'conflict_detection' => [
        'enabled' => true,
        'routes' => true,
        'migrations' => true,
        'views' => true,
        'config' => true,
    ],
    
    // Service exposure
    'service_exposure' => [
        'strict_mode' => true, // Only linked modules can access
        'allow_public' => true, // Allow public services
    ],
    
    // Caching
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
    ],
];
```

---

## Advanced Usage

### Custom Boot Logic

```php
public function bootModule(): void
{
    // Register Blade directives
    Blade::directive('blogPost', function ($id) {
        return "<?php echo renderPost($id); ?>";
    });
    
    // Register event listeners
    Event::listen('post.created', PostCreatedListener::class);
    
    // Register view composers
    View::composer('blog::sidebar', function ($view) {
        $view->with('recent', Post::recent());
    });
}
```

### Conditional Service Exposure

```php
public function getExposedForModule(string $moduleName): array
{
    if ($moduleName === 'Admin') {
        return [
            'PostService' => app(PostService::class),
            'moderation' => app(ModerationService::class),
        ];
    }
    
    if ($moduleName === 'Analytics') {
        return [
            'stats' => app(AnalyticsService::class),
        ];
    }
    
    return [];
}
```

### Module Statistics

```php
$stats = Anvil::getStatistics();
// [
//     'total' => 10,
//     'enabled' => 8,
//     'disabled' => 1,
//     'failed' => 1,
//     'booted' => 8,
// ]
```

---

## Best Practices

### 1. Module Naming

- Use PascalCase: `Blog`, `UserManagement`, `PaymentGateway`
- Keep names descriptive and unique

### 2. Dependencies

- Minimize dependencies for better modularity
- Use `required` only for critical dependencies
- Document all dependencies in README

### 3. Service Exposure

- Expose only what's necessary
- Use `linked` over `public` when possible
- Document exposed services

### 4. Migrations

- Always use migration prefixes
- Keep migrations module-specific
- Never depend on other module's tables

### 5. Testing

```php
// Test module registration
$this->assertTrue(Anvil::hasModule('Blog'));

// Test service exposure
$service = Anvil::getService('Blog', 'PostService');
$this->assertInstanceOf(PostService::class, $service);

// Test state transitions
$module->enable();
$this->assertTrue($module->getState()->isBooted());
```

---

## Troubleshooting

### Module Not Found

```bash
# Clear cache
php artisan ironflow:clear

# Check module path
config('ironflow.path') // Should point to modules/
```

### Circular Dependency Error

```php
// Check dependencies
$deps = Anvil::getDependencies('Blog');
```

### Service Not Accessible

```php
// Check if module exposes service
Anvil::hasService('Blog', 'PostService'); // true/false

// Check if your module is linked
$metadata->isLinkedWith('Admin'); // true/false
```

---

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security vulnerabilities, please email `ironflow.framework@gmail.com`.

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

## Credits

Built with ❤️ by Aure Dulvresse
