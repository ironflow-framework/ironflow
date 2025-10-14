<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * MakeServiceCommand
 */
class MakeModuleServiceCommand extends Command
{
    protected $signature = 'ironflow:make:service {module : Module name} {name : Service name}
                            {--model= : Associated model name}';
    protected $description = 'Create a new service for a module';

    public function handle(): int
    {
        $module = $this->argument('module');
        $name = $this->argument('name');
        $model = $this->option('model') ?: str_replace('Service', '', $name);

        $modulePath = config('ironflow.path') . '/' . $module;

        if (!File::isDirectory($modulePath)) {
            $this->error("Module {$module} does not exist!");
            return 1;
        }

        $servicePath = $modulePath . '/Services';
        File::ensureDirectoryExists($servicePath);

        $stub = $this->getStub('service');
        $namespace = config('ironflow.namespace', 'Modules');

        $content = str_replace(
            [
                '{{namespace}}',
                '{{module}}',
                '{{name}}',
                '{{model}}',
                '{{lower_name}}',
                '{{lower_model}}',
            ],
            [
                $namespace,
                $module,
                $name,
                $model,
                Str::lower(str_replace('Service', '', $name)),
                Str::lower($model),
            ],
            $stub
        );

        $filename = "{$name}.php";
        File::put($servicePath . '/' . $filename, $content);

        $this->output->info("Service created: {$module}/Services/{$filename}");

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
