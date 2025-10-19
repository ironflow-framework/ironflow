<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Facades\Anvil;

class CacheCommand extends Command
{
    protected $signature = 'ironflow:cache';
    protected $description = 'Cache the module manifest';

    public function handle(): int
    {
        $this->info('Caching module manifest...');

        try {
            Anvil::discover();
            Anvil::cacheManifest();

            $modules = Anvil::getModules();
            $count = count($modules);

            $this->output->success("Cached {$count} module(s) successfully");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->output->error("Cache failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
