<?php

namespace IronFlow\Testing;

use Orchestra\Testbench\TestCase;
use IronFlow\IronFlowServiceProvider;
use IronFlow\Facades\Anvil;
use IronFlow\Testing\Concerns\{InteractsWithModules, AssertsModules};

abstract class ModuleTestCase extends TestCase
{
    use InteractsWithModules, AssertsModules;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpModules();
    }

    protected function getPackageProviders($app): array
    {
        return [
            IronFlowServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Anvil' => Anvil::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Configure test database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configure IronFlow for testing
        $app['config']->set('ironflow.auto_discover', false);
        $app['config']->set('ironflow.cache.enabled', false);
        $app['config']->set('ironflow.exceptions.throw_on_missing_dependency', true);
    }

    protected function setUpModules(): void
    {
        // Override in child classes to register test modules
    }

    protected function registerTestModule(string $moduleClass): void
    {
        $module = new $moduleClass();
        $moduleName = $module->getName();
        
        Anvil::registerModule($moduleName, $moduleClass);
    }

    protected function bootTestModule(string $moduleName): void
    {
        Anvil::bootModule($moduleName);
    }
}