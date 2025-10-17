# IronFlow Framework

<p align="start">
  <img src="https://img.shields.io/badge/version-3.0.0-blue.svg" alt="Version">
  <img src="https://img.shields.io/badge/laravel-12%2B-red.svg" alt="Laravel">
  <img src="https://img.shields.io/badge/php-8.2%2B-purple.svg" alt="PHP">
  <img src="https://img.shields.io/badge/license-MIT-green.svg" alt="License">
</p>

> **A powerful, modular architecture framework for Laravel 12+**

IronFlow is a plug-and-play modular framework that provides complete module isolation, extensibility, and maintainability for Laravel applications. Think of it as Laravel Modules on steroids, with advanced features like lazy loading, service exposure, conflict detection, and lifecycle management.

---

## Table des Matières

- [Features](#features)
- [Quick Start](#quick-start)
  - [Installation](#installation)
  - [Create Your First Module](#create-your-first-module)
- [Core Concepts](#core-concepts)
  - [BaseModule](#basemodule)
  - [Anvil - The Orchestrator](#anvil---the-orchestrator)
  - [ModuleMetaData](#modulemetadata)
  - [ModuleState](#modulestate)
  - [Activable Interfaces](#activable-interfaces)
- [Performance](#performance)
  - [Lazy Loading](#lazy-loading)
  - [Benchmarks](#benchmarks)
- [Advanced Features](#advanced-features)
  - [Event System](#event-system)
  - [Permissions](#permissions)
- [Contributing](#-contributing)
- [License](#-license)

---

## Features

### Core Architecture

- ✅ **Complete Module Isolation** - Self-contained modules with own routes, views, migrations
- ✅ **Activable Interfaces** - Opt-in features via interfaces (ViewableInterface, RoutableInterface, etc.)
- ✅ **Lifecycle Management** - Full control: install(), enable(), disable(), update(), uninstall()
- ✅ **Service Exposure** - Controlled service sharing (public or linked-only)
- ✅ **Dependency Resolution** - Automatic with circular dependency detection
- ✅ **Conflict Detection** - Prevents route, migration, view, and config conflicts
- ✅ **Packagist Ready** - Export modules as standalone packages

### Performance

- **Lazy Loading** - 60-70% faster boot time, 65-75% less memory
- **Smart Preloading** - Route patterns, time-based, role-based
- **Selective Loading** - Load only what you need, when you need it

### Developer Experience

- **Hot-Reload** - Dev mode without server restart
- **Testing Utilities** - Complete ModuleTestCase with 15+ assertions
- **CLI Generator** - Create modules in seconds
- **Auto-Documentation** - Self-documenting architecture

### Advances Features

- **Permissions System** - Role-based access control per module
- **Event Bus** - Dedicated inter-module communication

---

## Quick Start

### Installation

```bash
composer require ironflow/ironflow
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=ironflow-config
php artisan vendor:publish --tag=ironflow-stubs
```

### Create Your First Module

```bash
php artisan ironflow:module:make Blog \
    --view \
    --route \
    --migration \
    --config \
    --asset \
    --model
```

This generates:

```diagram
modules/Blog/
├── BlogModule.php
├── Http/Controllers/
│   └── BlogController.php
├── Models/
│   └── Post.php
├── Routes/
│   ├── web.php
│   └── api.php
├── Database/Migrations/
│   └── 2025_01_01_create_posts_table.php
├── Resources/
│   ├── views/
│   │   └── index.blade.php
│   ├── css/
│   └── js/
├── config/blog.php
└── README.md
```

### Verify Installation

```bash
# Check routes are registered
php artisan route:list | grep blog

# Check module status
php artisan ironflow:module:list

# View statistics
php artisan ironflow:lazy:stats
```

---

## Core Concepts

### BaseModule

Base class for all IronFlow modules. **No longer extends ServiceProvider** - it's a pure structural contract.

```php
<?php

namespace Modules\Blog;

use IronFlow\Core\BaseModule;
use IronFlow\Core\ModuleMetaData;
use IronFlow\Contracts\ViewableInterface;
use IronFlow\Contracts\RoutableInterface;

class BlogModule extends BaseModule implements 
    ViewableInterface,
    RoutableInterface
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

    public function register($app): void
    {
        // Bind services to container
        $app->singleton(PostService::class, function($app) {
            return new PostService();
        });
    }

    // Views and routes are auto-loaded by Anvil
    // No need for loadViewsFrom() or loadRoutesFrom()
}
```

[↑ Back to top](#ironflow-framework)

### Anvil - The Orchestrator

Anvil is the core engine that:

- **Discovers** modules automatically
- **Registers** their services
- **Boots** them in dependency order
- **Manages** lifecycle operations
- **Exposes** services for inter-module communication

```php
use IronFlow\Facades\Anvil;

// Get a module
$module = Anvil::getModule('Blog');

// Enable/disable
Anvil::enable('Blog');
Anvil::disable('Blog');

// Get exposed service
$postService = Anvil::getService('Blog', 'PostService');

// Statistics
$stats = Anvil::getStatistics();
```

**Architecture:**

```diagram
IronFlowServiceProvider
    ↓
Anvil::discover()      → Scan modules/ directory
    ↓
Anvil::registerAll()   → Call module->register($app)
    ↓
Anvil::bootAll()       → Load resources + call module->boot($app)
    ↓
Module Booted ✅
```

[↑ Back to top](#ironflow-framework)

### ModuleMetaData

Encapsulates all module information:

```php
new ModuleMetaData([
    'name' => 'Blog',
    'version' => '2.1.0',
    'description' => 'Blog module with posts and comments',
    'authors' => [
        ['name' => 'John Doe', 'email' => 'john@example.com']
    ],
    'dependencies' => ['Auth', 'Settings'],
    'required' => ['Auth'], // Must be present
    'enabled' => true,
    'priority' => 60, // Higher = boots first
    'provides' => ['PostService', 'CommentService'],
    'allowOverride' => false,
    'linkedModules' => ['Admin', 'Analytics'],
]);
```

[↑ Back to top](#ironflow-framework)

### ModuleState

State machine with validation:

```diagram
registered → preloaded → booting → booted
                            ↓
                         failed
                            ↓
                        disabled
```

```php
$state = $module->getState();

$state->isBooted();      // true/false
$state->isFailed();      // true/false
$state->getHistory();    // Array of state transitions
$state->getLastError();  // Error details if failed
```

[↑ Back to top](#ironflow-framework)

### Activable Interfaces

Modules declare capabilities via interfaces:

| Interface | Purpose | Auto-loaded by Anvil |
|-----------|---------|----------------------|
| `ViewableInterface` | Views with namespace | ✅ `loadViewsFrom()` |
| `RoutableInterface` | Routes with prefix | ✅ `Route::group()` |
| `MigratableInterface` | Database migrations | ✅ `loadMigrationsFrom()` |
| `ConfigurableInterface` | Configuration | ✅ `mergeConfigFrom()` |
| `TranslatableInterface` | Translations | ✅ `loadTranslationsFrom()` |
| `PublishableInterface` | Assets/config publishing | ✅ `publishes()` |
| `BootableInterface` | Custom boot logic | Calls `bootModule()` |
| `ExposableInterface` | Service exposure | Registers with ServiceExposer |
| `SeedableInterface` | Database seeding | Used by seed command |
| `PermissionableInterface` | Permissions | Registers with PermissionSystem |

**Example:**

```php
class MyModule extends BaseModule implements 
    ViewableInterface,
    RoutableInterface,
    ConfigurableInterface
{
    // That's it! Resources are auto-loaded
    // Views from: Resources/views (namespace: mymodule)
    // Routes from: Routes/web.php and Routes/api.php  
    // Config from: config/mymodule.php (key: mymodule)
}
```

[↑ Back to top](#ironflow-framework)

---

## Performance

### Lazy Loading

IronFlow includes automatic lazy loading for optimal performance:

```php
// config/ironflow.php
'lazy_load' => [
    'enabled' => true,
    'eager' => ['Core', 'Auth', 'Settings'], // Always loaded
    'strategies' => [
        'route' => true,    // Load on route match
        'service' => true,  // Load on service access
        'event' => true,    // Load on event trigger
        'command' => true,  // Load on artisan command
    ],
    'preload' => [
        'routes' => [
            '#^admin/#' => ['Admin', 'Dashboard'],
            '#^api/#' => ['Api'],
        ],
        'roles' => [
            'admin' => ['Admin', 'Analytics'],
            'user' => ['Blog'],
        ],
    ],
],
```

**Commands:**

```bash
php artisan ironflow:lazy:stats       # View statistics
php artisan ironflow:lazy:warmup      # Preload all modules
php artisan ironflow:lazy:test Blog   # Test lazy loading
php artisan ironflow:lazy:benchmark   # Compare eager vs lazy
```

[↑ Back to top](#ironflow-framework)

### Benchmarks

| Scenario | Without Lazy | With Lazy | Improvement |
|----------|-------------|-----------|-------------|
| **Small App (5-10 modules)** | 80-120ms | 25-40ms | **70% faster** |
| **Medium App (15-30 modules)** | 150-250ms | 40-80ms | **65% faster** |
| **Large App (50+ modules)** | 400-600ms | 80-150ms | **70% faster** |
| **Memory** | 80-120MB | 20-35MB | **75% less** |

[↑ Back to top](#ironflow-framework)

---

## Advanced Features

### Event System

Dedicated event bus for inter-module communication:

```php
use IronFlow\Facades\EventBus;

// Dispatch event
EventBus::dispatch('Blog', 'post.created', [
    'post_id' => $post->id,
    'title' => $post->title,
]);

// Listen to event
EventBus::listen('Blog', 'post.created', function($event) {
    $postId = $event->getData('post_id');
    // Handle event
}, priority: 10);

// Subscribe to multiple events
EventBus::subscribe('Admin', [
    'Blog' => ['post.created', 'post.updated'],
    'Shop' => ['order.created'],
]);
```

**Features:**

- Async support via queue
- Priority-based listeners
- Event history & debug mode
- Statistics tracking

```bash
php artisan ironflow:events:stats --history=20
```

[↑ Back to top](#ironflow-framework)

### Permissions

Role-based access control per module:

```php
use IronFlow\Permissions\PermissionableInterface;

class BlogModule extends BaseModule implements PermissionableInterface
{
    public function getPermissions(): array
    {
        return [
            'view' => ['*'],                    // Public
            'create' => ['user', 'editor'],     // Restricted
            'edit' => ['editor', 'admin'],
            'delete' => ['admin'],
        ];
    }
}
```

**Usage:**

```php
use IronFlow\Permissions\ModulePermissionSystem;

$permissions = app(ModulePermissionSystem::class);

// Check permission
if ($permissions->check('Blog', 'edit', auth()->user())) {
    // User can edit
}

// Middleware
Route::middleware('module.permission:Blog,edit')->group(function () {
    // Protected routes
});
```

**Commands:**

```bash
php artisan ironflow:permissions              # View all
php artisan ironflow:permissions --module=Blog
php artisan ironflow:permissions --role=admin
```

[↑ Back to top](#ironflow-framework)

---

## Development Tools

### Hot-Reload

Reload modules without restarting server:

```bash
php artisan ironflow:hot-reload:watch
php artisan ironflow:hot-reload:stats
```

**Configuration:**

```php
'hot_reload' => [
    'enabled' => env('IRONFLOW_HOT_RELOAD', app()->environment('local')),
    'watch_paths' => [
        'ModuleClass.php',
        'Routes/*.php',
        'Http/Controllers/*.php',
    ],
],
```

### Testing Utilities

Complete test suite for modules:

```php
use IronFlow\Testing\ModuleTestCase;

class BlogModuleTest extends ModuleTestCase
{
    protected string $moduleName = 'Blog';
    
    public function test_module_boots()
    {
        $module = $this->bootModule();
        
        $this->assertModuleBooted('Blog');
        $this->assertRouteExists('blog.index');
        $this->assertViewExists('blog::index');
        $this->assertServiceExposed('Blog', 'PostService');
    }
}
```

**Available Assertions:**

- `assertModuleExists/Registered/Booted/Enabled`
- `assertRouteExists/Prefix`
- `assertViewExists/NamespaceExists`
- `assertServiceExposed/Accessible`
- `assertModuleHasDependency`
- `assertNoConflicts`

[↑ Back to top](#ironflow-framework)

---

## Creating Modules

### Full Example

```php
<?php

namespace Modules\Blog;

use IronFlow\Core\BaseModule;
use IronFlow\Core\ModuleMetaData;
use IronFlow\Contracts\ViewableInterface;
use IronFlow\Contracts\RoutableInterface;
use IronFlow\Contracts\MigratableInterface;
use IronFlow\Contracts\ConfigurableInterface;
use IronFlow\Contracts\BootableInterface;
use IronFlow\Contracts\ExposableInterface;

class BlogModule extends BaseModule implements
    ViewableInterface,
    RoutableInterface,
    MigratableInterface,
    ConfigurableInterface,
    BootableInterface,
    ExposableInterface
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
            'description' => 'Blog module with posts and comments',
            'dependencies' => ['Auth'],
            'enabled' => true,
            'priority' => 60,
        ]);
    }

    public function register($app): void
    {
        // Register services
        $app->singleton(PostService::class, function($app) {
            return new PostService();
        });
    }

    public function bootModule(): void
    {
        // Custom boot logic
        \Blade::directive('blogPost', function ($id) {
            return "<?php echo renderPost($id); ?>";
        });

        \Event::listen('post.created', PostCreatedListener::class);
    }

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

    // All interface methods use BaseModule defaults
    // Override only if you need custom behavior
}
```

### Directory Structure

```diagram
modules/Blog/
├── BlogModule.php
├── Http/
│   ├── Controllers/
│   │   ├── PostController.php
│   │   └── CommentController.php
│   └── Middleware/
├── Models/
│   ├── Post.php
│   └── Comment.php
├── Services/
│   ├── PostService.php
│   └── CommentService.php
├── Routes/
│   ├── web.php
│   └── api.php
├── Database/
│   ├── Migrations/
│   │   └── 2025_01_01_create_posts_table.php
│   └── Seeders/
│       └── PostsSeeder.php
├── Resources/
│   ├── views/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   ├── lang/
│   │   └── en/
│   ├── css/
│   │   └── app.css
│   └── js/
│       └── app.js
├── config/
│   └── blog.php
└── README.md
```

[↑ Back to top](#ironflow-framework)

---

## Configuration

### Main Configuration

```php
// config/ironflow.php
return [
    // Module discovery path
    'path' => base_path('modules'),
    
    // Base namespace
    'namespace' => 'Modules',
    
    // Auto-discover modules
    'auto_discover' => true,
    
    // Lazy loading
    'lazy_load' => [
        'enabled' => true,
        'eager' => ['Core', 'Auth'],
        'strategies' => ['route' => true, 'service' => true],
    ],
    
    // Conflict detection
    'conflict_detection' => [
        'enabled' => true,
        'routes' => true,
        'migrations' => true,
        'views' => true,
        'config' => true,
    ],
    
    // Logging
    'logging' => [
        'enabled' => true,
        'channel' => 'stack',
        'log_events' => [
            'registered' => true,
            'booted' => true,
            'failed' => true,
        ],
    ],
    
    // Service exposure
    'service_exposure' => [
        'strict_mode' => true,
        'allow_public' => true,
    ],
];
```

[↑ Back to top](#ironflow-framework)

---

## Testing

### Unit Tests

```php
use IronFlow\Testing\ModuleTestCase;

class BlogModuleTest extends ModuleTestCase
{
    protected string $moduleName = 'Blog';
    
    public function test_module_structure()
    {
        $this->assertModuleExists('Blog');
        $this->assertModuleEnabled('Blog');
    }
    
    public function test_routes_registered()
    {
        $this->bootModule();
        
        $this->assertRouteExists('blog.index');
        $this->assertRoutePrefix('blog');
    }
    
    public function test_services_exposed()
    {
        $this->bootModule();
        
        $this->assertServiceExposed('Blog', 'PostService');
        $this->assertServiceAccessible('Blog', 'PostService');
    }
}
```

### Integration Tests

```php
public function test_inter_module_communication()
{
    $this->bootModule('Blog');
    $this->bootModule('Admin');
    
    $postService = Anvil::getService('Blog', 'PostService', 'Admin');
    $post = $postService->create(['title' => 'Test']);
    
    $this->assertDatabaseHas('posts', ['title' => 'Test']);
}
```

[↑ Back to top](#ironflow-framework)

---

## API Reference

### Anvil

```php
// Module management
Anvil::discover();                    // Discover modules
Anvil::registerAll();                 // Register all modules
Anvil::bootAll();                     // Boot all modules
Anvil::getModule('Blog');             // Get module instance
Anvil::hasModule('Blog');             // Check if exists
Anvil::getModules();                  // Get all modules

// Lifecycle
Anvil::enable('Blog');                // Enable module
Anvil::disable('Blog');               // Disable module
Anvil::install('Blog');               // Install (run migrations)
Anvil::uninstall('Blog');             // Uninstall

// Services
Anvil::getService('Blog', 'PostService');              // Public
Anvil::getService('Blog', 'PostService', 'Admin');     // Linked
Anvil::hasService('Blog', 'PostService');              // Check

// Dependencies
Anvil::getDependencies('Blog');       // Get dependencies
Anvil::getDependents('Blog');         // Get dependents

// Statistics
Anvil::getStatistics();               // Module stats
Anvil::clearCache();                  // Clear cache
```

### EventBus

```php
EventBus::dispatch('Blog', 'post.created', $data);
EventBus::dispatch('Blog', 'post.created', $data, async: true);
EventBus::listen('Blog', 'post.created', $callback, priority: 10);
EventBus::subscribe('Admin', ['Blog' => ['post.created']]);
EventBus::forget('Blog', 'post.created');
EventBus::getListeners('Blog', 'post.created');
EventBus::getHistory(10);
EventBus::getStatistics();
```

### ModulePermissionSystem

```php
$permissions->check('Blog', 'edit', $user);
$permissions->grant('Blog', 'moderate', 'moderator');
$permissions->revoke('Blog', 'moderate', 'moderator');
$permissions->getModulePermissions('Blog');
$permissions->getPermissionsByRole('admin');
$permissions->export();
```

[↑ Back to top](#ironflow-framework)

---

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Setup

```bash
git clone https://github.com/ironflow/ironflow.git
cd ironflow
composer install
composer test
```

### Running Tests

```bash
composer test
composer test:unit
composer test:integration
composer test:coverage
```

[↑ Back to top](#ironflow-framework)

---

## 📄 License

IronFlow is open-sourced software licensed under the [MIT license](LICENSE.md).

---

## 🙏 Credits

Built with ❤️ by the [IronFlow Team](https://github.com/ironflow)

Inspired by Laravel Modules but designed for enterprise-grade modular applications.

---

## 📞 Support

- **Documentation**: [https://ironflow.dev/docs](https://ironflow.dev/docs)
- **Issues**: [GitHub Issues](https://github.com/ironflow/ironflow/issues)
- **Discussions**: [GitHub Discussions](https://github.com/ironflow/ironflow/discussions)
- **Twitter**: [@ironflow_dev](https://twitter.com/ironflow_dev)

---

<p align="center">
  <strong>IronFlow - Build modular Laravel applications at scale</strong>
</p>

<p align="center">
  <a href="#-quick-start">Quick Start</a> •
  <a href="#-core-concepts">Core Concepts</a> •
  <a href="#-advanced-features">Advanced Features</a> •
  <a href="#-api-reference">API Reference</a>
</p>

[↑ Back to top](#ironflow-framework)
