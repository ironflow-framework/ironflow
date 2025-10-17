<?php

declare(strict_types=1);

namespace IronFlow\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * ModuleEventBus
 *
 * Dedicated event bus for inter-module communication.
 */
class ModuleEventBus
{
    /**
     * @var array Registered listeners per module
     */
    protected static array $listeners = [];

    /**
     * @var array Event subscriptions
     */
    protected static array $subscriptions = [];

    /**
     * @var array Event history for debugging
     */
    protected static array $history = [];

    /**
     * @var bool Debug mode
     */
    protected static bool $debug = false;

    /**
     * Dispatch an event from a module.
     *
     * @param string $moduleName
     * @param string $eventName
     * @param array $data
     * @param bool $async
     * @return void
     */
    public static function dispatch(string $moduleName, string $eventName, array $data = [], bool $async = false): void
    {
        $fullEventName = self::buildEventName($moduleName, $eventName);

        if (self::$debug) {
            self::logEvent('dispatch', $moduleName, $eventName, $data);
        }

        // Store in history
        self::$history[] = [
            'type' => 'dispatch',
            'module' => $moduleName,
            'event' => $eventName,
            'data' => $data,
            'timestamp' => now()->toDateTimeString(),
        ];

        // Create event object
        $eventObject = new ModuleEvent($moduleName, $eventName, $data);

        if ($async) {
            // Dispatch as job for async processing
            dispatch(function () use ($fullEventName, $eventObject) {
                Event::dispatch($fullEventName, [$eventObject]);
            })->onQueue('module-events');
        } else {
            Event::dispatch($fullEventName, [$eventObject]);
        }
    }

    public static function channel(string $channelName): LoggerInterface
    {
        return Log::channel($channelName);
    }

    /**
     * Listen to events from a specific module.
     *
     * @param string $moduleName
     * @param string $eventName
     * @param callable $listener
     * @param int $priority
     * @return void
     */
    public static function listen(string $moduleName, string $eventName, callable $listener, int $priority = 0): void
    {
        $fullEventName = self::buildEventName($moduleName, $eventName);

        if (!isset(self::$listeners[$fullEventName])) {
            self::$listeners[$fullEventName] = [];
        }

        self::$listeners[$fullEventName][] = [
            'listener' => $listener,
            'priority' => $priority,
        ];

        // Sort by priority
        usort(self::$listeners[$fullEventName], fn($a, $b) => $b['priority'] <=> $a['priority']);

        // Register with Laravel's event system
        Event::listen($fullEventName, $listener);

        if (self::$debug) {
            Log::debug("[ModuleEventBus] Listener registered", [
                'event' => $fullEventName,
            ]);
        }
    }

    /**
     * Subscribe a module to multiple events.
     *
     * @param string $subscriberModule
     * @param array $events ['ModuleName' => ['event1', 'event2']]
     * @return void
     */
    public static function subscribe(string $subscriberModule, array $events): void
    {
        foreach ($events as $moduleName => $eventNames) {
            foreach ($eventNames as $eventName) {
                if (!isset(self::$subscriptions[$subscriberModule])) {
                    self::$subscriptions[$subscriberModule] = [];
                }

                self::$subscriptions[$subscriberModule][] = [
                    'module' => $moduleName,
                    'event' => $eventName,
                ];
            }
        }
    }

    /**
     * Forget all listeners for an event.
     *
     * @param string $moduleName
     * @param string $eventName
     * @return void
     */
    public static function forget(string $moduleName, string $eventName): void
    {
        $fullEventName = self::buildEventName($moduleName, $eventName);

        Event::forget($fullEventName);
        unset(self::$listeners[$fullEventName]);
    }

    /**
     * Get all listeners for a module event.
     *
     * @param string $moduleName
     * @param string $eventName
     * @return array
     */
    public static function getListeners(string $moduleName, string $eventName): array
    {
        $fullEventName = self::buildEventName($moduleName, $eventName);
        return self::$listeners[$fullEventName] ?? [];
    }

    /**
     * Get all subscriptions for a module.
     *
     * @param string $moduleName
     * @return array
     */
    public static function getSubscriptions(string $moduleName): array
    {
        return self::$subscriptions[$moduleName] ?? [];
    }

    /**
     * Build full event name.
     *
     * @param string $moduleName
     * @param string $eventName
     * @return string
     */
    protected static function buildEventName(string $moduleName, string $eventName): string
    {
        return "ironflow.module.{$moduleName}.{$eventName}";
    }

    /**
     * Enable debug mode.
     *
     * @return void
     */
    public static function enableDebug(): void
    {
        self::$debug = true;
    }

    /**
     * Disable debug mode.
     *
     * @return void
     */
    public static function disableDebug(): void
    {
        self::$debug = false;
    }

    /**
     * Get event history.
     *
     * @param int|null $limit
     * @return array
     */
    public static function getHistory(?int $limit = null): array
    {
        if ($limit) {
            return array_slice(self::$history, -$limit);
        }

        return self::$history;
    }

    /**
     * Clear event history.
     *
     * @return void
     */
    public static function clearHistory(): void
    {
        self::$history = [];
    }

    /**
     * Get statistics.
     *
     * @return array
     */
    public static function getStatistics(): array
    {
        return [
            'total_listeners' => count(self::$listeners),
            'total_subscriptions' => count(self::$subscriptions),
            'total_events_dispatched' => count(self::$history),
            'debug_enabled' => self::$debug,
        ];
    }

    /**
     * Log event for debugging.
     *
     * @param string $type
     * @param string $moduleName
     * @param string $eventName
     * @param array $data
     * @return void
     */
    protected static function logEvent(string $type, string $moduleName, string $eventName, array $data): void
    {
        Log::debug("[ModuleEventBus] {$type}", [
            'module' => $moduleName,
            'event' => $eventName,
            'data' => $data,
        ]);
    }
}
