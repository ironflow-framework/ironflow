<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * MakeControllerCommand
 */
class MakeModuleControllerCommand extends Command
{
    protected $signature = 'ironflow:make:controller {module : Module name} {name : Controller name}
                            {--resource : Generate a resource controller}';
    protected $description = 'Create a new controller for a module';

    public function handle(): int
    {
        $module = $this->argument('module');
        $name = $this->argument('name');

        $modulePath = config('ironflow.path') . '/' . $module;

        if (!File::isDirectory($modulePath)) {
            $this->error("Module {$module} does not exist!");
            return 1;
        }

        $controllerPath = $modulePath . '/Http/Controllers';
        File::ensureDirectoryExists($controllerPath);

        $stub = $this->getStub('controller');
        $namespace = config('ironflow.namespace', 'Modules');

        $content = str_replace(
            [
                '{{namespace}}',
                '{{module}}',
                '{{name}}',
                '{{lower_module}}',
                '{{lower_name}}',
            ],
            [
                $namespace,
                $module,
                $name,
                Str::lower($module),
                Str::snake(str_replace('Controller', '', $name)),
            ],
            $stub
        );

        $filename = "{$name}.php";
        File::put($controllerPath . '/' . $filename, $content);

        $this->output->info("Controller created: {$module}/Http/Controllers/{$filename}");

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
