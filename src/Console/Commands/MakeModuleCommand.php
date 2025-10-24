<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'ironflow:module:make {name : The name of the module}
                            {--path= : Custom path for the module}
                            {--namespace= : Custom namespace}
                            {--author= : Module author}
                            {--description= : Module description}';

    protected $description = 'Create a new IronFlow module';

    public function handle(): int
    {
        $name = $this->argument('name');
        $studlyName = Str::studly($name);

        $basePath = $this->option('path') ?: base_path('modules/' . $studlyName);
        $namespace = $this->option('namespace') ?: "Modules\\{$studlyName}";

        if (File::exists($basePath)) {
            $this->output->error("Module {$name} already exists at {$basePath}");
            return self::FAILURE;
        }

        $this->info("Creating module: {$studlyName}");

        // Create directory structure
        $this->createDirectories($basePath);

        // Generate files
        $this->generateModuleClass($basePath, $studlyName, $namespace);
        $this->generateServiceProvider($basePath, $studlyName, $namespace);
        $this->generateController($basePath, $studlyName, $namespace);
        $this->generateModel($basePath, $studlyName, $namespace);
        $this->generateService($basePath, $studlyName, $namespace);
        $this->generateConfig($basePath, $studlyName);
        $this->generateRoutes($basePath, $studlyName, $namespace);
        $this->generateViews($basePath, $studlyName);

        $this->output->info("Module {$studlyName} created successfully at {$basePath}");
        $this->newLine();
        $this->info("Next steps:");
        $this->line("  1. Run: composer dump-autoload");
        $this->line("  2. Run: php artisan ironflow:discover");

        return self::SUCCESS;
    }

    protected function createDirectories(string $basePath): void
    {
        $directories = [
            'Http/Controllers',
            'Models',
            'Services',
            'Resources/views',
            'Resources/lang/en',
            'Database/Migrations',
            'Database/Seeders',
            'routes',
            'config',
        ];

        foreach ($directories as $directory) {
            File::makeDirectory($basePath . '/' . $directory, 0755, true);
        }
    }

    protected function generateModuleClass(string $basePath, string $name, string $namespace): void
    {
        $stub = $this->getStub('module');
        $content = str_replace(
            ['{{namespace}}', '{{name}}', '{{author}}', '{{description}}'],
            [
                $namespace,
                $name,
                $this->option('author') ?: 'Your Name',
                $this->option('description') ?: "The {$name} module",
            ],
            $stub
        );

        File::put($basePath . "/{$name}Module.php", $content);
    }

    protected function generateServiceProvider(string $basePath, string $name, string $namespace): void
    {
        $stub = $this->getStub('module-service-provider');
        $content = str_replace(
            ['{{namespace}}', '{{name}}'],
            [$namespace, $name],
            $stub
        );

        File::put($basePath . "/{$name}ServiceProvider.php", $content);
    }

    protected function generateController(string $basePath, string $name, string $namespace): void
    {
        $stub = $this->getStub('controller');
        $content = str_replace(
            ['{{namespace}}', '{{name}}'],
            [$namespace, $name],
            $stub
        );

        File::put($basePath . "/Http/Controllers/{$name}Controller.php", $content);
    }

    protected function generateModel(string $basePath, string $name, string $namespace): void
    {
        $stub = $this->getStub('model');
        $modelName = Str::singular($name);
        $content = str_replace(
            ['{{namespace}}', '{{name}}'],
            [$namespace, $modelName],
            $stub
        );

        File::put($basePath . "/Models/{$modelName}.php", $content);
    }

    protected function generateService(string $basePath, string $name, string $namespace): void
    {
        $stub = $this->getStub('service');
        $content = str_replace(
            ['{{namespace}}', '{{name}}'],
            [$namespace, $name],
            $stub
        );

        File::put($basePath . "/Services/{$name}Service.php", $content);
    }

    protected function generateConfig(string $basePath, string $name): void
    {
        $stub = $this->getStub('config');
        $content = str_replace('{{name}}', strtolower($name), $stub);

        File::put($basePath . "/config/" . strtolower($name) . ".php", $content);
    }

    protected function generateRoutes(string $basePath, string $name, string $namespace): void
    {
        $stub = $this->getStub('routes');
        $content = str_replace(
            ['{{namespace}}', '{{name}}'],
            [$namespace, $name],
            $stub
        );

        File::put($basePath . "/routes/web.php", $content);
    }

    protected function generateViews(string $basePath, string $name): void
    {
        $stub = $this->getStub('view');
        $content = str_replace('{{name}}', $name, $stub);

        File::put($basePath . "/Resources/views/index.blade.php", $content);
    }

    protected function getStub(string $name): string
    {
        $stubPath = __DIR__ . '/../Stubs/' . $name . '.stub';

        if (!File::exists($stubPath)) {
            // Fallback to default stubs
            return $this->getDefaultStub($name);
        }

        return File::get($stubPath);
    }

    protected function getDefaultStub(string $name): string
    {
        // Return default stub content based on type
        return match ($name) {
            'module' => $this->getModuleStub(),
            'module-service-provider' => $this->getServiceProviderStub(),
            'controller' => $this->getControllerStub(),
            'model' => $this->getModelStub(),
            'service' => $this->getServiceStub(),
            'config' => $this->getConfigStub(),
            'routes' => $this->getRoutesStub(),
            'view' => $this->getViewStub(),
            default => ''
        };
    }

    // Stub content methods...
    protected function getModuleStub(): string
    {
        return <<<'PHP'
<?php

namespace {{namespace}};

use IronFlow\Core\{BaseModule, ModuleMetaData};
use IronFlow\Interfaces\{ViewableInterface, RoutableInterface, ConfigurableInterface, MigratableInterface};

class {{name}}Module extends BaseModule implements
    ViewableInterface,
    RoutableInterface,
    ConfigurableInterface,
    MigratableInterface
{
    protected function defineMetadata(): ModuleMetaData
    {
        return new ModuleMetaData(
            name: '{{name}}',
            version: '1.0.0',
            description: '{{description}}',
            author: '{{author}}',
            dependencies: [],
            provides: ['{{name}}Service'],
            path: __DIR__,
            namespace: __NAMESPACE__,
        );
    }

    public function bootModule(): void
    {
        // Boot logic here
    }

    public function expose(): array
    {
        return [
            '{{name}}Service' => Services\{{name}}Service::class,
        ];
    }

    public function registerViews(): void
    {
        $this->app['view']->addNamespace(
            $this->getViewNamespace(),
            $this->getViewsPath()
        );
    }

    public function getViewsPath(): string
    {
        return $this->getPath('Resources/views');
    }

    public function getViewNamespace(): string
    {
        return strtolower($this->getName());
    }

    public function registerRoutes(): void
    {
        require $this->getRoutesPath();
    }

    public function getRoutesPath(): string
    {
        return $this->getPath('routes/web.php');
    }

    public function getRouteMiddleware(): array
    {
        return ['web'];
    }

    public function getConfigPath(): string
    {
        return $this->getPath('config/' . strtolower($this->getName()) . '.php');
    }

    public function getConfigKey(): string
    {
        return strtolower($this->getName());
    }

    public function publishConfig(): void
    {
        // Handled by ModuleServiceProvider
    }

    public function getMigrationsPath(): string
    {
        return $this->getPath('Database/Migrations');
    }

    public function runMigrations(): void
    {
        // Handled by MigratorManager
    }

    public function rollbackMigrations(): void
    {
        // Handled by MigratorManager
    }
}
PHP;
    }

    protected function getServiceProviderStub(): string
    {
        return <<<'PHP'
<?php

namespace {{namespace}};

use IronFlow\Core\ModuleServiceProvider;

class {{name}}ServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        // Additional registrations
    }

    public function boot(): void
    {
        parent::boot();

        // Additional boot logic
    }
}
PHP;
    }

    protected function getControllerStub(): string
    {
        return <<<'PHP'
<?php

namespace {{namespace}}\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class {{name}}Controller extends Controller
{
    public function index()
    {
        return view('{{name}}::index');
    }

    public function show($id)
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
PHP;
    }

    protected function getModelStub(): string
    {
        return <<<'PHP'
<?php

namespace {{namespace}}\Models;

use Illuminate\Database\Eloquent\Model;

class {{name}} extends Model
{
    protected $fillable = [];

    protected $casts = [];
}
PHP;
    }

    protected function getServiceStub(): string
    {
        return <<<'PHP'
<?php

namespace {{namespace}}\Services;

class {{name}}Service
{
    public function __construct()
    {
        //
    }

    // Add your service methods here
}
PHP;
    }

    protected function getConfigStub(): string
    {
        return <<<'PHP'
<?php

return [
    'enabled' => true,

    // Add your configuration options here
];
PHP;
    }

    protected function getRoutesStub(): string
    {
        return <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use {{namespace}}\Http\Controllers\{{name}}Controller;

Route::middleware(['web'])->group(function () {
    Route::get('/{{name}}', [{{name}}Controller::class, 'index'])->name('{{name}}.index');
    Route::get('/{{name}}/{id}', [{{name}}Controller::class, 'show'])->name('{{name}}.show');
});
PHP;
    }

    protected function getViewStub(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>{{name}} Module</title>
</head>
<body>
    <h1>Welcome to {{name}} Module</h1>
    <p>This is the default view for the {{name}} module.</p>
</body>
</html>
HTML;
    }
}
