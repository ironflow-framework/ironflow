<?php

declare(strict_types=1);

use IronFlow\Core\Anvil;
use IronFlow\Core\ModuleMetadata;
use IronFlow\Contracts\ModuleInterface;
use IronFlow\Exceptions\CircularDependencyException;
use IronFlow\Exceptions\ModuleNotFoundException;

describe('Anvil', function () {
    test('can register a module', function () {
        $anvil = new Anvil();
        $module = Mockery::mock(ModuleInterface::class);
        $metadata = new ModuleMetadata(name: 'TestModule');

        $module->shouldReceive('metadata')->andReturn($metadata);

        $anvil->register($module);

        expect($anvil->getModule('TestModule'))->toBe($module);
    });

    test('returns null for non-existent module', function () {
        $anvil = new Anvil();

        expect($anvil->getModule('NonExistent'))->toBeNull();
    });

    test('detects circular dependencies', function () {
        $anvil = new Anvil();

        // Module A depends on B
        $moduleA = Mockery::mock(ModuleInterface::class);
        $metadataA = new ModuleMetadata(name: 'ModuleA', dependencies: ['ModuleB']);
        $moduleA->shouldReceive('metadata')->andReturn($metadataA);

        // Module B depends on A (circular)
        $moduleB = Mockery::mock(ModuleInterface::class);
        $metadataB = new ModuleMetadata(name: 'ModuleB', dependencies: ['ModuleA']);
        $moduleB->shouldReceive('metadata')->andReturn($metadataB);

        $anvil->register($moduleA);
        $anvil->register($moduleB);

        expect(fn() => $anvil->load())->toThrow(CircularDependencyException::class);
    });

    test('throws exception for missing required dependency', function () {
        $anvil = new Anvil();

        $module = Mockery::mock(ModuleInterface::class);
        $metadata = new ModuleMetadata(
            name: 'TestModule',
            dependencies: ['MissingModule'],
            required: true
        );
        $module->shouldReceive('metadata')->andReturn($metadata);

        $anvil->register($module);

        expect(fn() => $anvil->load())->toThrow(ModuleNotFoundException::class);
    });

    test('calculates correct boot order', function () {
        $anvil = new Anvil();

        // ModuleC depends on ModuleB
        // ModuleB depends on ModuleA
        // Expected order: A, B, C

        $moduleA = Mockery::mock(ModuleInterface::class);
        $metadataA = new ModuleMetadata(name: 'ModuleA');
        $moduleA->shouldReceive('metadata')->andReturn($metadataA);
        $moduleA->shouldReceive('boot');

        $moduleB = Mockery::mock(ModuleInterface::class);
        $metadataB = new ModuleMetadata(name: 'ModuleB', dependencies: ['ModuleA']);
        $moduleB->shouldReceive('metadata')->andReturn($metadataB);
        $moduleB->shouldReceive('boot');

        $moduleC = Mockery::mock(ModuleInterface::class);
        $metadataC = new ModuleMetadata(name: 'ModuleC', dependencies: ['ModuleB']);
        $moduleC->shouldReceive('metadata')->andReturn($metadataC);
        $moduleC->shouldReceive('boot');

        $anvil->register($moduleC);
        $anvil->register($moduleA);
        $anvil->register($moduleB);

        $anvil->load();

        $bootOrder = $anvil->getBootOrder();

        expect($bootOrder)->toBe(['ModuleA', 'ModuleB', 'ModuleC']);
    });

    test('boots modules in correct order', function () {
        $anvil = new Anvil();
        $bootSequence = [];

        $moduleA = Mockery::mock(ModuleInterface::class);
        $metadataA = new ModuleMetadata(name: 'ModuleA');
        $moduleA->shouldReceive('metadata')->andReturn($metadataA);
        $moduleA->shouldReceive('boot')->andReturnUsing(function () use (&$bootSequence) {
            $bootSequence[] = 'ModuleA';
        });

        $moduleB = Mockery::mock(ModuleInterface::class);
        $metadataB = new ModuleMetadata(name: 'ModuleB', dependencies: ['ModuleA']);
        $moduleB->shouldReceive('metadata')->andReturn($metadataB);
        $moduleB->shouldReceive('boot')->andReturnUsing(function () use (&$bootSequence) {
            $bootSequence[] = 'ModuleB';
        });

        $anvil->register($moduleB);
        $anvil->register($moduleA);

        $anvil->load()->boot();

        expect($bootSequence)->toBe(['ModuleA', 'ModuleB']);
    });

    test('marks modules as booted', function () {
        $anvil = new Anvil();

        $module = Mockery::mock(ModuleInterface::class);
        $metadata = new ModuleMetadata(name: 'TestModule');
        $module->shouldReceive('metadata')->andReturn($metadata);
        $module->shouldReceive('boot');

        $anvil->register($module);
        $anvil->load()->boot();

        expect($anvil->isModuleBooted('TestModule'))->toBeTrue();
    });

    test('does not boot twice', function () {
        $anvil = new Anvil();
        $bootCount = 0;

        $module = Mockery::mock(ModuleInterface::class);
        $metadata = new ModuleMetadata(name: 'TestModule');
        $module->shouldReceive('metadata')->andReturn($metadata);
        $module->shouldReceive('boot')->andReturnUsing(function () use (&$bootCount) {
            $bootCount++;
        });

        $anvil->register($module);
        $anvil->load()->boot();
        $anvil->boot(); // Second call should be ignored

        expect($bootCount)->toBe(1);
    });
});
