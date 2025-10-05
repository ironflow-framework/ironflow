<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Core\Anvil;
use Illuminate\Support\Facades\File;

/**
 * DiscoverCommand
 */
class DiscoverCommand extends Command
{
    protected $signature = 'ironflow:discover
                            {--fresh : Clear the cache before discovering}';

    protected $description = 'Discover and register all IronFlow modules';

    public function handle(Anvil $anvil): int
    {
        if ($this->option('fresh')) {
            $this->call('ironflow:cache:clear');
        }

        $this->info('Discovering IronFlow modules...');
        $this->newLine();

        $modulesPath = app_path('Modules');

        if (!File::exists($modulesPath)) {
            $this->warn('No modules directory found. Creating one...');
            File::makeDirectory($modulesPath, 0755, true);
            $this->info('Modules directory created at: ' . $modulesPath);
            return self::SUCCESS;
        }

        $modules = File::directories($modulesPath);
        $discovered = 0;

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $moduleClass = "App\\Modules\\{$moduleName}\\{$moduleName}Module";

            if (!class_exists($moduleClass)) {
                $this->warn("  ⚠ {$moduleName}: Module class not found");
                continue;
            }

            try {
                $module = app($moduleClass);
                $metadata = $module->metadata();

                $anvil->register($module);

                $this->line("  ✓ {$metadata->name} (v{$metadata->version})");
                if ($metadata->description) {
                    $this->line("    {$metadata->description}");
                }

                $discovered++;
            } catch (\Exception $e) {
                $this->error("  ✗ {$moduleName}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Discovered {$discovered} module(s)");

        // Cache the discovery results
        $this->call('ironflow:cache:modules');

        return self::SUCCESS;
    }
}
