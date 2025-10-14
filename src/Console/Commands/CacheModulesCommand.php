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
        if (config('ironflow.cache.enabled', true)) {

            Anvil::discover();

            $this->output->success('Modules cached successfully!');
        } else {
            $this->output->error('Ironflow cache not allowed.');
        }

        return self::SUCCESS;
    }
}
