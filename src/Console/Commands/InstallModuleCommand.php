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
class InstallModuleCommand extends Command
{
    protected $signature = 'ironflow:module:install
                            {source : Package name or local path}
                            {--local : Install from local path}';

    protected $description = 'Install an IronFlow module';

    public function handle(): int
    {
        $source = $this->argument('source');
        $isLocal = $this->option('local');

        $this->output->info("Installing module from: {$source}");

        if ($isLocal) {
            return $this->installFromLocal($source);
        }

        return $this->installFromComposer($source);
    }

    protected function installFromComposer(string $package): int
    {
        $this->output->writeln("  → Installing via Composer...");

        exec("composer require {$package} 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            $this->output->error("Failed to install package: {$package}");
            foreach ($output as $line) {
                $this->line($line);
            }
            return self::FAILURE;
        }

        $this->output->info("Package installed successfully!");
        $this->call('ironflow:discover', ['--fresh' => true]);

        return self::SUCCESS;
    }

    protected function installFromLocal(string $path): int
    {
        $this->output->writeln("  → Installing from local path...");

        if (!is_dir($path)) {
            $this->output->error("Path does not exist: {$path}");
            return self::FAILURE;
        }

        // Extract module name from path
        $moduleName = basename($path);
        $modulesPath = config('ironflow.path');
        $targetPath = $modulesPath . "/{$moduleName}";

        if (is_dir($targetPath)) {
            if (!$this->output->confirm("Module {$moduleName} already exists. Overwrite?")) {
                return self::FAILURE;
            }

            File::deleteDirectory($targetPath);
        }

        File::copyDirectory($path, $targetPath);

        $this->output->info("Module installed successfully!");
        $this->call('ironflow:discover', ['--fresh' => true]);

        return self::SUCCESS;
    }
}
