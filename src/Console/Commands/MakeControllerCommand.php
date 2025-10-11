<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use IronFlow\Facades\Anvil;

/**
 * MakeControllerCommand
 */
class MakeControllerCommand extends Command
{
    protected $signature = 'ironflow:make:controller
                            {name : The name of the controller}
                            {module : The module name}';
    protected $description = 'Create a new controller in a module';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $namespace = $this->argument('module');

        $name = Str::studly($this->argument('name'));
        $namespace = $this->argument('module');

        $modulePath = config('ironflow.path') . '/' . $namespace;

        $stub = $this->getStub('model');
        $namespace = config('ironflow.namespace', 'Modules');

        $controllerName = Str::studly($name);
        $lowerName = Str::lower($name);

        $path = $modulePath . "/Http/Controllers/{$controllerName}.php";

        if ($this->files->exists($path)) {
            $this->error("Controller {$controllerName} already exists!");
            return self::FAILURE;
        }

        $stub = $this->getStub('controller');
        $content = str_replace(
            ['{{namespace}}', '{{name}}', '{{lower_name}}'],
            [$namespace, $controllerName, $lowerName],
            $stub
        );

        $this->files->put($path, $content);
        $this->info("Controller {$controllerName} created successfully!");

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
