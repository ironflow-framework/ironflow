<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Support\LazyLoader;

/**
 * LazyLoadClearCommand
 *
 * Clear lazy loading cache.
 */
class LazyLoadClearCommand extends Command
{
    protected $signature = 'ironflow:lazy:clear';
    protected $description = 'Clear lazy loading cache';

    public function handle(LazyLoader $lazyLoader): int
    {
        $this->info('Clearing lazy loading cache...');

        $lazyLoader->clearCache();

        $this->info('✓ Cache cleared successfully!');

        return 0;
    }
}

