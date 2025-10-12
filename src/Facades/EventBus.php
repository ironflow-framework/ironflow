<?php

declare(strict_types=1);

namespace IronFlow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void dispatch(string $moduleName, string $eventName, array $data = [], bool $async = false)
 * @method static void listen(string $moduleName, string $eventName, callable $listener, int $priority = 0)
 * @method static void subscribe(string $subscriberModule, array $events)
 * @method static void forget(string $moduleName, string $eventName)
 * @method static array getListeners(string $moduleName, string $eventName)
 * @method static array getSubscriptions(string $moduleName)
 * @method static void enableDebug()
 * @method static void disableDebug()
 * @method static array getHistory(?int $limit = null)
 * @method static void clearHistory()
 * @method static array getStatistics()
 */
class EventBus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ironflow.eventbus';
    }
}