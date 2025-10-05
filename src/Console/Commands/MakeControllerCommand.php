<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * MakeControllerCommand
 */
class MakeControllerCommand extends Command
{
    protected $signature = 'ironflow:make:controller
                            {name : The name of the controller}
                            {module : The module name}
                            {--resource : Generate a resource controller}';

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
        $module = $this->argument('module');
        $resource = $this->option('resource');

        $modulePath = app_path("Modules/{$module}");

        if (!$this->files->exists($modulePath)) {
            $this->error("Module {$module} does not exist!");
            return self::FAILURE;
        }

        $controllerName = Str::studly($name);
        if (!Str::endsWith($controllerName, 'Controller')) {
            $controllerName .= 'Controller';
        }

        $path = $modulePath . "/Http/Controllers/{$controllerName}.php";

        if ($this->files->exists($path)) {
            $this->error("Controller {$controllerName} already exists!");
            return self::FAILURE;
        }

        $stub = $resource ? $this->getResourceStub() : $this->getStub();
        $content = str_replace(
            ['{{module}}', '{{name}}'],
            [$module, $controllerName],
            $stub
        );

        $this->files->put($path, $content);
        $this->info("Controller {$controllerName} created successfully!");

        return self::SUCCESS;
    }

    protected function getStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Modules\Http\{{module}}\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class {{name}} extends Controller
{
    public function __invoke(Request $request)
    {
        //
    }
}
PHP;
    }

    protected function getResourceStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Modules\{{module}}\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class {{name}} extends Controller
{
    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
PHP;
    }
}
