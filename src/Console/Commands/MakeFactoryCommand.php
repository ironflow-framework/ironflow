<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * MakeFactoryCommand
 */
class MakeFactoryCommand extends Command
{
    protected $signature = 'ironflow:make:factory
                            {name : The name of the factory}
                            {module : The module name}
                            {--model= : The model the factory is for}';

    protected $description = 'Create a new factory in a module';

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
        $model = $this->option('model') ?? str_replace('Factory', '', $name);

        if (!Str::endsWith($name, 'Factory')) {
            $name .= 'Factory';
        }

        $modulePath = app_path("Modules/{$module}");

        if (!$this->files->exists($modulePath)) {
            $this->error("Module {$module} does not exist!");
            return self::FAILURE;
        }

        $path = $modulePath . "/Database/factories/{$name}.php";

        if ($this->files->exists($path)) {
            $this->error("Factory {$name} already exists!");
            return self::FAILURE;
        }

        $stub = $this->getStub();
        $content = str_replace(
            ['{{module}}', '{{name}}', '{{model}}'],
            [$module, $name, $model],
            $stub
        );

        $this->files->put($path, $content);
        $this->info("Factory {$name} created successfully!");

        return self::SUCCESS;
    }

    protected function getStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Modules\{{module}}\Database\Factories;

use App\Modules\{{module}}\Models\{{model}};
use Illuminate\Database\Eloquent\Factories\Factory;

class {{name}} extends Factory
{
    protected $model = {{model}}::class;

    public function definition(): array
    {
        return [
            //
        ];
    }
}
PHP;
    }
}
