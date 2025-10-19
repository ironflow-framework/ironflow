<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

describe('Module Creation', function () {
    beforeEach(function () {
        File::deleteDirectory(app_path('Modules/TestModule'));
    });

    afterEach(function () {
        File::deleteDirectory(app_path('Modules/TestModule'));
    });

    test('can create a new module', function () {
        Artisan::call('ironflow:module:create', [
            'name' => 'TestModule',
            '--author' => 'Test Author',
            '--description' => 'Test Description',
        ]);

        $modulePath = app_path('Modules/TestModule');

        expect(File::exists($modulePath))->toBeTrue()
            ->and(File::exists($modulePath . '/Controllers'))->toBeTrue()
            ->and(File::exists($modulePath . '/Models'))->toBeTrue()
            ->and(File::exists($modulePath . '/Services'))->toBeTrue()
            ->and(File::exists($modulePath . '/Routes'))->toBeTrue()
            ->and(File::exists($modulePath . '/TestModuleModule.php'))->toBeTrue();
    });

    test('prevents duplicate module creation', function () {
        File::makeDirectory(app_path('Modules/TestModule'), 0755, true);

        $exitCode = Artisan::call('ironflow:module:create', [
            'name' => 'TestModule',
        ]);

        expect($exitCode)->toBe(1);
    });
});
