<?php

namespace IronFlow\Testing\Concerns;

use IronFlow\Core\{BaseModule, ModuleState};
use IronFlow\Facades\Anvil;
use PHPUnit\Framework\Assert;

trait AssertsModules
{
    protected function assertModuleRegistered(string $moduleName, string $message = ''): void
    {
        $module = Anvil::getModule($moduleName);
        
        Assert::assertNotNull(
            $module,
            $message ?: "Failed asserting that module '{$moduleName}' is registered"
        );
    }

    protected function assertModuleNotRegistered(string $moduleName, string $message = ''): void
    {
        $module = Anvil::getModule($moduleName);
        
        Assert::assertNull(
            $module,
            $message ?: "Failed asserting that module '{$moduleName}' is not registered"
        );
    }

    protected function assertModuleBooted(string $moduleName, string $message = ''): void
    {
        $module = Anvil::getModule($moduleName);
        
        Assert::assertNotNull($module, "Module '{$moduleName}' not found");
        Assert::assertTrue(
            $module->isBooted(),
            $message ?: "Failed asserting that module '{$moduleName}' is booted"
        );
    }

    protected function assertModuleState(string $moduleName, ModuleState $expectedState, string $message = ''): void
    {
        $module = Anvil::getModule($moduleName);
        
        Assert::assertNotNull($module, "Module '{$moduleName}' not found");
        Assert::assertEquals(
            $expectedState,
            $module->getState(),
            $message ?: "Failed asserting that module '{$moduleName}' has state '{$expectedState->value}'"
        );
    }

    protected function assertModuleFailed(string $moduleName, string $message = ''): void
    {
        $this->assertModuleState($moduleName, ModuleState::FAILED, $message);
    }

    protected function assertRouteExists(string $routeName, string $message = ''): void
    {
        $routes = app('router')->getRoutes();
        $route = $routes->getByName($routeName);
        
        Assert::assertNotNull(
            $route,
            $message ?: "Failed asserting that route '{$routeName}' exists"
        );
    }

    protected function assertViewExists(string $viewName, string $message = ''): void
    {
        Assert::assertTrue(
            view()->exists($viewName),
            $message ?: "Failed asserting that view '{$viewName}' exists"
        );
    }

    protected function assertServiceExposed(string $serviceName, string $message = ''): void
    {
        $serviceRegistry = app(\IronFlow\Services\ServiceRegistry::class);
        
        Assert::assertTrue(
            $serviceRegistry->has($serviceName),
            $message ?: "Failed asserting that service '{$serviceName}' is exposed"
        );
    }

    protected function assertServiceResolvable(string $serviceName, string $message = ''): void
    {
        try {
            $service = Anvil::getService($serviceName);
            Assert::assertNotNull(
                $service,
                $message ?: "Failed asserting that service '{$serviceName}' is resolvable"
            );
        } catch (\Exception $e) {
            Assert::fail(
                $message ?: "Failed asserting that service '{$serviceName}' is resolvable: {$e->getMessage()}"
            );
        }
    }

    protected function assertModuleHasDependency(string $moduleName, string $dependencyName, string $message = ''): void
    {
        $module = Anvil::getModule($moduleName);
        
        Assert::assertNotNull($module, "Module '{$moduleName}' not found");
        Assert::assertContains(
            $dependencyName,
            $module->getMetadata()->dependencies,
            $message ?: "Failed asserting that module '{$moduleName}' depends on '{$dependencyName}'"
        );
    }

    protected function assertNoConflicts(string $message = ''): void
    {
        $conflictDetector = app(\IronFlow\Core\Discovery\ConflictDetector::class);
        $conflicts = $conflictDetector->detect(Anvil::getModules());
        
        Assert::assertEmpty(
            $conflicts,
            $message ?: "Failed asserting no conflicts. Found: " . json_encode($conflicts)
        );
    }

    protected function assertConfigLoaded(string $configKey, string $message = ''): void
    {
        $config = config($configKey);
        
        Assert::assertNotNull(
            $config,
            $message ?: "Failed asserting that config '{$configKey}' is loaded"
        );
    }

    protected function assertModuleCount(int $expectedCount, string $message = ''): void
    {
        $actualCount = count(Anvil::getModules());
        
        Assert::assertEquals(
            $expectedCount,
            $actualCount,
            $message ?: "Failed asserting that there are {$expectedCount} modules. Found {$actualCount}."
        );
    }
}
