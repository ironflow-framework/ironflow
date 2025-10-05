<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * MakeMigrationCommand
 */
class MakeMigrationCommand extends Command
{
    protected $signature = 'ironflow:make:migration
                            {name : The name of the migration}
                            {module : The module name}
                            {--create= : The table to be created}
                            {--table= : The table to migrate}';

    protected $description = 'Create a new migration in a module';

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
        $create = $this->option('create');
        $table = $this->option('table');

        $modulePath = app_path("Modules/{$module}");

        if (!$this->files->exists($modulePath)) {
            $this->error("Module {$module} does not exist!");
            return self::FAILURE;
        }

        $migrationPath = $modulePath . "/Database/migrations";
        $this->files->ensureDirectoryExists($migrationPath);

        $fileName = date('Y_m_d_His') . '_' . $name . '.php';
        $path = $migrationPath . '/' . $fileName;

        $stub = $create ? $this->getCreateStub() : $this->getStub();
        $tableName = $create ?? $table ?? 'table';
        $className = Str::studly($name);

        $content = str_replace(
            ['{{class}}', '{{table}}'],
            [$className, $tableName],
            $stub
        );

        $this->files->put($path, $content);
        $this->info("Migration {$fileName} created successfully!");

        return self::SUCCESS;
    }

    protected function getCreateStub(): string
    {
        return <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{{table}}', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{{table}}');
    }
};
PHP;
    }

    protected function getStub(): string
    {
        return <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('{{table}}', function (Blueprint $table) {
            //
        });
    }

    public function down(): void
    {
        Schema::table('{{table}}', function (Blueprint $table) {
            //
        });
    }
};
PHP;
    }
}
