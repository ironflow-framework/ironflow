<?php

declare(strict_types=1);

use IronFlow\Core\ModuleMetadata;

describe('ModuleMetadata', function () {
    test('can be created with minimal data', function () {
        $metadata = new ModuleMetadata(name: 'TestModule');

        expect($metadata->name)->toBe('TestModule')
            ->and($metadata->version)->toBe('1.0.0')
            ->and($metadata->enabled)->toBeTrue()
            ->and($metadata->required)->toBeFalse();
    });

    test('can be created from array', function () {
        $data = [
            'name' => 'TestModule',
            'version' => '2.0.0',
            'description' => 'Test description',
            'dependencies' => ['OtherModule'],
        ];

        $metadata = ModuleMetadata::fromArray($data);

        expect($metadata->name)->toBe('TestModule')
            ->and($metadata->version)->toBe('2.0.0')
            ->and($metadata->description)->toBe('Test description')
            ->and($metadata->dependencies)->toBe(['OtherModule']);
    });

    test('validates module name format', function () {
        $metadata = new ModuleMetadata(name: 'ValidModule_123');
        expect($metadata->validate())->toBeTrue();
    });

    test('throws exception for invalid module name', function () {
        $metadata = new ModuleMetadata(name: '123Invalid');
        $metadata->validate();
    })->throws(InvalidArgumentException::class);

    test('throws exception for invalid version', function () {
        $metadata = new ModuleMetadata(name: 'Test', version: 'invalid');
        $metadata->validate();
    })->throws(InvalidArgumentException::class);

    test('can convert to array', function () {
        $metadata = new ModuleMetadata(
            name: 'TestModule',
            version: '1.0.0',
            dependencies: ['Dep1', 'Dep2']
        );

        $array = $metadata->toArray();

        expect($array)->toHaveKey('name')
            ->and($array)->toHaveKey('dependencies')
            ->and($array['dependencies'])->toBe(['Dep1', 'Dep2']);
    });

    test('can check if module has dependencies', function () {
        $metadata1 = new ModuleMetadata(name: 'Test1');
        $metadata2 = new ModuleMetadata(name: 'Test2', dependencies: ['Test1']);

        expect($metadata1->hasDependencies())->toBeFalse()
            ->and($metadata2->hasDependencies())->toBeTrue();
    });

    test('can check if module depends on another', function () {
        $metadata = new ModuleMetadata(
            name: 'Test',
            dependencies: ['Module1', 'Module2']
        );

        expect($metadata->dependsOn('Module1'))->toBeTrue()
            ->and($metadata->dependsOn('Module3'))->toBeFalse();
    });
});
