<?php

declare(strict_types=1);

namespace IronFlow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Anvil Facade
 *
 * @method static void discover()
 * @method static void registerModule(string|\IronFlow\Core\BaseModule $module)
 * @method static void bootAll()
 * @method static \IronFlow\Core\BaseModule|null getModule(string $name)
 * @method static bool hasModule(string $name)
 * @method static \Illuminate\Support\Collection getModules()
 * @method static \Illuminate\Support\Collection getEnabledModules()
 * @method static \Illuminate\Support\Collection getDisabledModules()
 * @method static void enable(string $name)
 * @method static void disable(string $name)
 * @method static void install(string $name)
 * @method static void uninstall(string $name)
 * @method static mixed getService(string $moduleName, string $serviceName, string|null $requesterModule = null)
 * @method static bool hasService(string $moduleName, string $serviceName)
 * @method static array getDependencies(string $name)
 * @method static \Illuminate\Support\Collection getDependents(string $name)
 * @method static void clearCache()
 * @method static array getStatistics()
 *
 * @see \IronFlow\Core\Anvil
 */
class Anvil extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'ironflow.anvil';
    }
}
