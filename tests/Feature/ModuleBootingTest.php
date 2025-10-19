<?php

use IronFlow\Exceptions\ModuleException;
use IronFlow\Facades\Anvil;

test('module can be registered and booted', function () {
    $module = createTestModule('TestModule');

    Anvil::registerModule('TestModule', get_class($module));
    Anvil::bootModule('TestModule');

    expect(Anvil::getModule('TestModule'))->toBeBooted();
});

test('booting module with missing dependency fails', function () {
    $module = new class extends \IronFlow\Core\BaseModule {
        protected function defineMetadata(): \IronFlow\Core\ModuleMetaData
        {
            return new \IronFlow\Core\ModuleMetaData(
                name: 'TestModule',
                version: '1.0.0',
                dependencies: ['MissingModule'],
            );
        }
    };

    Anvil::registerModule('TestModule', get_class($module));
    Anvil::bootModules();
})->throws(ModuleException::class);

test('modules are booted in dependency order', function () {
    $bootOrder = [];

    $moduleA = new class($bootOrder) extends \IronFlow\Core\BaseModule {
        public function __construct(private array &$order) {
            parent::__construct();
        }

        protected function defineMetadata(): \IronFlow\Core\ModuleMetaData
        {
            return new \IronFlow\Core\ModuleMetaData(
                name: 'ModuleA',
                version: '1.0.0',
            );
        }

        public function bootModule(): void
        {
            $this->order[] = 'ModuleA';
        }
    };

    $moduleB = new class($bootOrder) extends \IronFlow\Core\BaseModule {
        public function __construct(private array &$order) {
            parent::__construct();
        }

        protected function defineMetadata(): \IronFlow\Core\ModuleMetaData
        {
            return new \IronFlow\Core\ModuleMetaData(
                name: 'ModuleB',
                version: '1.0.0',
                dependencies: ['ModuleA'],
            );
        }

        public function bootModule(): void
        {
            $this->order[] = 'ModuleB';
        }
    };

    Anvil::registerModule('ModuleB', get_class($moduleB));
    Anvil::registerModule('ModuleA', get_class($moduleA));
    Anvil::bootModules();

    expect($bootOrder)->toBe(['ModuleA', 'ModuleB']);
});
