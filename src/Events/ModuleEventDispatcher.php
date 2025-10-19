<?php

declare(strict_types=1);

namespace IronFlow\Events;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;

class ModuleEventDispatcher
{
    protected Application $app;
    protected array $history = [];
    protected int $maxHistoryItems;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->maxHistoryItems = config('ironflow.events.history.max_items', 1000);
    }

    /**
     * Dispatch a module event
     */
    public function dispatch(object $event): void
    {
        if (!config('ironflow.events.enabled', true)) {
            return;
        }

        // Dispatch via Laravel's event system
        Event::dispatch($event);

        // Store in history if enabled
        if (config('ironflow.events.history.enabled', false)) {
            $this->addToHistory($event);
        }
    }

    /**
     * Add event to history
     */
    protected function addToHistory(object $event): void
    {
        $this->history[] = [
            'event' => get_class($event),
            'data' => $this->serializeEvent($event),
            'timestamp' => now(),
        ];

        // Trim history if needed
        if (count($this->history) > $this->maxHistoryItems) {
            array_shift($this->history);
        }
    }

    /**
     * Serialize event for history
     */
    protected function serializeEvent(object $event): array
    {
        return [
            'class' => get_class($event),
            'properties' => get_object_vars($event),
        ];
    }

    /**
     * Get event history
     */
    public function getHistory(): array
    {
        return $this->history;
    }

    /**
     * Clear event history
     */
    public function clearHistory(): void
    {
        $this->history = [];
    }
}
