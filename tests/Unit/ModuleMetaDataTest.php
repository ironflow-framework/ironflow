<?php

use IronFlow\Core\ModuleMetaData;

test('module metadata can be created with valid data', function () {
    $metadata = new ModuleMetaData(
        name: 'Blog',
        version: '1.0.0',
        description: 'Blog module',
        author: 'John Doe',
        dependencies: ['Users'],
        provides: ['BlogService'],
        path: '/path/to/module',
        namespace: 'Modules\\Blog',
    );

    expect($metadata->name)->toBe('Blog')
        ->and($metadata->version)->toBe('1.0.0')
        ->and($metadata->dependencies)->toContain('Users')
        ->and($metadata->provides)->toContain('BlogService');
});

test('module metadata validates name format', function () {
    new ModuleMetaData(
        name: 'invalid-name',  // Should start with uppercase
        version: '1.0.0',
    );
})->throws(InvalidArgumentException::class, 'must start with uppercase');

test('module metadata validates version format', function () {
    new ModuleMetaData(
        name: 'Blog',
        version: 'invalid',  // Should be semver
    );
})->throws(InvalidArgumentException::class, 'semantic versioning');

test('module metadata can check dependencies', function () {
    $metadata = new ModuleMetaData(
        name: 'Blog',
        version: '1.0.0',
        dependencies: ['Users', 'Auth'],
    );

    expect($metadata->hasDependency('Users'))->toBeTrue()
        ->and($metadata->hasDependency('Comments'))->toBeFalse();
});

test('module metadata can check version compatibility', function () {
    $metadata = new ModuleMetaData(
        name: 'Blog',
        version: '2.5.0',
    );

    expect($metadata->isCompatibleWith('2.0.0'))->toBeTrue()
        ->and($metadata->isCompatibleWith('2.5.0'))->toBeTrue()
        ->and($metadata->isCompatibleWith('3.0.0'))->toBeFalse();
});
