<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * MakeModuleCommand
 *
 * Generate a new IronFlow module with all necessary structure.
 */
class MakeModuleCommand extends Command
{
    protected $signature = 'ironflow:module:make {name : The name of the module}
                            {--view : Include view support}
                            {--route : Include route support}
                            {--migration : Include migration support}
                            {--config : Include configuration support}
                            {--asset : Include asset support}
                            {--model : Include model support}
                            {--exposable : Make module exposable}
                            {--exportable : Make module exportable}
                            {--force : Overwrite existing module}';

    protected $description = 'Create a new IronFlow module';

    public function handle(): int
    {
        $name = $this->argument('name');
        $modulePath = config('ironflow.path') . '/' . $name;

        if (File::exists($modulePath) && !$this->option('force')) {
            $this->error("Module {$name} already exists!");
            return 1;
        }

        $this->info("Creating module: {$name}");

        // Create module directory structure
        $this->createDirectoryStructure($modulePath, $name);

        // Generate module class
        $this->generateModuleClass($modulePath, $name);

        // Generate additional components based on options
        if ($this->option('view')) {
            $this->generateViews($modulePath, $name);
        }

        if ($this->option('route')) {
            $this->generateRoutes($modulePath, $name);
        }

        if ($this->option('migration')) {
            $this->generateMigration($modulePath, $name);
        }

        if ($this->option('config')) {
            $this->generateConfig($modulePath, $name);
        }

        if ($this->option('asset')) {
            $this->generateAssets($modulePath);
        }

        if ($this->option('model')) {
            $this->generateModel($modulePath, $name);
        }

        $this->generateReadme($modulePath, $name);

        $this->info("Module {$name} created successfully!");
        $this->info("Location: {$modulePath}");

        return self::SUCCESS;
    }

    protected function createDirectoryStructure(string $path, string $name): void
    {
        $directories = [
            'Http/Controllers',
            'Http/Middleware',
            'Services',
            'Providers',
            'config',
        ];

        if ($this->option('route')) {
            $directories[] = 'Routes';
        }

        if ($this->option('migration')) {
            $directories[] = 'Database/Migrations';
        }

        if ($this->option('model')) {
            $directories[] = 'Models';
        }

        if ($this->option('view')) {
            $directories[] = 'Resources/views';
        }

        if ($this->option('asset')) {
            $directories[] = 'Resources/css';
            $directories[] = 'Resources/js';
        }

        foreach ($directories as $dir) {
            File::makeDirectory($path . '/' . $dir, 0755, true, true);
        }
    }

    protected function generateModuleClass(string $path, string $name): void
    {
        $stub = $this->getStub('module');
        $namespace = config('ironflow.namespace', 'Modules');

        $interfaces = ['BootableInterface'];
        $traits = [];

        if ($this->option('view')) {
            $interfaces[] = 'ViewableInterface';
        }
        if ($this->option('route')) {
            $interfaces[] = 'RoutableInterface';
        }
        if ($this->option('migration')) {
            $interfaces[] = 'MigratableInterface';
        }
        if ($this->option('config')) {
            $interfaces[] = 'ConfigurableInterface';
        }
        if ($this->option('exposable')) {
            $interfaces[] = 'ExposableInterface';
        }
        if ($this->option('exportable')) {
            $interfaces[] = 'ExportableInterface';
        }

        $interfacesStr = !empty($interfaces) ? ', ' . implode(', ', $interfaces) : '';

        $content = str_replace(
            ['{{namespace}}', '{{name}}', '{{interfaces}}'],
            [$namespace, $name, $interfacesStr],
            $stub
        );

        File::put($path . '/' . $name . 'Module.php', $content);
    }

    protected function generateViews(string $path, string $name): void
    {
        $stub = $this->getStub('view');
        $content = str_replace('{{name}}', $name, $stub);
        File::put($path . '/Resources/views/index.blade.php', $content);
    }

    protected function generateRoutes(string $path, string $name): void
    {
        $stub = $this->getStub('route');
        $namespace = config('ironflow.namespace', 'Modules');
        $content = str_replace(
            ['{{namespace}}', '{{name}}'],
            [$namespace, $name],
            $stub
        );
        File::put($path . '/Routes/web.php', $content);
        File::put($path . '/Routes/api.php', "<?php\n\n// API routes for {$name} module\n");
    }

    protected function generateMigration(string $path, string $name): void
    {
        $stub = $this->getStub('migration');
        $tableName = Str::snake(Str::plural($name));
        $className = 'Create' . Str::studly($tableName) . 'Table';
        $timestamp = date('Y_m_d_His');

        $content = str_replace(
            ['{{className}}', '{{tableName}}'],
            [$className, $tableName],
            $stub
        );

        File::put(
            $path . '/Database/Migrations/' . $timestamp . '_create_' . $tableName . '_table.php',
            $content
        );
    }

    protected function generateConfig(string $path, string $name): void
    {
        $stub = $this->getStub('config');
        $content = str_replace('{{name}}', Str::lower($name), $stub);
        File::put($path . '/config/' . Str::lower($name) . '.php', $content);
    }

    protected function generateAssets(string $path): void
    {
        File::put($path . '/Resources/css/app.css', "/* Module styles */\n");
        File::put($path . '/Resources/js/app.js', "// Module scripts\n");
    }

    protected function generateModel(string $path, string $name): void
    {
        $stub = $this->getStub('model');
        $namespace = config('ironflow.namespace', 'Modules');
        $content = str_replace(
            ['{{namespace}}', '{{name}}'],
            [$namespace, $name],
            $stub
        );
        File::put($path . '/Models/' . $name . '.php', $content);
    }

    protected function generateReadme(string $path, string $name): void
    {
        $content = <<<MD
# {$name} Module

## Description

This module was generated using IronFlow.

## Installation

Module is automatically discovered by IronFlow.

## Usage

```php
use Modules\\{$name}\\{$name}Module;
```

## Configuration

Configuration file: `config/{$name}.php`

MD;

        File::put($path . '/README.md', $content);
    }

    protected function getStub(string $name): string
    {
        $customPath = resource_path('stubs/ironflow/' . $name . '.stub');
        $defaultPath = __DIR__ . '/../../stubs/' . $name . '.stub';

        if (File::exists($customPath)) {
            return File::get($customPath);
        }

        return File::get($defaultPath);
    }
}
