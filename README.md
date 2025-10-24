<p align="center">
  <img src="./ironflow-logo.png" alt="IronFlow Logo" width="200">
</p>

<p align="center">
  <strong>A Powerful Modular Framework for Laravel 12+</strong>
</p>

 <p align="center">
  <a href="https://packagist.org/packages/ironflow/ironflow">
    <img src="https://img.shields.io/packagist/v/ironflow/ironflow" alt="Latest Version" />
  </a>
  <a href="https://packagist.org/packages/ironflow/ironflow">
    <img src="https://img.shields.io/packagist/dt/ironflow/ironflow" alt="Total Downloads" />
  </a>
  <a href="https://packagist.org/packages/ironflow/ironflow">
    <img src="https://img.shields.io/packagist/l/ironflow/ironflow" alt="License" />
  </a>
 </p>

---

## About IronFlow

IronFlow is a complete rewrite of the popular modular framework for Laravel. It enables you to build highly modular, maintainable, and scalable applications by organizing your code into isolated, self-contained modules.

### Why IronFlow?

- **True Modularity**: Each module is completely isolated with its own routes, views, migrations, and services
- **Service Exposure**: Modules can expose services to other modules with fine-grained access control
- **Smart Lazy Loading**: Hybrid lazy loading optimizes boot time and memory usage
- **Dependency Management**: Automatic dependency resolution with circular dependency detection
- **Manifest Caching**: Lightning-fast module discovery in production
- **Testing Framework**: Comprehensive `ModuleTestCase` for testing your modules
- **Laravel 12+ Ready**: Built for modern Laravel with PHP 8.2+ features

---

## Requirements

- **PHP**: 8.2 or higher
- **Laravel**: 12.0 or higher
- **Composer**: 2.0 or higher

## Installation

### Existing Laravel Project

```bash
composer require ironflow/ironflow
php artisan ironflow:install
```

### New Project

```bash
composer create-project ironflow/skeleton my-project
cd my-project
php artisan ironflow:install
```

---

## Quick Start

### 1. Create Your First Module

```bash
php artisan ironflow:module:make Blog
```

This generates a complete module structure:

```markdown
├── BlogModule.php # Main module class
├── BlogServiceProvider.php # Laravel service provider (optional)
├── Http/Controllers/ # Controllers
├── Models/ # Eloquent models
├── Services/ # Business logic
├── Resources/views/ # Blade templates
├── Database/Migrations/ # Database migrations
├── routes/web.php # Routes
└── config/blog.php # Configuration
```

### 2. Define Your Module

```php
namespace Modules\Blog;

use IronFlow\Core\{BaseModule, ModuleMetaData};
use IronFlow\Contracts\{
  ViewableInterface,
  RoutableInterface,
  MigratableInterface
};

class BlogModule extends BaseModule implements
  ViewableInterface,
  RoutableInterface,
  MigratableInterface
{
  protected function defineMetadata(): ModuleMetaData
  {
      return new ModuleMetaData(
          name: 'Blog',
          version: '1.0.0',
          description: 'Complete blog with posts and comments',
          author: 'Your Name',
          dependencies: [
              'Users' => '^1.0',  // Semver constraints
          ],
          provides: ['BlogService', 'PostRepository'],
          path: __DIR__,
          namespace: __NAMESPACE__,
      );
  }
  public function expose(): array
  {
      return [
          'blog' => Services\BlogService::class,
          'posts' => Services\PostRepository::class,
      ];
  }
  // Interface implementations...
}
```

### 3. Discover and Boot Modules

```bash
# Discover all modules
php artisan ironflow:discover

# List modules
php artisan ironflow:list --detailed

# Cache for production
php artisan ironflow:cache
```

### 4. Use Exposed Services

```php
use IronFlow\Facades\Anvil;

// Resolve a service from any module
$blogService = Anvil::getService('blog.blog');
$posts = $blogService->getPublishedPosts();

// In controllers via dependency injection
class DashboardController extends Controller
{
    public function __construct(
        private BlogService $blogService
    ) {}

    public function index()
    {
        return view('dashboard', [
            'posts' => $this->blogService->getPublishedPosts()
        ]);
    }
}
```

---

## Core Features

### 1. Module Lifecycle Management

Complete lifecycle with automatic state management:

```markdown
UNREGISTERED → REGISTERED → PRELOADED → BOOTING → BOOTED
```

Each state is tracked, validated, and can trigger custom logic.

### 2. Service Exposure & Resolution

**Public Services** (accessible by all modules):

```php
public function expose(): array
{
   return [
       'blog' => Services\BlogService::class,
   ];
}
// Usage in any module
$blogService = Anvil::getService('blog.blog');
```

**Linked Services** (restricted access):

```php
public function exposeLinked(): array
{
   return [
       'admin' => [
           'class' => Services\AdminService::class,
           'allowed' => ['Dashboard', 'Settings'],
       ],
   ];
}
```

### 3. Smart Lazy Loading

Hybrid loading strategy for optimal performance:

```php
'lazy_loading' => [
   'enabled' => true,
   'eager' => ['routes', 'views', 'config'],  // Load immediately
   'lazy' => ['services', 'events', 'commands'], // Load on demand
]
```

### 4. Semantic Versioning

Full semver support with constraint validation:

- `^1.2.3` - Caret : `>=1.2.3 <2.0.0`
- `~1.2.3` - Tilde : `>=1.2.3 <1.3.0`
- `>=1.0.0` - Superior
- `<2.0.0` - Inferior
- `1.2.3` - Exact version
- `*` - All version
- `^1.0 || ^2.0` - logic OR
- `>=1.0 <2.0` - logic AND

#### Bumper une Version

```bash
# Patch (1.2.3 → 1.2.4)
php artisan ironflow:version:bump Blog patch

# Minor (1.2.3 → 1.3.0)
php artisan ironflow:version:bump Blog minor

# Major (1.2.3 → 2.0.0)
php artisan ironflow:version:bump Blog major

# With commit et tag
php artisan ironflow:version:bump Blog minor --commit --tag
```

---

## Running Tests

IronFlow uses Pest for an elegant testing experience.

```bash
# Run all tests
composer test

# Run with coverage
composer test:coverage

# Run in parallel
composer test:parallel

# Run specific test file
./vendor/bin/pest tests/Unit/ModuleMetaDataTest.php

# Run tests with filter
./vendor/bin/pest --filter="permissions"

# Run tests in watch mode
./vendor/bin/pest --watch
```

## Writing Tests

```php
use IronFlow\Testing\ModuleTestCase;

test('my module feature works', function () {
    $module = createTestModule('MyModule');

    expect($module->getName())->toBe('MyModule')
        ->and($module->getState())->toBe(ModuleState::UNREGISTERED);
});
```

## Custom Expectations

```php
expect($module)->toBeBooted();
expect($module)->toHaveState(ModuleState::BOOTED);
```

---

## Interfaces

IronFlow provides several activatable interfaces:

### ViewableInterface

Register and manage Blade views:

```php
implements ViewableInterface
```

### RoutableInterface

Register web/API routes:

```php
implements RoutableInterface
```

### MigratableInterface

Manage database migrations:

```php
implements MigratableInterface
```

### ConfigurableInterface

Handle module configuration:

```php
implements ConfigurableInterface
```

### SeedableInterface

Database seeding support:

```php
implements SeedableInterface
```

### ExposableInterface

Expose services to other modules:

```php
implements ExposableInterface
```

### ExportableInterface

Export module:

```php
implements ExportableInterface
```

---

## Artisan Commands

| Command                                 | Description                          |
| --------------------------------------- | ------------------------------------ |
| `ironflow:install`                      | Install IronFlow in your project     |
| `ironflow:discover`                     | Discover all modules                 |
| `ironflow:cache`                        | Cache module manifest for production |
| `ironflow:clear`                        | Clear module cache                   |
| `ironflow:module:make {name}`           | Create a new module                  |
| `ironflow:list [--detailed]`            | List all registered modules          |
| `ironflow:permissions:activate`         | Active permission system             |
| `ironflow:permissions:sync`             | Sync permissions to database         |
| `ironflow:publish {module}`             | Prepare for publishing               |
| `ironflow:version:bump {module} {type}` | Bump version (major/minor/patch)     |

---

## Available Packages

### Official Packages

| Package             | Description     | Status |
| ------------------- | --------------- | ------ |
| `ironflow/ironflow` | Core framework  | Stable |
| `ironflow/admin`    | Admin dashboard | Stable |

---

<!-- ## Documentation

Full documentation is available at [docs.ironflow.dev](https://docs.ironflow.dev) -->

<!-- ### Quick Links

- [Installation Guide](https://docs.ironflow.dev/installation)
- [Creating Modules](https://docs.ironflow.dev/creating-modules)
- [Service Exposure](https://docs.ironflow.dev/services)
- [Testing Guide](https://docs.ironflow.dev/testing)
- [API Reference](https://docs.ironflow.dev/api) -->

---

## Example: Blog Module

Complete working example included in the repository:

```php
namespace Modules\Blog;

use IronFlow\Core\{BaseModule, ModuleMetaData};
use IronFlow\Contracts\{
  ViewableInterface,
  RoutableInterface,
  MigratableInterface,
  ConfigurableInterface
}

class BlogModule extends BaseModule implements
    ViewableInterface,
    RoutableInterface,
    MigratableInterface,
    ConfigurableInterface
{
    // Define metadata
    protected function defineMetadata(): ModuleMetaData
    {
        return new ModuleMetaData(
            name: 'Blog',
            version: '1.0.0',
            description: 'Complete blog with posts and comments',
            author: 'IronFlow Team',
            dependencies: [],
            provides: ['BlogService', 'PostRepository'],
            path: __DIR__,
            namespace: __NAMESPACE__,
        );
    }

    // Register services
    public function register(): void
    {
        $this->app->singleton(Services\BlogService::class);
        $this->app->bind(Services\PostRepository::class);
    }

    // Boot module
    public function bootModule(): void
    {
        $this->loadTranslations();
    }

    // Expose services
    public function expose(): array
    {
        return [
            'blog' => Services\BlogService::class,
            'post-repository' => Services\PostRepository::class,
        ];
    }

    // Implement interfaces...
}
```

Features:

- ✅ Complete CRUD for posts
- ✅ Comment system
- ✅ Categories and tags
- ✅ SEO-friendly URLs
- ✅ View counters
- ✅ Blade templates
- ✅ Migrations and seeders
- ✅ Full test coverage

---

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

---

## Security

If you discover any security-related issues, please email `ironflow.framework@gmail.com` instead of using the issue tracker.

---

## License

IronFlow is open-sourced software licensed under the [MIT license](LICENSE.md).

---

## Credits

Built with love by the Aure Dulvresse and amazing contributors.

- **Contributors**: [All Contributors](https://github.com/ironflow-framework/ironflow/graphs/contributors)

Special thanks to:

- Laravel community for inspiration
- All our contributors and sponsors
- Open source libraries we depend on

---

## Links

<!-- - **Website**: [ironflow.dev](https://ironflow.dev) -->
<!-- - **Documentation**: [docs.ironflow.dev](https://docs.ironflow.dev) -->

- **GitHub**: [github.com/ironflow/ironflow](https://github.com/ironflow-framework/ironflow)
- **Discord**: [discord.gg/ironflow](https://discord.gg/9Vy9Tz94j9)
<!-- - **Twitter**: [@ironflowphp](https://twitter.com/ironflowphp) -->
- **Packagist**: [packagist.org/packages/ironflow/ironflow](https://packagist.org/packages/ironflow/ironflow)

---

## Sponsors

Support IronFlow development:

<p align="center">
  <a href="https://github.com/sponsors/ironflow-framework">
    <img src="https://img.shields.io/badge/Sponsor-IronFlow-EA4AAA?style=for-the-badge&logo=GitHubSponsors&logoColor=white" alt="Sponsor">
  </a>
</p>

---

<p align="center">
  <strong>Build Better Modular Applications</strong>
  Made with ❤️ by Aure Dulvresse
 </p
