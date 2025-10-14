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
            $this->output->error('Please provide a module name or use --all flag');
            return 1;
        }

        return $this->seedModule($moduleName);
    }

    protected function seedModule(string $moduleName): int
    {
        $module = Anvil::getModule($moduleName);

        if (!$module) {
            $this->output->error("Module {$moduleName} not found!");
            return 1;
        }

        if (!$module instanceof SeedableInterface) {
            $this->output->error("Module {$moduleName} does not implement SeedableInterface!");
            return 1;
        }

        $this->output->info("Seeding module: {$moduleName}");

        try {
            $seederClass = $this->option('class');

            if ($seederClass) {
                $this->line("Running seeder: {$seederClass}");
            }

            $module->seed($seederClass);

            $this->output->info("✓ Module {$moduleName} seeded successfully!");
            return 0;
        } catch (\Exception $e) {
            $this->output->error("Failed to seed module: {$e->getMessage()}");
            return 1;
        }
    }

    protected function seedAllModules(): int
    {
        $modules = Anvil::getModules()->filter(function (BaseModule $module) {
            return $module instanceof SeedableInterface && $module->getMetadata()->isEnabled();
        });

        if ($modules->isEmpty()) {
            $this->output->info('No seedable modules found.');
            return 0;
        }

        // Sort by priority
        $sorted = $modules->sortByDesc(function ($module) {
            return $module->getSeederPriority();
        });

        $this->output->info("Seeding {$sorted->count()} modules...");
        $this->newLine();

        foreach ($sorted as $name => $module) {
            $this->line("Seeding: <comment>{$name}</comment>");

            try {
                $module->seed();
                $this->output->success("  ✓ Success");
            } catch (\Exception $e) {
                $this->output->error("  ✗ Failed: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->output->info('✓ All modules seeded!');

        return 0;
    }
}
