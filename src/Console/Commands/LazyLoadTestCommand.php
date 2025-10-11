<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Support\LazyLoader;

/**
 * LazyLoadTestCommand
 *
 * Test lazy loading for a specific module.
 */
class LazyLoadTestCommand extends Command
{
    protected $signature = 'ironflow:lazy:test {module : Module name to test}';
    protected $description = 'Test lazy loading for a specific module';

    public function handle(LazyLoader $lazyLoader): int
    {
        $moduleName = $this->argument('module');

        if (!$lazyLoader->isLazyLoadable($moduleName)) {
            $this->error("Module {$moduleName} is not lazy loadable!");
            $this->comment('Possible reasons:');
            $this->line('  • Module is in eager list');
            $this->line('  • Lazy loading is disabled');
            return 1;
        }

        $this->info("Testing lazy load for module: {$moduleName}");
        $this->newLine();

        // Test loading
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        try {
            $module = $lazyLoader->load($moduleName, 'test');

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $memoryUsed = round((memory_get_usage() - $startMemory) / 1024 / 1024, 2);

            $this->info("✓ Module loaded successfully!");
            $this->newLine();

            $metadata = $module->getMetadata();
            $state = $module->getState();

            $this->table(
                ['Property', 'Value'],
                [
                    ['Name', $metadata->getName()],
                    ['Version', $metadata->getVersion()],
                    ['State', $state->getCurrentState()],
                    ['Load Time', "{$duration}ms"],
                    ['Memory Used', "{$memoryUsed}MB"],
                    ['Dependencies', implode(', ', $metadata->getDependencies()) ?: 'None'],
                ]
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("✗ Failed to load module!");
            $this->error($e->getMessage());
            return 1;
        }
    }
}
