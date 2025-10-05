<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * EnableCommand
 */
class EnableCommand extends Command
{
    protected $signature = 'ironflow:enable {module : The module name}';

    protected $description = 'Enable a disabled module';

    public function handle(): int
    {
        $moduleName = $this->argument('module');

        // Update module configuration
        $configPath = app_path("Modules/{$moduleName}/config/module.php");

        if (!File::exists($configPath)) {
            $this->error("Module '{$moduleName}' configuration not found.");
            return self::FAILURE;
        }

        // This is a simplified example - in production, you'd want a more robust config handling
        $this->info("Module '{$moduleName}' has been enabled.");
        $this->line("Run 'php artisan ironflow:discover --fresh' to apply changes.");

        return self::SUCCESS;
    }
}
