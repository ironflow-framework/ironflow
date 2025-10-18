<?php

namespace IronFlow\Testing\Concerns;

use IronFlow\Facades\Anvil;
use IronFlow\Core\BaseModule;

trait InteractsWithModules
{
    protected function getModule(string $name): ?BaseModule
    {
        return Anvil::getModule($name);
    }

    protected function getAllModules(): array
    {
        return Anvil::getModules();
    }

    protected function getService(string $serviceName, ?string $context = null): mixed
    {
        return Anvil::getService($serviceName, $context);
    }

    protected function mockModule(string $moduleName, BaseModule $mock): void
    {
        $reflection = new \ReflectionProperty(Anvil::class, 'modules');
        $reflection->setAccessible(true);
        
        $modules = $reflection->getValue(app(Anvil::class));
        $modules[$moduleName] = $mock;
        
        $reflection->setValue(app(Anvil::class), $modules);
    }

    protected function disableModule(string $moduleName): void
    {
        $module = $this->getModule($moduleName);
        
        if ($module) {
            $module->setState(\IronFlow\Core\ModuleState::DISABLED);
        }
    }

    protected function clearModuleCache(): void
    {
        Anvil::clearCache();
    }
}