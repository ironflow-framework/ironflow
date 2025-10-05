<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * MakeModelCommand
 */
class MakeModelCommand extends Command
{
    protected $signature = 'ironflow:make:model
                            {name : The name of the model}
                            {module : The module name}
                            {--m|migration : Create a migration for the model}
                            {--f|factory : Create a factory for the model}';

    protected $description = 'Create a new model in a module';

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

        $modulePath = app_path("Modules/{$module}");

        if (!$this->files->exists($modulePath)) {
            $this->error("Module {$module} does not exist!");
            return self::FAILURE;
        }

        $path = $modulePath . "/Models/{$name}.php";

        if ($this->files->exists($path)) {
            $this->error("Model {$name} already exists!");
            return self::FAILURE;
        }

        $stub = $this->getStub();
        $content = str_replace(
            ['{{module}}', '{{name}}', '{{table}}'],
            [$module, $name, Str::snake(Str::pluralStudly($name))],
            $stub
        );

        $this->files->put($path, $content);
        $this->info("Model {$name} created successfully!");

        if ($this->option('migration')) {
            $this->call('ironflow:make:migration', [
                'name' => "create_" . Str::snake(Str::pluralStudly($name)) . "_table",
                'module' => $module,
            ]);
        }

        if ($this->option('factory')) {
            $this->call('ironflow:make:factory', [
                'name' => $name . 'Factory',
                'module' => $module,
            ]);
        }

        return self::SUCCESS;
    }

    protected function getStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Modules\{{module}}\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class {{name}} extends Model
{
    use HasFactory;

    protected $table = '{{table}}';

    protected $fillable = [];

    protected $casts = [];
}
PHP;
    }
}
