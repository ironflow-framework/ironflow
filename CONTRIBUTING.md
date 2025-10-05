# Contributing to IronFlow

Thank you for considering contributing to IronFlow! This guide will help you get started.

## Development Setup

### Prerequisites

- PHP 8.3 or higher
- Composer
- Laravel 12
- Git

### Setting Up Your Development Environment

1. **Fork and Clone**

```bash
git clone https://github.com/ironflow-framework/ironflow.git
cd ironflow
```

2. **Install Dependencies**

```bash
composer install
```

3. **Run Tests**

```bash
composer test
```

## Testing

IronFlow uses Pest for testing. All new features must include tests.

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test file
vendor/bin/pest tests/Unit/AnvilTest.php

# Run tests in watch mode
vendor/bin/pest --watch
```

### Writing Tests

Tests are located in the `tests/` directory:

```
tests/
├── Feature/         # Integration tests
├── Unit/           # Unit tests
└── Pest.php        # Pest configuration
```

Example test:

```php
<?php

use IronFlow\Core\Anvil;
use IronFlow\Core\ModuleMetadata;

test('can register a module', function () {
    $anvil = new Anvil();
    $module = createTestModule('TestModule');

    $anvil->register($module);

    expect($anvil->getModules())->toHaveCount(1);
});
```

### Test Helpers

Common test helpers are available:

```php
// Create a mock module
$module = createTestModule('TestModule', [
    'dependencies' => ['OtherModule'],
    'version' => '2.0.0',
]);

// Create a temporary module directory
$path = createTempModule('TestModule');

// Clean up after tests
afterEach(function () {
    cleanupTestModules();
});
```

## Code Style

IronFlow follows PSR-12 coding standards.

### Running Code Style Fixer

```bash
composer format
```

### Code Style Rules

- Use type hints for parameters and return types
- Use strict types: `declare(strict_types=1);`
- Use PHP 8.3 features where appropriate
- Document public methods with PHPDoc
- Keep methods focused and single-purpose

Example:

```php
<?php

declare(strict_types=1);

namespace IronFlow\Core;

/**
 * Example class demonstrating code style
 */
class ExampleClass
{
    /**
     * Process the given data
     */
    public function process(array $data): array
    {
        return array_map(
            fn(string $item): string => strtoupper($item),
            $data
        );
    }
}
```

## Pull Request Process

### Before Submitting

1. **Write Tests** - All new features and bug fixes must include tests
2. **Run Tests** - Ensure all tests pass: `composer test`
3. **Check Code Style** - Run `composer format`
4. **Update Documentation** - Update README.md if needed
5. **Write Clear Commits** - Use descriptive commit messages

### Commit Message Format

```bash
type(scope): subject

body

footer
```

Types:

- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation changes
- **style**: Code style changes (formatting, etc.)
- **refactor**: Code refactoring
- **test**: Adding or updating tests
- **chore**: Maintenance tasks

Example:

```markdown
feat(anvil): add priority-based boot ordering

Implement priority field in ModuleMetadata to allow modules
to specify boot order priority. Higher priority modules boot
first after dependency resolution.

Closes #123
```

### Pull Request Guidelines

1. Create a feature branch: `git checkout -b feature/my-feature`
2. Make your changes
3. Commit with clear messages
4. Push to your fork
5. Create a Pull Request with:
   - Clear title and description
   - Reference to related issues
   - Screenshots/examples if applicable

### Review Process

- Maintainers will review your PR
- Address any requested changes
- Once approved, your PR will be merged

## Development Workflow

### Adding a New Feature

1. **Create an Issue** - Discuss the feature first
2. **Fork & Branch** - Create a feature branch
3. **Write Tests** - Test-driven development
4. **Implement Feature** - Write the code
5. **Document** - Add/update documentation
6. **Submit PR** - Create pull request

### Example: Adding a New Command

```php
<?php

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;

class MyCommand extends Command
{
    protected $signature = 'ironflow:my-command {arg}';
    protected $description = 'Description of my command';

    public function handle(): int
    {
        $arg = $this->argument('arg');
        
        // Implementation
        
        $this->info('Success!');
        return self::SUCCESS;
    }
}
```

Then register in `IronFlowServiceProvider`:

```php
$this->commands([
    MyCommand::class,
]);
```

And add tests:

```php
test('my command works', function () {
    Artisan::call('ironflow:my-command', ['arg' => 'test']);
    
    expect(Artisan::output())->toContain('Success!');
});
```

## Module Publishing Workflow

### Creating a Publishable Module

1. **Develop Locally**

```bash
php artisan ironflow:module:create MyModule
# Develop your module
```

2. **Test Thoroughly**

```bash
# Write comprehensive tests
php artisan test --filter=MyModule
```

3. **Prepare for Publishing**

```bash
php artisan ironflow:module:publish MyModule
cd publishables/mymodule
```

4. **Version Control**

```bash
git init
git add .
git commit -m "Initial commit"
```

5. **Create Repository**

- Create GitHub repository
- Push code
- Tag releases

```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

6. **Submit to Packagist**

- Go to https://packagist.org
- Submit your package
- Set up auto-update hook

### Module Guidelines

Published modules should:

- Include comprehensive README
- Have complete test coverage
- Follow semantic versioning
- Document all public APIs
- Include LICENSE file
- Have GitHub Actions CI

## Documentation

### Updating Documentation

Documentation files:

- `README.md` - Main documentation
- `CONTRIBUTING.md` - This file
- `CHANGELOG.md` - Version history

### Documentation Style

- Use clear, concise language
- Include code examples
- Add command examples with output
- Use proper markdown formatting
- Keep examples up to date

## Bug Reports

### Before Reporting

1. Search existing issues
2. Try latest version
3. Create minimal reproduction

### Bug Report Template

```markdown
## Description
Clear description of the bug

## Steps to Reproduce
1. First step
2. Second step
3. ...

## Expected Behavior
What should happen

## Actual Behavior
What actually happens

## Environment
- IronFlow version: 1.0.0
- Laravel version: 12.0
- PHP version: 8.3.0
- OS: Ubuntu 22.04
```

## Feature Requests

Feature requests are welcome! Please:

1. Check if feature already requested
2. Clearly describe the use case
3. Provide examples
4. Discuss implementation approach

## Questions?

- Open a GitHub Discussion
- Join our Discord community
- Check existing documentation

## Code of Conduct

### Our Standards

- Be respectful and inclusive
- Welcome newcomers
- Accept constructive criticism
- Focus on what's best for the community

### Unacceptable Behavior

- Harassment or discrimination
- Trolling or insulting comments
- Publishing private information
- Other unprofessional conduct

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

## Thank You!

Your contributions make IronFlow better for everyone. Thank you for being part of the community!
