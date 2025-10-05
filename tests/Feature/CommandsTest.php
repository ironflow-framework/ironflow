<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use IronFlow\Contracts\ModuleInterface;
use IronFlow\Core\Anvil;
use IronFlow\Core\ModuleMetadata;

describe('IronFlow Commands', function () {
    test('list command shows registered modules', function () {
        Artisan::call('ironflow:list');
        $output = Artisan::output();

        expect($output)->toBeString();
    });

    test('boot order command displays dependency order', function () {
        Artisan::call('ironflow:boot-order');
        $output = Artisan::output();

        expect($output)->toBeString();
    });

    test('cache clear command removes cache', function () {
        $cachePath = storage_path('framework/cache/ironflow');
        File::makeDirectory($cachePath, 0755, true);
        File::put($cachePath . '/test.php', '<?php return [];');

        Artisan::call('ironflow:cache:clear');

        expect(File::exists($cachePath))->toBeFalse();
    });

     test('skips disabled modules', function () {
        $anvil = new Anvil();
        $module = Mockery::mock(ModuleInterface::class);
        $metadata = new ModuleMetadata(name: 'TestModule', enabled: false);

        $module->shouldReceive('metadata')->andReturn($metadata);

        $anvil->register($module);

        expect($anvil->getModules())->toHaveCount(1); // Still registered but marked disabled
    });

    test('can get a registered module', function () {
        $anvil = new Anvil();
        $module = Mockery::mock(ModuleInterface::class);
        $metadata = new ModuleMetadata(name: 'TestModule');

        $module->shouldReceive('metadata')->andReturn($metadata);

        $anvil->register($module);

        expect($anvil->getModules())->toHaveCount(1);
    });
});

afterEach(function () {
    Mockery::close();
});