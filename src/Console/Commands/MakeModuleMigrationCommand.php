<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * MakeMigrationCommand
 */
class MakeModuleMigrationCommand extends Command
{
    protected $signature = 'ironflow:make:migration {module : Module name} {name : Migration name}';
    protected $description = 'Create a new migration for a module';

    public function handle(): int
    {
        $module = $this->argument('module');
        $name = $this->argument('name');
        
        $modulePath = config('ironflow.path') . '/' . $module;
        
        if (!File::isDirectory($modulePath)) {
            $this->error("Module {$module} does not exist!");
            return 1;
        }

        $migrationPath = $modulePath . '/Database/Migrations';
        File::ensureDirectoryExists($migrationPath);

        $stub = $this->getStub('migration');
        
        // Extract table name from migration name
        preg_match('/create_(\w+)_table/', $name, $matches);
        $tableName = $matches[1] ?? Str::snake(Str::plural($module));

        $content = str_replace(
            ['{{table}}'],
            [$tableName],
            $stub
        );

        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";
        
        File::put($migrationPath . '/' . $filename, $content);

        $this->info("Migration created: {$module}/Database/Migrations/{$filename}");

        return 0;
    }

    protected function getStub(string $name): string
    {
        $customPath = resource_path('stubs/ironflow/' . $name . '.stub');
        $defaultPath = __DIR__ . '/../../../stubs/' . $name . '.stub';

        if (File::exists($customPath)) {
            return File::get($customPath);
        }

        return File::get($defaultPath);
    }
}
