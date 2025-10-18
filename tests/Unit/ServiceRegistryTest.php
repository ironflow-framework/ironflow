<?php

use IronFlow\Services\ServiceRegistry;
use IronFlow\Exceptions\ServiceNotFoundException;

beforeEach(function () {
    $this->registry = app(ServiceRegistry::class);
});

test('service registry can register public services', function () {
    $this->registry->registerPublicServices('TestModule', [
        'test-service' => stdClass::class,
    ]);

    expect($this->registry->has('testmodule.test-service'))->toBeTrue();
});

test('service registry can resolve registered services', function () {
    $this->registry->registerPublicServices('TestModule', [
        'test-service' => stdClass::class,
    ]);

    $service = $this->registry->resolve('testmodule.test-service');

    expect($service)->toBeInstanceOf(stdClass::class);
});

test('service registry throws exception for missing service', function () {
    $this->registry->resolve('non-existent.service');
})->throws(ServiceNotFoundException::class, 'not found');

test('service registry suggests similar service names', function () {
    $this->registry->registerPublicServices('TestModule', [
        'blog-service' => stdClass::class,
    ]);

    try {
        $this->registry->resolve('testmodule.blod-service');
    } catch (ServiceNotFoundException $e) {
        expect($e->getMessage())->toContain('Did you mean');
    }
});

test('service registry can register services with interface validation', function () {
    interface TestInterface {}
    class TestService implements TestInterface {}

    $this->registry->registerPublicServices('TestModule', [
        'test' => [
            'class' => TestService::class,
            'interface' => TestInterface::class,
        ],
    ]);

    $service = $this->registry->resolve('testmodule.test');
    expect($service)->toBeInstanceOf(TestInterface::class);
});
