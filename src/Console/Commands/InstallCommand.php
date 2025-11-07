<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'ironflow:install';
    protected $description = 'Install IronFlow framework';

    public function handle(): int
    {
        $this->info('Installing IronFlow...');

        // Create modules directory
        $modulesPath = base_path('modules');
        if (!File::isDirectory($modulesPath)) {
            File::makeDirectory($modulesPath, 0755, true);
            $this->output->info('Created modules directory');
        }

        // Publish configuration
        $this->call('vendor:publish', [
            '--tag' => 'ironflow-config',
            '--force' => false,
        ]);

        $this->newLine();
        $this->output->info('IronFlow installed successfully!');
        $this->newLine();
        $this->info('Next steps:');
        $this->line('  1. Create your first module: php artisan ironflow:module:make MyModule');
        $this->line('  2. Discover modules: php artisan ironflow:discover');
        $this->line('  3. Cache modules (production): php artisan ironflow:cache');

        return self::SUCCESS;
    }
}
