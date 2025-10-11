<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * MakeMigrationCommand
 */
class MakeMigrationCommand extends Command
{
    protected $signature = 'ironflow:make:migration
                            {name : The name of the migration}
                            {module : The module name}
                            {--create= : The table to be created}
                            {--table= : The table to migrate}';

    protected $description = 'Create a new migration in a module';


    public function handle(): int
    {
        $name = $this->argument('name');
        $module = $this->argument('module');
        $create = $this->option('create');
        $table = $this->option('table');

        $modulePath = config('ironflow.path') . '/' . $module;

        $stub = $this->getStub('migration');
        $tableName = Str::snake(Str::plural($name));
        $timestamp = date('Y_m_d_His');

        if ($create) {
            $className = 'Create' . Str::studly($tableName) . 'Table';
            $filePath = $modulePath . '/Database/Migrations/' . $timestamp . '_create_' . $tableName . '_table.php';
        }

        if ($table) {
            $className = 'Modify' . Str::studly($tableName) . 'Table';
            $filePath = $modulePath . '/Database/Migrations/' . $timestamp . '_create_' . $tableName . '_table.php';
        }


        $content = str_replace(
            ['{{className}}', '{{tableName}}'],
            [$className, $tableName],
            $stub
        );

        File::put(
            $filePath,
            $content
        );

        return self::SUCCESS;
    }
}
