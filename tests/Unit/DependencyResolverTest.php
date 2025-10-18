<?php

use IronFlow\Exceptions\ModuleException;
use IronFlow\Services\DependencyResolver;

beforeEach(function () {
    $this->resolver = new DependencyResolver();
});

test('dependency resolver resolves modules without dependencies', function () {
    $moduleA = createTestModule('ModuleA');
    $moduleB = createTestModule('ModuleB');

    $modules = [
        'ModuleA' => $moduleA,
        'ModuleB' => $moduleB,
    ];

    $resolved = $this->resolver->resolve($modules);

    expect($resolved)->toHaveCount(2)
        ->and($resolved)->toContain('ModuleA')
        ->and($resolved)->toContain('ModuleB');
});

test('dependency resolver resolves modules in correct order', function () {
    // ModuleB depends on ModuleA
    $moduleA = createTestModule('ModuleA');
    $moduleB = new class extends \IronFlow\Core\BaseModule {
        protected function defineMetadata(): \IronFlow\Core\ModuleMetaData
        {
            return new \IronFlow\Core\ModuleMetaData(
                name: 'ModuleB',
                version: '1.0.0',
                dependencies: ['ModuleA'],
            );
        }
    };

    $modules = [
        'ModuleB' => $moduleB,
        'ModuleA' => $moduleA,
    ];

    $resolved = $this->resolver->resolve($modules);

    expect($resolved)->toBe(['ModuleA', 'ModuleB']);
});

test('dependency resolver detects circular dependencies', function () {
    // ModuleA depends on ModuleB, ModuleB depends on ModuleA
    $moduleA = new class extends \IronFlow\Core\BaseModule {
        protected function defineMetadata(): \IronFlow\Core\ModuleMetaData
        {
            return new \IronFlow\Core\ModuleMetaData(
                name: 'ModuleA',
                version: '1.0.0',
                dependencies: ['ModuleB'],
            );
        }
    };

    $moduleB = new class extends \IronFlow\Core\BaseModule {
        protected function defineMetadata(): \IronFlow\Core\ModuleMetaData
        {
            return new \IronFlow\Core\ModuleMetaData(
                name: 'ModuleB',
                version: '1.0.0',
                dependencies: ['ModuleA'],
            );
        }
    };

    $modules = [
        'ModuleA' => $moduleA,
        'ModuleB' => $moduleB,
    ];

    $this->resolver->resolve($modules);
})->throws(ModuleException::class, 'Circular dependency detected');

test('dependency resolver throws helpful error for missing dependencies', function () {
    $module = new class extends \IronFlow\Core\BaseModule {
        protected function defineMetadata(): \IronFlow\Core\ModuleMetaData
        {
            return new \IronFlow\Core\ModuleMetaData(
                name: 'ModuleA',
                version: '1.0.0',
                dependencies: ['NonExistent'],
            );
        }
    };

    try {
        $this->resolver->resolve(['ModuleA' => $module]);
    } catch (ModuleException $e) {
        expect($e->getMessage())
            ->toContain('NonExistent')
            ->toContain('not registered')
            ->toContain('Suggestions:');
    }
});

test('dependency resolver calculates dependency depth', function () {
    $moduleA = createTestModule('ModuleA'); // depth 0
    $moduleB = new class extends \IronFlow\Core\BaseModule {
        protected function defineMetadata(): \IronFlow\Core\ModuleMetaData
        {
            return new \IronFlow\Core\ModuleMetaData(
                name: 'ModuleB',
                version: '1.0.0',
                dependencies: ['ModuleA'], // depth 1
            );
        }
    };
    $moduleC = new class extends \IronFlow\Core\BaseModule {
        protected function defineMetadata(): \IronFlow\Core\ModuleMetaData
        {
            return new \IronFlow\Core\ModuleMetaData(
                name: 'ModuleC',
                version: '1.0.0',
                dependencies: ['ModuleB'], // depth 2
            );
        }
    };

    $modules = [
        'ModuleA' => $moduleA,
        'ModuleB' => $moduleB,
        'ModuleC' => $moduleC,
    ];

    $tree = $this->resolver->getTree($modules);

    expect($tree['ModuleA']['depth'])->toBe(0)
        ->and($tree['ModuleB']['depth'])->toBe(1)
        ->and($tree['ModuleC']['depth'])->toBe(2);
});
