<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use IronFlow\Facades\Anvil;

/**
 * PublishModuleCommand
 *
 * Publish a module as a standalone Packagist package.
 */
class PublishModuleCommand extends Command
{
    protected $signature = 'ironflow:module:publish {name : The name of the module}
                            {--output= : Output directory for the package}
                            {--dry-run : Preview what would be exported}';

    protected $description = 'Export an IronFlow module for Packagist publication';

    public function handle(): int
    {
        $name = $this->argument('name');
        $module = Anvil::getModule($name);

        if (!$module) {
            $this->error("Module {$name} not found!");
            return 1;
        }

        if (!$module instanceof \IronFlow\Contracts\ExportableInterface) {
            $this->error("Module {$name} does not implement ExportableInterface!");
            return 1;
        }

        $outputPath = $this->option('output')
            ?? config('ironflow.export.output_path', storage_path('ironflow/exports'))
            . '/' . Str::slug($name);

        $this->info("Exporting module: {$name}");

        try {
            $exportData = $module->export();

            if ($this->option('dry-run')) {
                $this->info("Dry run - would export the following:");
                $this->table(['Type', 'Items'], [
                    ['Files', count($exportData['files'] ?? [])],
                    ['Assets', count($exportData['assets'] ?? [])],
                    ['Config', count($exportData['config'] ?? [])],
                    ['Stubs', count($exportData['stubs'] ?? [])],
                ]);
                return 0;
            }

            // Create output directory
            File::makeDirectory($outputPath, 0755, true, true);

            // Generate composer.json
            $this->generateComposerJson($module, $outputPath);

            // Copy files
            $this->copyFiles($exportData, $outputPath);

            // Generate README
            $this->generatePackageReadme($module, $outputPath);

            $this->info("Module exported successfully to: {$outputPath}");
            $this->info("Package name: {$module->getPackageName()}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to export module: {$e->getMessage()}");
            return 1;
        }
    }

    protected function generateComposerJson($module, string $path): void
    {
        $metadata = $module->getMetadata();
        $packageName = $module->getPackageName();

        $composer = [
            'name' => $packageName,
            'description' => $module->getPackageDescription(),
            'type' => 'library',
            'license' => 'MIT',
            'authors' => $metadata->getAuthors(),
            'require' => $module->getPackageDependencies(),
            'autoload' => $module->getPackageAutoload(),
            'extra' => [
                'laravel' => [
                    'providers' => [
                        str_replace('/', '\\', ucfirst($packageName)) . '\\' . $metadata->getName() . 'Module'
                    ]
                ]
            ]
        ];

        File::put($path . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function copyFiles(array $exportData, string $outputPath): void
    {
        foreach (['files', 'assets', 'config', 'stubs'] as $type) {
            if (empty($exportData[$type])) {
                continue;
            }

            foreach ($exportData[$type] as $source) {
                if (!File::exists($source)) {
                    continue;
                }

                $destination = $outputPath . '/src/' . basename($source);

                if (File::isDirectory($source)) {
                    File::copyDirectory($source, $destination);
                } else {
                    File::copy($source, $destination);
                }
            }
        }
    }

    protected function generatePackageReadme($module, string $path): void
    {
        $metadata = $module->getMetadata();
        $name = $metadata->getName();
        $description = $metadata->getDescription();

        $readme = <<<MD
# {$name}

{$description}

## Installation

```bash
composer require {$module->getPackageName()}
```

## Usage

This module is automatically registered via Laravel's package discovery.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag={$name}-config
```

## License

MIT

MD;

        File::put($path . '/README.md', $readme);
    }
}
