<?php

use IronFlow\Core\ModuleState;
use IronFlow\Exceptions\ModuleException;

test('base module starts in unregistered state', function () {
    $module = createTestModule();

    expect($module->getState())->toBe(ModuleState::UNREGISTERED);
});

test('base module can transition through valid states', function () {
    $module = createTestModule();

    $module->setState(ModuleState::REGISTERED);
    expect($module)->toHaveState(ModuleState::REGISTERED);

    $module->setState(ModuleState::PRELOADED);
    expect($module)->toHaveState(ModuleState::PRELOADED);

    $module->setState(ModuleState::BOOTING);
    expect($module)->toHaveState(ModuleState::BOOTING);

    $module->setState(ModuleState::BOOTED);
    expect($module)->toHaveState(ModuleState::BOOTED);
});

test('base module throws exception on invalid state transition', function () {
    $module = createTestModule();
    $module->setState(ModuleState::REGISTERED);

    // Invalid: can't go directly from REGISTERED to BOOTED
    $module->setState(ModuleState::BOOTED);
})->throws(ModuleException::class, 'Invalid state transition');

test('base module can be marked as failed', function () {
    $module = createTestModule();
    $exception = new Exception('Test error');

    $module->markAsFailed($exception);

    expect($module->getState())->toBe(ModuleState::FAILED)
        ->and($module->getLastError())->toBe($exception);
});

test('base module can check if active', function () {
    $module = createTestModule();

    expect($module->isActive())->toBeFalse();

    $module->setState(ModuleState::REGISTERED);
    expect($module->isActive())->toBeTrue();

    $module->setState(ModuleState::PRELOADED);
    expect($module->isActive())->toBeTrue();

    $module->setState(ModuleState::BOOTED);
    expect($module->isActive())->toBeTrue();

    $module->markAsFailed(new Exception());
    expect($module->isActive())->toBeFalse();
});
