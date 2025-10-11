<?php

declare(strict_types=1);

namespace IronFlow\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ModuleLazyLoadedListener
 *
 * Track lazy loading events for analytics.
 */
class ModuleLazyLoadedListener
{
    /**
     * Handle the event.
     *
     * @param array $event
     * @return void
     */
    public function handle(array $event): void
    {
        $module = $event['module'];
        $trigger = $event['trigger'];
        $duration = $event['duration'];

        // Track in cache for analytics
        $this->trackLoadEvent($module, $trigger, $duration);

        // Log if debug mode
        if (config('ironflow.debug')) {
            Log::debug("[IronFlow Analytics] Module lazy loaded", [
                'module' => $module,
                'trigger' => $trigger,
                'duration' => $duration,
            ]);
        }
    }

    /**
     * Track load event for analytics.
     *
     * @param string $module
     * @param string $trigger
     * @param float $duration
     * @return void
     */
    protected function trackLoadEvent(string $module, string $trigger, float $duration): void
    {
        $key = "ironflow.analytics.lazy_load.{$module}";

        $data = Cache::get($key, [
            'total_loads' => 0,
            'total_duration' => 0,
            'triggers' => [],
            'first_load' => now()->toDateTimeString(),
        ]);

        $data['total_loads']++;
        $data['total_duration'] += $duration;
        $data['last_load'] = now()->toDateTimeString();

        if (!isset($data['triggers'][$trigger])) {
            $data['triggers'][$trigger] = 0;
        }
        $data['triggers'][$trigger]++;

        Cache::put($key, $data, 86400); // 24 hours
    }
}
