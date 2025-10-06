<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

/**
 * ModuleCreateCommand
 *
 * Creates a new IronFlow module with standard structure
 */
class ModuleCreateCommand extends Command
{
    protected $signature = 'ironflow:module:create
                            {name : The name of the module}
                            {--path=app/Modules : The base path for modules}
                            {--author= : Module author name}
                            {--description= : Module description}';

    protected $description = 'Create a new IronFlow module';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $path = $this->option('path');
        $author = $this->option('author') ?? config('app.name', 'Unknown');
        $description = $this->option('description') ?? "The {$name} module";

        $modulePath = base_path($path . '/' . $name);

        if ($this->files->exists($modulePath)) {
            $this->error("Module {$name} already exists!");
            return self::FAILURE;
        }

        $this->info("Creating module: {$name}");

        // Create directory structure
        $this->createDirectoryStructure($modulePath);

        // Generate module files
        $this->generateModuleClass($modulePath, $name, $author, $description);
        $this->generateServiceProvider($modulePath, $name);
        $this->generateRoutes($modulePath, $name);
        $this->generateController($modulePath, $name);
        $this->generateModel($modulePath, $name);
        $this->generateComposerJson($modulePath, $name, $description, $author);

        $this->info("Module {$name} created successfully!");
        $this->info("Location: {$modulePath}");
        $this->newLine();
        $this->info("Next steps:");
        $this->line("  1. Run: php artisan ironflow:discover");
        $this->line("  2. Start building your module!");

        return self::SUCCESS;
    }

    protected function createDirectoryStructure(string $path): void
    {
        $directories = [
            'Http/Controllers',
            'Models',
            'Services',
            'Routes',
            'Database/Migrations',
            'Database/Seeders',
            'Database/Factories',
            'Providers',
            'Resources/views',
            'Tests/Feature',
            'Tests/Unit',
            'config',
        ];

        foreach ($directories as $directory) {
            $this->files->makeDirectory($path . '/' . $directory, 0755, true);
        }
    }

    protected function generateModuleClass(string $path, string $name, string $author, string $description): void
    {
        $stub = $this->getStub('module');
        $content = str_replace(
            ['{{name}}', '{{slug}}', '{{description}}', '{{author}}'],
            [$name, Str::slug($name), $description, $author],
            $stub
        );

        $this->files->put($path . '/' . $name . 'Module.php', $content);
    }

    protected function generateServiceProvider(string $path, string $name): void
    {
        $stub = $this->getStub('provider');
        $content = str_replace(['{{name}}'], [$name], $stub);

        $this->files->put($path . '/Providers/' . $name . 'ServiceProvider.php', $content);
    }

    protected function generateRoutes(string $path, string $name): void
    {
        $webStub = $this->getStub('routes-web');
        $apiStub = $this->getStub('routes-api');

        $this->files->put($path . '/Routes/web.php', str_replace('{{name}}', $name, $webStub));
        $this->files->put($path . '/Routes/api.php', str_replace('{{name}}', $name, $apiStub));
    }

    protected function generateController(string $path, string $name): void
    {
        $stub = $this->getStub('controller');
        $content = str_replace(['{{name}}'], [$name], $stub);

        $this->files->put($path . '/Http/Controllers/' . $name . 'Controller.php', $content);
    }

    protected function generateModel(string $path, string $name): void
    {
        $stub = $this->getStub('model');
        $content = str_replace(['{{name}}'], [$name], $stub);

        $this->files->put($path . '/Models/' . $name . '.php', $content);
    }

    protected function generateComposerJson(string $path, string $name, string $description, string $author): void
    {
        $slug = Str::slug($name);
        $content = json_encode([
            'name' => "modules/{$slug}",
            'description' => $description,
            'type' => 'ironflow-module',
            'authors' => [
                ['name' => $author]
            ],
            'require' => [
                'php' => '^8.3',
                'ironflow/ironflow' => '^1.0'
            ],
            'autoload' => [
                'psr-4' => [
                    "App\\Modules\\{$name}\\" => ''
                ]
            ],
            'extra' => [
                'ironflow' => [
                    'module' => "App\\Modules\\{$name}\\{$name}Module"
                ]
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->files->put($path . '/composer.json', $content);
    }

    protected function getStub(string $name): string
    {
        // In production, these would be actual stub files
        return match($name) {
            'module' => $this->getModuleStub(),
            'provider' => $this->getProviderStub(),
            'controller' => $this->getControllerStub(),
            'model' => $this->getModelStub(),
            'routes-web' => $this->getWebRoutesStub(),
            'routes-api' => $this->getApiRoutesStub(),
            default => '',
        };
    }

    protected function getModuleStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Modules\{{name}};

use IronFlow\Core\BaseModule;
use IronFlow\Core\ModuleMetadata;
use IronFlow\Contracts\RoutableInterface;
use Illuminate\Support\Facades\Route;

class {{name}}Module extends BaseModule implements RoutableInterface
{
    public function metadata(): ModuleMetadata
    {
        return new ModuleMetadata(
            name: '{{name}}',
            version: '1.0.0',
            description: '{{description}}',
            authors: ['{{author}}'],
            dependencies: [],
            enabled: true,
        );
    }

    public function register(): void
    {
        parent::register();

        // Register module services here
    }

    public function expose(): array
    {
        return [
            'public' => [],
            'internal' => []
        ];
    }

    public function boot(): void
    {
        parent::boot();

        // Boot module here
    }

    public function registerRoutes(): void
    {
        $this->loadRoutesFrom($this->path('Routes/web.php'));
        $this->loadApiRoutesFrom($this->path('Routes/api.php'));
    }

    public function routePrefix(): ?string
    {
        return '{{slug}}';
    }

    public function routeMiddleware(): array
    {
        return ['web'];
    }
}
PHP;
    }

    protected function getProviderStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Modules\{{name}}\Providers;

use Illuminate\Support\ServiceProvider;

class {{name}}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
PHP;
    }

    protected function getControllerStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Modules\{{name}}\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class {{name}}Controller extends Controller
{
    public function index()
    {
        return view('{{slug}}::index');
    }
}
PHP;
    }

    protected function getModelStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Modules\{{name}}\Models;

use Illuminate\Database\Eloquent\Model;

class {{name}} extends Model
{
    protected $fillable = [];
}
PHP;
    }

    protected function getWebRoutesStub(): string
    {
        return <<<'PHP'
<?php

use App\Modules\{{name}}\Controllers\{{name}}Controller;
use Illuminate\Support\Facades\Route;

Route::get('/', [{{name}}Controller::class, 'index'])->name('index');
PHP;
    }

    protected function getApiRoutesStub(): string
    {
        return <<<'PHP'
<?php

use App\Modules\{{name}}\Controllers\{{name}}Controller;
use Illuminate\Support\Facades\Route;

// API routes for {{name}} module
Route::get('/', [{{name}}Controller::class, 'index']);
PHP;
    }
}
