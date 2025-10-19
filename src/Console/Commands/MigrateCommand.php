<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Facades\Anvil;

class MigrateCommand extends Command
{
    protected $signature = 'ironflow:migrate {module? : The module name}
                            {--rollback : Rollback migrations}';

    protected $description = 'Run migrations for IronFlow modules';

    public function handle(): int
    {
        $moduleName = $this->argument('module');
        $rollback = $this->option('rollback');

        if ($moduleName) {
            return $this->migrateModule($moduleName, $rollback);
        }

        return $this->migrateAll($rollback);
    }

    protected function migrateModule(string $moduleName, bool $rollback): int
    {
        $module = Anvil::getModule($moduleName);

        if (!$module) {
            $this->output->error("Module {$moduleName} not found");
            return self::FAILURE;
        }

        if (!$module instanceof \IronFlow\Contracts\MigratableInterface) {
            $this->output->error("Module {$moduleName} is not migratable");
            return self::FAILURE;
        }

        try {
            if ($rollback) {
                $this->info("Rolling back migrations for {$moduleName}...");
                $module->rollbackMigrations();
                $this->info("Migrations rolled back successfully");
            } else {
                $this->info("Running migrations for {$moduleName}...");
                $module->runMigrations();
                $this->info("Migrations completed successfully");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->output->error("Migration failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    protected function migrateAll(bool $rollback): int
    {
        $modules = Anvil::getModules();
        $migratable = array_filter($modules, fn($m) => $m instanceof \IronFlow\Contracts\MigratableInterface);

        if (empty($migratable)) {
            $this->warn('No migratable modules found');
            return self::SUCCESS;
        }

        $action = $rollback ? 'Rolling back' : 'Running';
        $this->info("{$action} migrations for all modules...");

        foreach ($migratable as $name => $module) {
            try {
                if ($rollback) {
                    $module->rollbackMigrations();
                } else {
                    $module->runMigrations();
                }
                $this->line("  ✓ {$name}");
            } catch (\Exception $e) {
                $this->line("  ✗ {$name}: {$e->getMessage()}");
            }
        }

        $this->output->info('Done');
        return self::SUCCESS;
    }
}

