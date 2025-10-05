<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use IronFlow\Core\Anvil;

/**
 * CacheModulesCommand
 */
class CacheModulesCommand extends Command
{
    protected $signature = 'ironflow:cache:modules';

    protected $description = 'Cache the discovered modules for faster boot';

    public function handle(Anvil $anvil): int
    {
        $cachePath = storage_path('framework/cache/ironflow');
        File::ensureDirectoryExists($cachePath);

        $modules = $anvil->getModules()->map(function ($moduleData) {
            return [
                'class' => get_class($moduleData['instance']),
                'metadata' => $moduleData['metadata']->toArray(),
            ];
        });

        $cacheFile = $cachePath . '/modules.php';
        $content = '<?php return ' . var_export($modules->toArray(), true) . ';';

        File::put($cacheFile, $content);

        $this->info('Modules cached successfully!');
        $this->line("Cache file: {$cacheFile}");

        return self::SUCCESS;
    }
}
