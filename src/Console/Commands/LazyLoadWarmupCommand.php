<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Support\LazyLoader;

/**
 * LazyLoadWarmupCommand
 *
 * Warm up lazy loader by preloading all modules.
 */
class LazyLoadWarmupCommand extends Command
{
    protected $signature = 'ironflow:lazy:warmup';
    protected $description = 'Warm up the lazy loader by preloading all modules';

    public function handle(LazyLoader $lazyLoader): int
    {
        if (!config('ironflow.lazy_load.enabled')) {
            $this->error('Lazy loading is disabled!');
            return 1;
        }

        $this->info('Warming up lazy loader...');

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $lazyLoader->warmUp();

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $memoryUsed = round((memory_get_usage() - $startMemory) / 1024 / 1024, 2);

        $stats = $lazyLoader->getStatistics();

        $this->newLine();
        $this->info("✓ All modules warmed up!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Modules Loaded', $stats['loaded_modules']],
                ['Duration', "{$duration}ms"],
                ['Memory Used', "{$memoryUsed}MB"],
            ]
        );

        return 0;
    }
}
