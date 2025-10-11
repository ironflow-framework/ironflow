<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Facades\Anvil;
use IronFlow\Contracts\SeedableInterface;
use IronFlow\Core\BaseModule;

/**
 * SeedCommand
 */
class SeedModuleCommand extends Command
{
    protected $signature = 'ironflow:module:seed {module? : Module name}
                            {--class= : Specific seeder class}
                            {--all : Seed all modules}
                            {--force : Force seed in production}';
    protected $description = 'Seed database for IronFlow modules';

    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->seedAllModules();
        }

        $moduleName = $this->argument('module');

        if (!$moduleName) {
            $this->error('Please provide a module name or use --all flag');
            return 1;
        }

        return $this->seedModule($moduleName);
    }

    protected function seedModule(string $moduleName): int
    {
        $module = Anvil::getModule($moduleName);

        if (!$module) {
            $this->error("Module {$moduleName} not found!");
            return 1;
        }

        if (!$module instanceof SeedableInterface) {
            $this->error("Module {$moduleName} does not implement SeedableInterface!");
            return 1;
        }

        $this->info("Seeding module: {$moduleName}");

        try {
            $seederClass = $this->option('class');

            if ($seederClass) {
                $this->line("Running seeder: {$seederClass}");
            }

            $module->seed($seederClass);

            $this->info("✓ Module {$moduleName} seeded successfully!");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to seed module: {$e->getMessage()}");
            return 1;
        }
    }

    protected function seedAllModules(): int
    {
        $modules = Anvil::getModules()->filter(function (BaseModule $module) {
            return $module instanceof SeedableInterface && $module->getMetadata()->isEnabled();
        });

        if ($modules->isEmpty()) {
            $this->info('No seedable modules found.');
            return 0;
        }

        // Sort by priority
        $sorted = $modules->sortByDesc(function ($module) {
            return $module->getSeederPriority();
        });

        $this->info("Seeding {$sorted->count()} modules...");
        $this->newLine();

        foreach ($sorted as $name => $module) {
            $this->line("Seeding: <comment>{$name}</comment>");

            try {
                $module->seed();
                $this->info("  ✓ Success");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('✓ All modules seeded!');

        return 0;
    }
}
