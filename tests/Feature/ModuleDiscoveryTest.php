<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

describe('Module Discovery', function () {
    test('can discover modules', function () {
        // Create a test module structure
        $modulePath = app_path('Modules/DiscoveryTest');
        File::makeDirectory($modulePath, 0755, true);

        $moduleClass = <<<'PHP'
<?php
namespace App\Modules\DiscoveryTest;

use IronFlow\Core\BaseModule;
use IronFlow\Core\ModuleMetadata;

class DiscoveryTestModule extends BaseModule
{
    public function metadata(): ModuleMetadata
    {
        return new ModuleMetadata(name: 'DiscoveryTest');
    }
}
PHP;

        File::put($modulePath . '/DiscoveryTestModule.php', $moduleClass);

        Artisan::call('ironflow:discover');

        $output = Artisan::output();

        expect($output)->toContain('DiscoveryTest');

        File::deleteDirectory($modulePath);
    });
});

