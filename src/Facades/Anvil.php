<?php

declare(strict_types=1);

namespace IronFlow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void bootstrap()
 * @method static void discover()
 * @method static void registerModule(string $moduleName, string $moduleClass)
 * @method static void bootModules()
 * @method static mixed getService(string $serviceName, string $moduleContext = null)
 * @method static array getModules()
 * @method static \IronFlow\Core\BaseModule|null getModule(string $name)
 * @method static bool hasModule(string $name)
 * @method static void cacheManifest()
 * @method static void clearCache()
 * @method static array getDependencyTree()
 * @method static void resolveService(string $abstract, callable $concrete)
 * 
 * @see \IronFlow\Core\Anvil
 */
class Anvil extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \IronFlow\Core\Anvil::class;
    }
}