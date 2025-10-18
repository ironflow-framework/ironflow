<?php

declare(strict_types=1);

namespace IronFlow\Publishing;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use IronFlow\Core\BaseModule;
use IronFlow\Contracts\ExportableInterface;
use IronFlow\Exceptions\PublishException;

class ModulePublisher
{
    protected Application $app;
    protected string $publishPath;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->publishPath = base_path('publishable');
    }

    /**
     * Publish a module for distribution
     */
    public function publish(BaseModule $module): string
    {
        if (!$module instanceof ExportableInterface) {
            throw new PublishException(
                "Module {$module->getName()} must implement ExportableInterface to be publishable"
            );
        }

        // Validate pré-publication
        $this->validateModule($module);

        // Call before hook
        $module->beforePublish();

        $packageName = $module->getPackageName();
        $packagePath = $this->createPackageStructure($packageName);

        // Copy module files
        $this->copyModuleFiles($module, $packagePath);

        // Generate composer.json
        $this->generateComposerJson($module, $packagePath);

        // Generate README.md
        $this->generateReadme($module, $packagePath);

        // Generate LICENSE
        $this->generateLicense($module, $packagePath);

        // Generate .gitignore
        $this->generateGitignore($packagePath);

        // Generate ServiceProvider
        $this->generateServiceProvider($module, $packagePath);

        // Generate tests skeleton
        $this->generateTestsSkeleton($module, $packagePath);

        // Call after hook
        $module->afterPublish();

        return $packagePath;
    }

    protected function validateModule(BaseModule $module): void
    {
        if (!$module instanceof ExportableInterface) {
            throw new PublishException("Module must implement ExportableInterface");
        }

        $packageName = $module->getPackageName();
        if (!preg_match('/^[a-z0-9\-]+\/[a-z0-9\-]+$/', $packageName)) {
            throw new PublishException("Invalid package name: {$packageName}");
        }

        $license = $module->getPackageLicense();
        $validLicenses = ['MIT', 'GPL-3.0', 'Apache-2.0', 'BSD-3-Clause'];
        if (!in_array($license, $validLicenses)) {
            throw new PublishException("Unsupported license: {$license}");
        }
    }

    /**
     * Create package directory structure
     */
    protected function createPackageStructure(string $packageName): string
    {
        [$vendor, $package] = explode('/', $packageName);

        $packagePath = $this->publishPath . '/' . $package;

        if (File::exists($packagePath)) {
            File::deleteDirectory($packagePath);
        }

        File::makeDirectory($packagePath, 0755, true);
        File::makeDirectory($packagePath . '/src', 0755, true);
        File::makeDirectory($packagePath . '/tests', 0755, true);
        File::makeDirectory($packagePath . '/config', 0755, true);
        File::makeDirectory($packagePath . '/resources', 0755, true);
        File::makeDirectory($packagePath . '/database/migrations', 0755, true);
        File::makeDirectory($packagePath . '/database/seeders', 0755, true);

        return $packagePath;
    }

    /**
     * Copy module files to package
     */
    protected function copyModuleFiles(BaseModule $module, string $packagePath): void
    {
        $modulePath = $module->getPath();
        $excludedPaths = $module->getExcludedPaths();

        $directories = [
            'Http' => 'src/Http',
            'Models' => 'src/Models',
            'Services' => 'src/Services',
            'Resources' => 'resources',
            'Database' => 'database',
            'config' => 'config',
            'routes' => 'routes',
        ];

        foreach ($directories as $source => $destination) {
            $sourcePath = $modulePath . '/' . $source;
            $destPath = $packagePath . '/' . $destination;

            if (File::isDirectory($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath, $excludedPaths);
            }
        }

        // Copy main module class
        $moduleClass = $module->getName() . 'Module.php';
        $moduleServiceProvider = $module->getName() . 'ServiceProvider.php';

        if (File::exists($modulePath . '/' . $moduleClass)) {
            File::copy(
                $modulePath . '/' . $moduleClass,
                $packagePath . '/src/' . $moduleClass
            );
        }

        if (File::exists($modulePath . '/' . $moduleServiceProvider)) {
            File::copy(
                $modulePath . '/' . $moduleServiceProvider,
                $packagePath . '/src/' . $moduleServiceProvider
            );
        }
    }

    /**
     * Copy directory with exclusions
     */
    protected function copyDirectory(string $source, string $destination, array $excludedPaths): void
    {
        if (!File::isDirectory($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        foreach (File::allFiles($source) as $file) {
            $relativePath = str_replace($source . '/', '', $file->getPathname());

            // Check if file should be excluded
            $shouldExclude = false;
            foreach ($excludedPaths as $excludedPath) {
                if (str_contains($relativePath, $excludedPath)) {
                    $shouldExclude = true;
                    break;
                }
            }

            if (!$shouldExclude) {
                $destFile = $destination . '/' . $relativePath;
                $destDir = dirname($destFile);

                if (!File::isDirectory($destDir)) {
                    File::makeDirectory($destDir, 0755, true);
                }

                File::copy($file->getPathname(), $destFile);
            }
        }
    }

    /**
     * Generate composer.json
     */
    protected function generateComposerJson(ExportableInterface $module, string $packagePath): void
    {
        $moduleName = $module->getName();
        $namespace = str_replace('\\', '\\\\', $module->getNamespace());

        $composerData = [
            'name' => $module->getPackageName(),
            'description' => $module->getPackageDescription(),
            'keywords' => $module->getPackageKeywords(),
            'license' => $module->getPackageLicense(),
            'type' => 'library',
            'authors' => $module->getPackageAuthors(),
            'require' => array_merge(
                [
                    'php' => '^8.2',
                    'illuminate/support' => '^12.0',
                    'ironflow/ironflow' => '^4.0',
                ],
                $module->getPackageDependencies()
            ),
            'require-dev' => array_merge(
                [
                    'phpunit/phpunit' => '^11.0',
                    'orchestra/testbench' => '^10.0',
                ],
                $module->getPackageDevDependencies()
            ),
            'autoload' => [
                'psr-4' => [
                    $namespace . '\\' => 'src/'
                ]
            ],
            'autoload-dev' => [
                'psr-4' => [
                    $namespace . '\\Tests\\' => 'tests/'
                ]
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [
                        $namespace . '\\' . $moduleName . 'ServiceProvider'
                    ]
                ],
                'ironflow' => [
                    'module' => $namespace . '\\' . $moduleName . 'Module'
                ]
            ],
            'minimum-stability' => 'dev',
            'prefer-stable' => true,
        ];

        // Merge additional data
        $composerData = array_merge($composerData, $module->getAdditionalComposerData());

        // Add homepage if provided
        if ($homepage = $module->getPackageHomepage()) {
            $composerData['homepage'] = $homepage;
        }

        File::put(
            $packagePath . '/composer.json',
            json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Generate README.md
     */
    protected function generateReadme(ExportableInterface $module, string $packagePath): void
    {
        $moduleName = $module->getName();
        $packageName = $module->getPackageName();
        $description = $module->getPackageDescription();

        // Préparer les variables complexes avant le heredoc
        $namespace = $module->getNamespace();
        $usageLine = "use {$namespace}\\Services\\{$moduleName}Service;";

        $authors = $module->getPackageAuthors();
        $authorName = $authors[0]['name'] ?? 'Author Name';
        $homepage = $module->getPackageHomepage() ?? '#';
        $license = $module->getPackageLicense();

        $readme = <<<MD
# {$moduleName}

{$description}

## Installation

```bash
composer require {$packageName}
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag={$moduleName}-config
```

## Usage

```php
{$usageLine}

// Your usage example here
```

## Features

- Feature 1
- Feature 2
- Feature 3

## Requirements

- PHP 8.2 or higher
- Laravel 12.0 or higher
- IronFlow 4.0 or higher

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review our [security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [{$authorName}]({$homepage})
- [All Contributors](../../contributors)

## License

The {$license} License. Please see [License File](LICENSE.md) for more information.
MD;

        File::put($packagePath . '/README.md', $readme);
    }

    /**
     * Generate LICENSE file
     */
    protected function generateLicense(ExportableInterface $module, string $packagePath): void
    {
        $license = $module->getPackageLicense();
        $year = date('Y');
        $author = $module->getPackageAuthors()[0]['name'] ?? 'Author Name';

        $licenseText = match (strtoupper($license)) {
            'MIT' => $this->getMITLicense($year, $author),
            'GPL-3.0' => $this->getGPL3License($year, $author),
            'APACHE-2.0' => $this->getApacheLicense($year, $author),
            default => "Copyright (c) {$year} {$author}\n\nAll rights reserved.",
        };

        File::put($packagePath . '/LICENSE.md', $licenseText);
    }

    /**
     * Generate .gitignore
     */
    protected function generateGitignore(string $packagePath): void
    {
        $gitignore = <<<TXT
/vendor
composer.lock
.phpunit.result.cache
.DS_Store
.idea
.vscode
*.swp
*.swo
*~
.env
TXT;

        File::put($packagePath . '/.gitignore', $gitignore);
    }

    /**
     * Generate ServiceProvider for standalone package
     */
    protected function generateServiceProvider(BaseModule $module, string $packagePath): void
    {
        $moduleName = $module->getName();
        $namespace = $module->getNamespace();

        $provider = <<<PHP
<?php

namespace {$namespace};

use Illuminate\Support\ServiceProvider;
use IronFlow\Facades\Anvil;

class {$moduleName}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register the module with IronFlow
        if (class_exists(Anvil::class)) {
            Anvil::registerModule('{$moduleName}', {$moduleName}Module::class);
        }

        // Merge configuration
        \$this->mergeConfigFrom(
            __DIR__.'/../config/{$moduleName}.php',
            strtolower('{$moduleName}')
        );
    }

    public function boot(): void
    {
        // Publish configuration
        \$this->publishes([
            __DIR__.'/../config/{$moduleName}.php' => config_path(strtolower('{$moduleName}') . '.php'),
        ], '{$moduleName}-config');

        // Publish migrations
        if (\$this->app->runningInConsole()) {
            \$this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }
}
PHP;

        File::put($packagePath . '/src/' . $moduleName . 'ServiceProvider.php', $provider);
    }

    /**
     * Generate tests skeleton
     */
    protected function generateTestsSkeleton(BaseModule $module, string $packagePath): void
    {
        $moduleName = $module->getName();
        $namespace = $module->getNamespace();

        // phpunit.xml
        $phpunit = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">./src</directory>
        </include>
    </coverage>
</phpunit>
XML;

        File::put($packagePath . '/phpunit.xml', $phpunit);

        // Test case
        $testCase = <<<PHP
<?php

namespace {$namespace}\\Tests;

use Orchestra\\Testbench\\TestCase as Orchestra;
use IronFlow\\IronFlowServiceProvider;
use {$namespace}\\{$moduleName}ServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders(\$app): array
    {
        return [
            IronFlowServiceProvider::class,
            {$moduleName}ServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp(\$app): void
    {
        \$app['config']->set('database.default', 'testing');
        \$app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }
}
PHP;

        File::put($packagePath . '/tests/TestCase.php', $testCase);

        // Example test
        $exampleTest = <<<PHP
<?php

namespace {$namespace}\\Tests;

class ExampleTest extends TestCase
{
    /** @test */
    public function it_works(): void
    {
        \$this->assertTrue(true);
    }
}
PHP;

        File::put($packagePath . '/tests/ExampleTest.php', $exampleTest);
    }

    /**
     * Get MIT License text
     */
    protected function getMITLicense(string $year, string $author): string
    {
        return <<<TXT
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
TXT;
    }

    protected function getGPL3License(string $year, string $author): string
    {
        return "GPL-3.0 License\n\nCopyright (c) {$year} {$author}\n\nSee https://www.gnu.org/licenses/gpl-3.0.en.html";
    }

    protected function getApacheLicense(string $year, string $author): string
    {
        return "Apache License 2.0\n\nCopyright (c) {$year} {$author}\n\nSee https://www.apache.org/licenses/LICENSE-2.0";
    }
}
