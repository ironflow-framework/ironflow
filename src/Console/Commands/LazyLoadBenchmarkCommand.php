<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Support\LazyLoader;

/**
 * LazyLoadBenchmarkCommand
 *
 * Benchmark lazy loading vs eager loading.
 */
class LazyLoadBenchmarkCommand extends Command
{
    protected $signature = 'ironflow:lazy:benchmark
                            {--runs=10 : Number of benchmark runs}';
    protected $description = 'Benchmark lazy loading performance';

    public function handle(): int
    {
        $runs = (int) $this->option('runs');

        $this->info("Running benchmark with {$runs} iterations...");
        $this->newLine();

        // Benchmark eager loading
        $this->comment('Testing Eager Loading...');
        $eagerTimes = [];
        $eagerMemory = [];

        for ($i = 0; $i < $runs; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();

            // Simulate eager loading
            config(['ironflow.lazy_load.enabled' => false]);
            app()->make('ironflow.anvil')->bootAll();

            $eagerTimes[] = (microtime(true) - $startTime) * 1000;
            $eagerMemory[] = (memory_get_usage() - $startMemory) / 1024 / 1024;

            // Clear for next iteration
            app()->forgetInstance('ironflow.anvil');
        }

        // Benchmark lazy loading
        $this->comment('Testing Lazy Loading...');
        $lazyTimes = [];
        $lazyMemory = [];

        for ($i = 0; $i < $runs; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();

            // Simulate lazy loading (eager modules only)
            config(['ironflow.lazy_load.enabled' => true]);
            $lazyLoader = app()->make(LazyLoader::class);
            $lazyLoader->loadEager();

            $lazyTimes[] = (microtime(true) - $startTime) * 1000;
            $lazyMemory[] = (memory_get_usage() - $startMemory) / 1024 / 1024;

            // Clear for next iteration
            app()->forgetInstance(LazyLoader::class);
        }

        $this->newLine();
        $this->info('Benchmark Results:');
        $this->newLine();

        $avgEagerTime = round(array_sum($eagerTimes) / count($eagerTimes), 2);
        $avgLazyTime = round(array_sum($lazyTimes) / count($lazyTimes), 2);
        $avgEagerMemory = round(array_sum($eagerMemory) / count($eagerMemory), 2);
        $avgLazyMemory = round(array_sum($lazyMemory) / count($lazyMemory), 2);

        $timeSaved = round((($avgEagerTime - $avgLazyTime) / $avgEagerTime) * 100, 1);
        $memorySaved = round((($avgEagerMemory - $avgLazyMemory) / $avgEagerMemory) * 100, 1);

        $this->table(
            ['Metric', 'Eager Loading', 'Lazy Loading', 'Improvement'],
            [
                [
                    'Avg Boot Time',
                    "{$avgEagerTime}ms",
                    "{$avgLazyTime}ms",
                    "<info>{$timeSaved}% faster</info>"
                ],
                [
                    'Avg Memory Usage',
                    "{$avgEagerMemory}MB",
                    "{$avgLazyMemory}MB",
                    "<info>{$memorySaved}% less</info>"
                ],
            ]
        );

        $this->newLine();
        $this->info("✓ Lazy loading is {$timeSaved}% faster and uses {$memorySaved}% less memory!");

        return 0;
    }
}
