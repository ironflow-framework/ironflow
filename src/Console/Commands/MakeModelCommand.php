<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * MakeModelCommand
 */
class MakeModelCommand extends Command
{
    protected $signature = 'ironflow:make:model
                            {name : The name of the model}
                            {module : The module name}
                            {--m|migration : Create a migration for the model}
                            {--f|factory : Create a factory for the model}';

    protected $description = 'Create a new model in a module';


    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $module = $this->argument('module');

        $modulePath = config('ironflow.path') . '/' . $module;

        $stub = $this->getStub('model');
        $namespace = config('ironflow.namespace', 'Modules');
        $content = str_replace(
            ['{{namespace}}', '{{name}}'],
            [$namespace, $name],
            $stub
        );
        File::put($modulePath . '/Models/' . $name . '.php', $content);

        $this->info("Model {$name} created successfully!");

        if ($this->option('migration')) {
            $this->call('ironflow:make:migration', [
                'name' => "create_" . Str::snake(Str::pluralStudly($name)) . "_table",
                'module' => $module,
            ]);
        }

        if ($this->option('factory')) {
            $this->call('ironflow:make:factory', [
                'name' => $name . 'Factory',
                'module' => $module,
            ]);
        }

        return self::SUCCESS;
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
