<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * MakeRouteCommand
 */
class MakeModuleRouteCommand extends Command
{
    protected $signature = 'ironflow:make:route {module : Module name} {name : Route name}';
    protected $description = 'Create a new route for a module';

    public function handle(): int
    {
        $module = $this->argument('module');
        $name = $this->argument('name');
        
        $modulePath = config('ironflow.path') . '/' . $module;
        
        if (!File::isDirectory($modulePath)) {
            $this->error("Module {$module} does not exist!");
            return 1;
        }

        $routePath = $modulePath . '/Routes';
        File::ensureDirectoryExists($routePath);

        $stub = $this->getStub('route');
        $namespace = config('ironflow.namespace', 'Modules');

        $content = str_replace(
            [
                '{{namespace}}',
                '{{name}}',
                '{{lower_name}}',
            ],
            [
                $namespace,
                $name,
                Str::lower($module),
            ],
            $stub
        );

        $filename = "{$name}.php";
        File::put($routePath . '/' . $filename, $content);

        $this->info("Route created: {$module}/Routes/{$filename}");

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