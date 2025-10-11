<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Support\HotReloader;

class HotReloadStatsCommand extends Command
{
    protected $signature = 'ironflow:hot-reload:stats';
    protected $description = 'Show hot reload statistics';

    public function handle(HotReloader $hotReloader): int
    {
        $stats = $hotReloader->getStatistics();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Enabled', $stats['enabled'] ? '<info>Yes</info>' : '<comment>No</comment>'],
                ['Watched Modules', $stats['watched_modules']],
                ['Watched Files', $stats['watched_files']],
            ]
        );

        if (!empty($stats['modules'])) {
            $this->newLine();
            $this->info('Watched Modules:');
            foreach ($stats['modules'] as $module) {
                $this->line("  • {$module}");
            }
        }

        return 0;
    }
}
