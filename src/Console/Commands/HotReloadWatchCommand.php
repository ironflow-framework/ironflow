<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Support\HotReloader;

/**
 * HotReloadCommand
 */
class HotReloadWatchCommand extends Command
{
    protected $signature = 'ironflow:hot-reload:watch
                            {--interval=1 : Check interval in seconds}';
    protected $description = 'Watch modules for changes and hot reload';

    public function handle(HotReloader $hotReloader): int
    {
        if (!config('ironflow.hot_reload.enabled')) {
            $this->error('Hot reload is disabled in configuration!');
            return 1;
        }

        $this->info('Starting hot reload watcher...');
        $hotReloader->enable();

        $stats = $hotReloader->getStatistics();
        $this->info("Watching {$stats['watched_modules']} modules ({$stats['watched_files']} files)");
        $this->newLine();

        $interval = (int) $this->option('interval');

        $this->info('Press Ctrl+C to stop watching');
        $this->newLine();

        while (true) {
            $changed = $hotReloader->checkAndReload();

            if (!empty($changed)) {
                foreach ($changed as $module => $file) {
                    $this->line("<info>✓</info> Reloaded: <comment>{$module}</comment> (changed: " . basename($file) . ")");
                }
            }

            sleep($interval);
        }

        return self::SUCCESS;
    }
}

