<?php

use IronFlow\Facades\Anvil;

test('services are loaded lazily when enabled', function () {
    config(['ironflow.lazy_loading.enabled' => true]);

    $loaded = false;

    $module = new class($loaded) extends \IronFlow\Core\BaseModule implements \IronFlow\Contracts\ExposableInterface {
        public function __construct(private bool &$loadedFlag) {
            parent::__construct();
        }

        protected function defineMetadata(): \IronFlow\Core\ModuleMetaData
        {
            return new \IronFlow\Core\ModuleMetaData(
                name: 'LazyModule',
                version: '1.0.0',
            );
        }

        public function expose(): array
        {
            return [
                'lazy-service' => LazyTestService::class,
            ];
        }

        public function exposeLinked(): array
        {
            return [];
        }
    };

    class LazyTestService
    {
        public function __construct()
        {
            // Service should only be instantiated when requested
        }
    }

    Anvil::registerModule('LazyModule', get_class($module));
    Anvil::bootModule('LazyModule');

    // Service not yet loaded
    expect(app()->bound(LazyTestService::class))->toBeTrue();

    // Resolve service - should be instantiated now
    $service = Anvil::getService('lazymodule.lazy-service');
    expect($service)->toBeInstanceOf(LazyTestService::class);
});
