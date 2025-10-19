<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Facades\Anvil;

class ClearCommand extends Command
{
    protected $signature = 'ironflow:clear';
    protected $description = 'Clear the module cache';

    public function handle(): int
    {
        $this->info('Clearing module cache...');

        try {
            Anvil::clearCache();
            $this->output->info('Module cache cleared successfully');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->output->error("Clear failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
