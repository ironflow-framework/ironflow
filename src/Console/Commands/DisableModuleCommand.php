<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Facades\Anvil;

/**
 * DisableModuleCommand
 */
class DisableModuleCommand extends Command
{
    protected $signature = 'ironflow:module:disable {name : The name of the module}';
    protected $description = 'Disable an IronFlow module';

    public function handle(): int
    {
        $name = $this->argument('name');

        try {
            Anvil::disable($name);
            $this->output->info("Module {$name} disabled successfully!");
            return 0;
        } catch (\Exception $e) {
            $this->output->error("Failed to disable module: {$e->getMessage()}");
            return 1;
        }
    }
}
