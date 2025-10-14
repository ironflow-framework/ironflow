<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Facades\Anvil;

/**
 * EnableModuleCommand
 */
class EnableModuleCommand extends Command
{
    protected $signature = 'ironflow:module:enable {name : The name of the module}';
    protected $description = 'Enable an IronFlow module';

    public function handle(): int
    {
        $name = $this->argument('name');

        try {
            Anvil::enable($name);
            $this->output->success("Module {$name} enabled successfully!");
            return 0;
        } catch (\Exception $e) {
            $this->output->error("Failed to enable module: {$e->getMessage()}");
            return 1;
        }
    }
}
