# IronFlow Framework v1.0.0

<p align="center">
  <img src="https://github.com/ironflow-framework/ironflow/workflows/tests/badge.svg" alt="Tests">
  <img src="https://img.shields.io/badge/version-1.0.0-blue" alt="Version 1.0.0">
  <img src="https://img.shields.io/badge/PHP-%3E%3D8.2-777bb3" alt="PHP >= 8.2">
  <img src="https://img.shields.io/badge/license-MIT-green" alt="License MIT">
</p>

**IronFlow** is a modular architecture framework for Laravel 12 that enables you to build scalable, maintainable applications with isolated, reusable modules.

> "Structure, autonomy, and reusability — without losing the soul of Laravel."

## Features

- **True Module System** - Isolated feature domains with complete autonomy
- **Advanced Dependency Resolution** - Automatic dependency management with circular dependency detection
- **Module Lifecycle** - Register → Preload → Boot phases
- **Laravel 12 Native** - Built on Laravel's foundation, not against it
- **Powerful CLI** - Extended Artisan commands for module management
- **Testing Ready** - Full PHPUnit/Pest support
- **Publishable Modules** - Export modules as Composer packages

## Requirements

- PHP 8.2 or higher
- Laravel 12.x

## Installation

### Install in Existing Laravel Project

```bash
composer require ironflow/ironflow
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=ironflow-config
```

### Create New Project with Skeleton

```bash
composer create-project ironflow/skeleton my-app
cd my-app
php artisan serve
```

## Quick Start

### Create Your First Module

```bash
php artisan ironflow:module:create Blog --description="Blog management module"
```

This creates a complete module structure:

```markdown
app/Modules/Blog/
├── Http/Controllers/
│   └── BlogController.php
├── Models/
│   └── Blog.php
├── Services/
├── Routes/
│   ├── web.php
│   └── api.php
├── Database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── Resources/
│   └── views/
├── Providers/
│   └── BlogServiceProvider.php
└── BlogModule.php
```

### Define Your Module

```php
<?php

namespace App\Modules\Blog;

use IronFlow\Core\BaseModule;
use IronFlow\Core\ModuleMetadata;
use IronFlow\Contracts\RoutableInterface;

class BlogModule extends BaseModule implements RoutableInterface
{
    public function metadata(): ModuleMetadata
    {
        return new ModuleMetadata(
            name: 'Blog',
            version: '1.0.0',
            description: 'Blog management system',
            authors: ['Your Name'],
            dependencies: ['User'], // Depends on User module
            enabled: true,
        );
    }

    public function register(): void
    {
        parent::register();
        
        // Register services
        $this->app()->singleton(BlogService::class);
    }

    public function boot(): void
    {
        parent::boot();
        
        // Boot module
    }

    public function registerRoutes(): void
    {
        $this->loadRoutesFrom($this->path('Routes/web.php'));
        $this->loadApiRoutesFrom($this->path('Routes/api.php'));
    }

    public function routePrefix(): ?string
    {
        return 'blog';
    }

    public function routeMiddleware(): array
    {
        return ['web', 'auth'];
    }
}
```

### Discover and Register Modules

```bash
php artisan ironflow:discover
```

This will scan your `app/Modules` directory and register all modules.

## CLI Commands

### Module Management

```bash
# Create a new module
php artisan ironflow:module:create {name}

# List all modules
php artisan ironflow:list

# Show module info
php artisan ironflow:info {module}

# Enable/disable modules
php artisan ironflow:enable {module}
php artisan ironflow:disable {module}

# Discover modules
php artisan ironflow:discover --fresh

# Show boot order
php artisan ironflow:boot-order
```

### Module Generators

```bash
# Create a controller in a module
php artisan ironflow:make:controller PostController Blog --resource

# Create a model in a module
php artisan ironflow:make:model Post Blog --migration --factory

# Create a service in a module
php artisan ironflow:make:service PostService Blog

# Create a migration in a module
php artisan ironflow:make:migration create_posts_table Blog --create=posts

# Create a factory in a module
php artisan ironflow:make:factory PostFactory Blog --model=Post
```

### Module Publishing

```bash
# Prepare module for publishing to Packagist
php artisan ironflow:module:publish Blog

# Install a module from Packagist
php artisan ironflow:module:install ironflow/blog-module

# Install from local path
php artisan ironflow:module:install ./my-module --local
```

### Cache Management

```bash
# Clear module cache
php artisan ironflow:cache:clear

# Cache modules for faster boot
php artisan ironflow:cache:modules
```

## Module Dependencies

Modules can depend on other modules. IronFlow automatically resolves dependencies and boots modules in the correct order.

```php
public function metadata(): ModuleMetadata
{
    return new ModuleMetadata(
        name: 'Comments',
        dependencies: ['Blog', 'User'], // This module needs Blog and User
        required: true, // Fail if dependencies are missing
    );
}
```

### Dependency Resolution

IronFlow uses topological sorting to determine boot order:

1. Validates all dependencies exist
2. Detects circular dependencies
3. Calculates optimal boot order
4. Boots modules in dependency order

## Module Lifecycle

Each module goes through these states:

1. **REGISTERED** - Module is discovered and registered
2. **PRELOADED** - Dependencies are being loaded
3. **BOOTING** - Module is currently booting
4. **BOOTED** - Module successfully booted
5. **FAILED** - Module failed to boot
6. **DISABLED** - Module is disabled

## Configuration

Edit `config/ironflow.php`:

```php
return [
    // Auto-discover modules on boot
    'auto_discover' => true,

    // Auto-boot discovered modules
    'auto_boot' => true,

    // Modules directory
    'modules_path' => app_path('Modules'),

    // Cache discovered modules
    'cache_modules' => true,

    // Throw exception on boot failure
    'throw_on_boot_failure' => false,

    // Allow modules to override app services
    'allow_override' => false,

    // Disabled modules
    'disabled_modules' => [],
];
```

## Testing

IronFlow uses Pest for testing:

```bash
composer test
```

### Writing Module Tests

```php
<?php

use App\Modules\Blog\Models\Post;

test('can create a blog post', function () {
    $post = Post::factory()->create([
        'title' => 'Test Post',
    ]);

    expect($post->title)->toBe('Test Post');
});
```

## Advanced Features

### Service Override

Modules can override global services:

```php
public function metadata(): ModuleMetadata
{
    return new ModuleMetadata(
        name: 'CustomAuth',
        allowOverride: true, // Enable override
        provides: ['auth.driver'], // Services this module provides
    );
}
```

### Priority Boot Order

Control boot order with priority:

```php
public function metadata(): ModuleMetadata
{
    return new ModuleMetadata(
        name: 'CoreModule',
        priority: 100, // Higher priority boots first
    );
}
```

### Custom Module Contracts

Implement optional contracts for additional features:

```php
class BlogModule extends BaseModule implements 
    RoutableInterface,
    ViewableInterface,
    MigratableInterface,
    ConfigurableInterface
{
    // Your implementation
}
```

## Publishing Modules

Prepare your module for distribution:

```bash
php artisan ironflow:module:publish Blog
```

This creates a `publishables/blog` directory with:
- Complete module source
- `composer.json` configured for Packagist
- `README.md`
- `LICENSE.md`
- `.gitignore`
- GitHub Actions workflow

Then:

1. Create a GitHub repository
2. Push your code
3. Submit to [Packagist](https://packagist.org)

## Architecture

### The Anvil

The **Anvil** is IronFlow's core module manager:

```php
use IronFlow\Facades\Anvil;

// Register a module
Anvil::register($module);

// Load and boot all modules
Anvil::load()->boot();

// Check if module is booted
if (Anvil::isModuleBooted('Blog')) {
    // ...
}

// Get a module instance
$blogModule = Anvil::getModule('Blog');
```

### Module Structure

Each module is self-contained:

- **Controllers** - HTTP request handlers
- **Models** - Eloquent models
- **Services** - Business logic
- **Routes** - Module-specific routes
- **Migrations** - Database schema
- **Views** - Blade templates
- **Tests** - Module tests

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security vulnerabilities, please email `ironflow.framework@gmail.com`.

## Credits

- IronFlow Team
- [Laravel Framework](https://laravel.com)
- All Contributors

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.
