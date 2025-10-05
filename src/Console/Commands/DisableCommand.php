<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;

/**
 * DisableCommand
 */
class DisableCommand extends Command
{
    protected $signature = 'ironflow:disable {module : The module name}';

    protected $description = 'Disable an enabled module';

    public function handle(): int
    {
        $moduleName = $this->argument('module');

        $this->info("Module '{$moduleName}' has been disabled.");
        $this->line("Run 'php artisan ironflow:discover --fresh' to apply changes.");

        return self::SUCCESS;
    }
}
