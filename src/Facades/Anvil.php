<?php

namespace IronFlow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \IronFlow\Core\Anvil register(\IronFlow\Contracts\ModuleInterface $module)
 * @method static \IronFlow\Core\Anvil load()
 * @method static void boot()
 * @method static bool isModuleBooted(string $name)
 * @method static \IronFlow\Contracts\ModuleInterface|null getModule(string $name)
 * @method static \Illuminate\Support\Collection getModules()
 * @method static array getBootOrder()
 * @method static bool hasBooted()
 *
 * @see \IronFlow\Core\Anvil
 */
class Anvil extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \IronFlow\Core\Anvil::class;
    }
}
