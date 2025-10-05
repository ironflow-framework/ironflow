<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * CacheClearCommand
 */
class CacheClearCommand extends Command
{
    protected $signature = 'ironflow:cache:clear';

    protected $description = 'Clear the IronFlow module cache';

    public function handle(): int
    {
        $cachePath = storage_path('framework/cache/ironflow');

        if (File::exists($cachePath)) {
            File::deleteDirectory($cachePath);
            $this->info('IronFlow cache cleared successfully!');
        } else {
            $this->info('No cache to clear.');
        }

        return self::SUCCESS;
    }
}
