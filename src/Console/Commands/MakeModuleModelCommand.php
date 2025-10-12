<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * MakeModelCommand
 */
class MakeModuleModelCommand extends Command
{
    protected $signature = 'ironflow:make:model {module : Module name} {name : Model name}
                            {--migration : Create migration}';
    protected $description = 'Create a new model for a module';

    public function handle(): int
    {
        $module = $this->argument('module');
        $name = $this->argument('name');
        
        $modulePath = config('ironflow.path') . '/' . $module;
        
        if (!File::isDirectory($modulePath)) {
            $this->error("Module {$module} does not exist!");
            return 1;
        }

        $modelPath = $modulePath . '/Models';
        File::ensureDirectoryExists($modelPath);

        $stub = $this->getStub('model');
        $namespace = config('ironflow.namespace', 'Modules');

        $content = str_replace(
            ['{{namespace}}', '{{module}}', '{{name}}'],
            [$namespace, $module, $name],
            $stub
        );

        $filename = "{$name}.php";
        File::put($modelPath . '/' . $filename, $content);

        $this->info("Model created: {$module}/Models/{$filename}");

        // Create migration if requested
        if ($this->option('migration')) {
            $this->call('ironflow:make:migration', [
                'module' => $module,
                'name' => 'create_' . Str::snake(Str::plural($name)) . '_table',
            ]);
        }

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
