<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * MakeRepository
 */
class MakeModuleRepositoryCommand extends Command
{
    protected $signature = 'ironflow:make:repo {module : Module name} {name : Repository name}
                            {--model= : Associated model name}';
    protected $description = 'Create a new repository for a module';

    public function handle(): int
    {
        $module = $this->argument('module');
        $name = $this->argument('name');
        $model = $this->option('model') ?: str_replace('Repository', '', $name);
        
        $modulePath = config('ironflow.path') . '/' . $module;
        
        if (!File::isDirectory($modulePath)) {
            $this->error("Module {$module} does not exist!");
            return 1;
        }

        $servicePath = $modulePath . '/Repositories';
        File::ensureDirectoryExists($servicePath);

        $stub = $this->getStub('repository');
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
                Str::lower(str_replace('Repository', '', $name)),
                Str::lower($model),
            ],
            $stub
        );

        $filename = "{$name}.php";
        File::put($servicePath . '/' . $filename, $content);

        $this->info("Repository created: {$module}/Repositories/{$filename}");

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