<?php

use IronFlow\Exceptions\ServiceNotFoundException;
use IronFlow\Facades\Anvil;

test('module can expose services', function () {
    $module = new class extends \IronFlow\Core\BaseModule implements \IronFlow\Contracts\ExposableInterface {
        protected function defineMetadata(): \IronFlow\Core\ModuleMetaData
        {
            return new \IronFlow\Core\ModuleMetaData(
                name: 'Blog',
                version: '1.0.0',
            );
        }

        public function expose(): array
        {
            return [
                'blog-service' => \stdClass::class,
            ];
        }

        public function exposeLinked(): array
        {
            return [];
        }
    };

    Anvil::registerModule('Blog', get_class($module));
    Anvil::bootModule('Blog');

    $service = Anvil::getService('blog.blog-service');

    expect($service)->toBeInstanceOf(\stdClass::class);
});

test('linked services respect access control', function () {
    $module = new class extends \IronFlow\Core\BaseModule implements \IronFlow\Contracts\ExposableInterface {
        protected function defineMetadata(): \IronFlow\Core\ModuleMetaData
        {
            return new \IronFlow\Core\ModuleMetaData(
                name: 'Blog',
                version: '1.0.0',
            );
        }

        public function expose(): array
        {
            return [];
        }

        public function exposeLinked(): array
        {
            return [
                'admin-service' => [
                    'class' => \stdClass::class,
                    'allowed_modules' => ['Admin'],
                ],
            ];
        }
    };

    Anvil::registerModule('Blog', get_class($module));
    Anvil::bootModule('Blog');

    // Should throw exception when accessed from wrong context
    Anvil::getService('blog.admin-service', 'UnauthorizedModule');
})->throws(ServiceNotFoundException::class, 'not accessible');

