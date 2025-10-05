<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * ModulePublishCommand
 *
 * Prepares a module for distribution as a Composer package
 */
class ModulePublishCommand extends Command
{
    protected $signature = 'ironflow:module:publish
                            {module : The module name}
                            {--path=publishables : The output directory}
                            {--force : Overwrite existing files}';

    protected $description = 'Prepare a module for publishing to Packagist';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $moduleName = $this->argument('module');
        $outputPath = $this->option('path');
        $force = $this->option('force');

        $modulePath = app_path("Modules/{$moduleName}");

        if (!$this->files->exists($modulePath)) {
            $this->error("Module {$moduleName} does not exist!");
            return self::FAILURE;
        }

        $publishPath = base_path($outputPath . '/' . Str::slug($moduleName));

        if ($this->files->exists($publishPath) && !$force) {
            $this->error("Publish directory already exists! Use --force to overwrite.");
            return self::FAILURE;
        }

        $this->info("Publishing module: {$moduleName}");
        $this->newLine();

        // Create publish directory
        if ($force && $this->files->exists($publishPath)) {
            $this->files->deleteDirectory($publishPath);
        }
        $this->files->makeDirectory($publishPath, 0755, true);

        // Copy module files
        $this->copyModuleFiles($modulePath, $publishPath);

        // Generate package files
        $this->generateComposerJson($publishPath, $moduleName);
        $this->generateReadme($publishPath, $moduleName);
        $this->generateLicense($publishPath);
        $this->generateGitignore($publishPath);
        $this->generateGitHubWorkflow($publishPath);

        $this->newLine();
        $this->info("Module {$moduleName} prepared for publishing!");
        $this->line("Location: {$publishPath}");
        $this->newLine();
        $this->info("Next steps:");
        $this->line("  1. cd {$publishPath}");
        $this->line("  2. git init");
        $this->line("  3. git add .");
        $this->line("  4. git commit -m 'Initial commit'");
        $this->line("  5. Create a repository on GitHub");
        $this->line("  6. git remote add origin <your-repo-url>");
        $this->line("  7. git push -u origin main");
        $this->line("  8. Submit to Packagist: https://packagist.org/packages/submit");

        return self::SUCCESS;
    }

    protected function copyModuleFiles(string $source, string $destination): void
    {
        $excludes = [
            'vendor',
            'node_modules',
            '.git',
            '.env',
            'storage',
            'Tests',
        ];

        $this->line("  → Copying module files...");

        $this->files->copyDirectory($source, $destination . '/src');

        // Remove excluded directories
        foreach ($excludes as $exclude) {
            $path = $destination . '/src/' . $exclude;
            if ($this->files->exists($path)) {
                $this->files->deleteDirectory($path);
            }
        }
    }

    protected function generateComposerJson(string $path, string $moduleName): void
    {
        $this->line("  → Generating composer.json...");

        $slug = Str::slug($moduleName);
        $moduleClass = "App\\Modules\\{$moduleName}\\{$moduleName}Module";

        // Try to get module metadata
        $description = "IronFlow module: {$moduleName}";
        $authors = [['name' => config('app.name', 'Your Name')]];

        if (class_exists($moduleClass)) {
            try {
                $module = new $moduleClass();
                $metadata = $module->metadata();
                $description = $metadata->description ?: $description;

                if (!empty($metadata->authors)) {
                    $authors = array_map(fn($author) => ['name' => $author], $metadata->authors);
                }
            } catch (\Exception $e) {
                // Use defaults
            }
        }

        $composer = [
            'name' => "ironflow/{$slug}",
            'description' => $description,
            'type' => 'library',
            'license' => 'MIT',
            'authors' => $authors,
            'require' => [
                'php' => '^8.3',
                'illuminate/support' => '^12.0',
                'ironflow/ironflow' => '^1.0',
            ],
            'require-dev' => [
                'phpunit/phpunit' => '^11.0',
                'orchestra/testbench' => '^9.0',
            ],
            'autoload' => [
                'psr-4' => [
                    "IronFlow\\{$moduleName}\\" => 'src/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    "IronFlow\\{$moduleName}\\Tests\\" => 'tests/',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [
                        "IronFlow\\{$moduleName}\\{$moduleName}ServiceProvider",
                    ],
                ],
                'ironflow' => [
                    'module' => "IronFlow\\{$moduleName}\\{$moduleName}Module",
                ],
            ],
            'minimum-stability' => 'dev',
            'prefer-stable' => true,
        ];

        $this->files->put(
            $path . '/composer.json',
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    protected function generateReadme(string $path, string $moduleName): void
    {
        $this->line("  → Generating README.md...");

        $slug = Str::slug($moduleName);
        $readme = <<<MD
# {$moduleName} Module

An IronFlow module for Laravel 12.

## Installation

You can install the package via composer:

```bash
composer require ironflow/{$slug}
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="{$slug}-config"
```

## Usage

```php
// Usage examples here
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email security@example.com instead of using the issue tracker.

## Credits

- [Author Name](https://github.com/username)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
MD;

        $this->files->put($path . '/README.md', $readme);
    }

    protected function generateLicense(string $path): void
    {
        $this->line("  → Generating LICENSE.md...");

        $year = date('Y');
        $author = config('app.name', 'Your Name');

        $license = <<<LICENSE
MIT License

Copyright (c) {$year} {$author}

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
LICENSE;

        $this->files->put($path . '/LICENSE.md', $license);
    }

    protected function generateGitignore(string $path): void
    {
        $this->line("  → Generating .gitignore...");

        $gitignore = <<<GITIGNORE
/vendor
composer.lock
.phpunit.result.cache
.php-cs-fixer.cache
.idea
.vscode
*.log
.DS_Store
GITIGNORE;

        $this->files->put($path . '/.gitignore', $gitignore);
    }

    protected function generateGitHubWorkflow(string $path): void
    {
        $this->line("  → Generating GitHub Actions workflow...");

        $workflowPath = $path . '/.github/workflows';
        $this->files->makeDirectory($workflowPath, 0755, true);

        $workflow = <<<YAML
name: Tests

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest

    strategy:
      matrix:
        php: [8.3]
        laravel: [12.*]

    name: PHP \${{ matrix.php }} - Laravel \${{ matrix.laravel }}

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: \${{ matrix.php }}
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite
          coverage: none

      - name: Install dependencies
        run: |
          composer require "laravel/framework:\${{ matrix.laravel }}" --no-interaction --no-update
          composer update --prefer-dist --no-interaction --no-progress

      - name: Execute tests
        run: vendor/bin/phpunit
YAML;

        $this->files->put($workflowPath . '/tests.yml', $workflow);
    }
}
