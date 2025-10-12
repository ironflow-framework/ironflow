<?php

namespace IronFlow\Console;

use Illuminate\Console\Command;
use IronFlow\Events\ModuleEventBus;

class EventBusStatsCommand extends Command
{
    protected $signature = 'ironflow:events:stats
                            {--history=10 : Show recent events}';
    protected $description = 'Show ModuleEventBus statistics';

    public function handle(): int
    {
        $stats = ModuleEventBus::getStatistics();

        $this->info('ModuleEventBus Statistics');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Listeners', $stats['total_listeners']],
                ['Total Subscriptions', $stats['total_subscriptions']],
                ['Events Dispatched', $stats['total_events_dispatched']],
                ['Debug Mode', $stats['debug_enabled'] ? 'Enabled' : 'Disabled'],
            ]
        );

        $historyLimit = (int) $this->option('history');
        if ($historyLimit > 0) {
            $history = ModuleEventBus::getHistory($historyLimit);
            
            if (!empty($history)) {
                $this->newLine();
                $this->info("Recent Events (last {$historyLimit}):");
                $this->newLine();

                foreach ($history as $event) {
                    $this->line("<comment>{$event['timestamp']}</comment> - {$event['module']}.{$event['event']}");
                }
            }
        }

        return 0;
    }
}