<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * MakeServiceCommand
 */
class MakeServiceCommand extends Command
{
    protected $signature = 'ironflow:make:service
                            {name : The name of the service}
                            {module : The module name}';

    protected $description = 'Create a new service in a module';

    public function handle(): int
    {
         $name = Str::studly($this->argument('name'));
        $module = $this->argument('module');

        $modulePath = config('ironflow.path') . '/' . $module;

        $stub = $this->getStub('service');
        $namespace = config('ironflow.namespace', 'Modules');
        $content = str_replace(
            ['{{namespace}}', '{{name}}'],
            [$namespace, $name],
            $stub
        );
        File::put($modulePath . '/Models/' . $name . '.php', $content);

        $this->info("Service {$name} created successfully!");

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
