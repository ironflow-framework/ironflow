<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Facades\Anvil;

class DiscoverCommand extends Command
{
    protected $signature = 'ironflow:discover';
    protected $description = 'Discover all IronFlow modules';

    public function handle(): int
    {
        $this->info('Discovering modules...');

        try {
            Anvil::discover();

            $modules = Anvil::getModules();
            $count = count($modules);

            $this->output->info("Discovered {$count} module(s):");

            foreach ($modules as $name => $module) {
                $state = $module->getState()->value;
                $this->line("  - {$name} [{$state}]");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->output->error("Discovery failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
