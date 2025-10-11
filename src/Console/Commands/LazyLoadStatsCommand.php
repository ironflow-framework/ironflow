<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Support\LazyLoader;

/**
 * LazyLoadStatsCommand
 *
 * Display lazy loading statistics.
 */
class LazyLoadStatsCommand extends Command
{
    protected $signature = 'ironflow:lazy:stats';
    protected $description = 'Display lazy loading statistics';

    public function handle(LazyLoader $lazyLoader): int
    {
        $stats = $lazyLoader->getStatistics();

        $this->info('IronFlow Lazy Loading Statistics');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Lazy Loading', $stats['enabled'] ? '<info>Enabled</info>' : '<comment>Disabled</comment>'],
                ['Total Modules', $stats['total_modules']],
                ['Eager Modules', $stats['eager_modules']],
                ['Loaded Modules', $stats['loaded_modules']],
                ['Pending Modules', $stats['pending_modules']],
                ['Memory Saved', $stats['memory_saved_estimate']],
            ]
        );

        $this->newLine();

        if (!empty($stats['loaded_list'])) {
            $this->info('Loaded Modules:');
            foreach ($stats['loaded_list'] as $module) {
                $this->line("  • {$module}");
            }
            $this->newLine();
        }

        if (!empty($stats['pending_list'])) {
            $this->comment('Pending Modules (not loaded yet):');
            foreach ($stats['pending_list'] as $module) {
                $this->line("  • {$module}");
            }
        }

        return 0;
    }
}
