<?php

use IronFlow\Testing\ModuleTestCase;

uses(ModuleTestCase::class)->in('Feature', 'Unit');

// Helper functions globals
function createTestModule(string $name = 'TestModule'): \IronFlow\Core\BaseModule
{
    return new class($name) extends \IronFlow\Core\BaseModule {
        public function __construct(private string $moduleName = 'TestModule')
        {
            parent::__construct();
        }

        protected function defineMetadata(): \IronFlow\Core\ModuleMetaData
        {
            return new \IronFlow\Core\ModuleMetaData(
                name: $this->moduleName,
                version: '1.0.0',
                description: 'Test module',
                author: 'Test Author',
                dependencies: [],
                provides: [],
                path: __DIR__,
                namespace: __NAMESPACE__,
            );
        }
    };
}

// Expectations personalizes
expect()->extend('toBeBooted', function () {
    expect($this->value->isBooted())->toBeTrue();
});

expect()->extend('toHaveState', function (\IronFlow\Core\ModuleState $state) {
    expect($this->value->getState())->toBe($state);
});
