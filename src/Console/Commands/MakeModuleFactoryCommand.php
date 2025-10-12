<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * MakeFactoryCommand
 */
class MakeModuleFactoryCommand extends Command
{
    protected $signature = 'ironflow:make:factory {module : Module name} {name : Factory name}
                            {--model= : Associated model name}';
    protected $description = 'Create a new factory for a module';

    public function handle(): int
    {
        $module = $this->argument('module');
        $name = $this->argument('name');
        $model = $this->option('model') ?: str_replace('Factory', '', $name);
        
        $modulePath = config('ironflow.path') . '/' . $module;
        
        if (!File::isDirectory($modulePath)) {
            $this->error("Module {$module} does not exist!");
            return 1;
        }

        $factoryPath = $modulePath . 'Database/Factories';
        File::ensureDirectoryExists($factoryPath);

        $stub = $this->getStub('factory');
        $namespace = config('ironflow.namespace', 'Modules');

        $content = str_replace(
            [
                '{{namespace}}',
                '{{module}}',
                '{{name}}',
                '{{model}}',
            ],
            [
                $namespace,
                $module,
                $name,
                $model,
            ],
            $stub
        );

        $filename = "{$name}.php";
        File::put($factoryPath . '/' . $filename, $content);

        $this->info("Factory created: {$module}/Factories/{$filename}");

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