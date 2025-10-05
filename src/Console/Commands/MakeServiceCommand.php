<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
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

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $module = $this->argument('module');

        if (!Str::endsWith($name, 'Service')) {
            $name .= 'Service';
        }

        $modulePath = app_path("Modules/{$module}");

        if (!$this->files->exists($modulePath)) {
            $this->error("Module {$module} does not exist!");
            return self::FAILURE;
        }

        $path = $modulePath . "/Services/{$name}.php";

        if ($this->files->exists($path)) {
            $this->error("Service {$name} already exists!");
            return self::FAILURE;
        }

        $stub = $this->getStub();
        $content = str_replace(
            ['{{module}}', '{{name}}'],
            [$module, $name],
            $stub
        );

        $this->files->put($path, $content);
        $this->info("Service {$name} created successfully!");

        return self::SUCCESS;
    }

    protected function getStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Modules\{{module}}\Services;

class {{name}}
{
    public function __construct()
    {
        //
    }

    public function handle()
    {
        //
    }
}
PHP;
    }
}
