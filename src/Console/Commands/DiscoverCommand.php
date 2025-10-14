<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Facades\Anvil;

/**
 * DiscoverCommand
 */
class DiscoverCommand extends Command
{
    protected $signature = 'ironflow:discover
                            {--fresh : Clear the cache before discovering}';

    protected $description = 'Discover and register all IronFlow modules';

    public function handle(): int
    {

        if ($this->option('fresh')) {
            $this->call('ironflow:cache:clear');
        }
        
        $this->output->info('Discovering IronFlow modules...');

        Anvil::discover();
        $stats = Anvil::getStatistics();

        foreach ($stats as $key => $value) {
            $this->line("    {$key} : {$value}");
        }

        // Cache the discovery results
        $this->call('ironflow:cache:modules');

        return self::SUCCESS;
    }
}
