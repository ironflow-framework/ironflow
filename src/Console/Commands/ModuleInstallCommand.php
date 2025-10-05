<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * ModuleInstallCommand
 *
 * Install a module from a local path or Composer package
 */
class ModuleInstallCommand extends Command
{
    protected $signature = 'ironflow:module:install
                            {source : Package name or local path}
                            {--local : Install from local path}';

    protected $description = 'Install an IronFlow module';

    public function handle(): int
    {
        $source = $this->argument('source');
        $isLocal = $this->option('local');

        $this->info("Installing module from: {$source}");

        if ($isLocal) {
            return $this->installFromLocal($source);
        }

        return $this->installFromComposer($source);
    }

    protected function installFromComposer(string $package): int
    {
        $this->line("  → Installing via Composer...");

        exec("composer require {$package} 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error("Failed to install package: {$package}");
            foreach ($output as $line) {
                $this->line($line);
            }
            return self::FAILURE;
        }

        $this->info("Package installed successfully!");
        $this->call('ironflow:discover', ['--fresh' => true]);

        return self::SUCCESS;
    }

    protected function installFromLocal(string $path): int
    {
        $this->line("  → Installing from local path...");

        if (!is_dir($path)) {
            $this->error("Path does not exist: {$path}");
            return self::FAILURE;
        }

        // Extract module name from path
        $moduleName = basename($path);
        $targetPath = app_path("Modules/{$moduleName}");

        if (is_dir($targetPath)) {
            if (!$this->confirm("Module {$moduleName} already exists. Overwrite?")) {
                return self::FAILURE;
            }

            File::deleteDirectory($targetPath);
        }

        File::copyDirectory($path, $targetPath);

        $this->info("Module installed successfully!");
        $this->call('ironflow:discover', ['--fresh' => true]);

        return self::SUCCESS;
    }
}
