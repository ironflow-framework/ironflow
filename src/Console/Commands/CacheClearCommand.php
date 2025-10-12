<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use IronFlow\Facades\Anvil;

/**
 * CacheClearCommand
 */
class CacheClearCommand extends Command
{
    protected $signature = 'ironflow:cache:clear';

    protected $description = 'Clear the IronFlow module cache';

    public function handle(): int
    {

        if (config('ironflow.cache.enabled', true)) {

            Anvil::clearCache();

            $this->info('IronFlow cache cleared successfully!');

        } else {
            $this->info('No cache to clear.');
        }

        return self::SUCCESS;
    }
}
