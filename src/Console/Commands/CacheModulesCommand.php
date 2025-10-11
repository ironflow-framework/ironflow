<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use IronFlow\Core\BaseModule;
use IronFlow\Facades\Anvil;

/**
 * CacheModulesCommand
 */
class CacheModulesCommand extends Command
{
    protected $signature = 'ironflow:cache:modules';

    protected $description = 'Cache the discovered modules for faster boot';

    public function handle(): int
    {
        $modules = Anvil::getModules();

        if (config('ironflow.cache.enabled', true)) {
            Cache::put(
                config('ironflow.cache.key', 'ironflow.modules'),
                $modules->keys()->toArray(),
                config('ironflow.cache.ttl', 3600)
            );
        }

        $this->info('Modules cached successfully!');

        return self::SUCCESS;
    }
}
