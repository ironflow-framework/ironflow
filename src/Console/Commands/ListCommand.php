<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Facades\Anvil;

class ListCommand extends Command
{
    protected $signature = 'ironflow:list {--detailed : Show detailed information}';
    protected $description = 'List all registered modules';

    public function handle(): int
    {
        $modules = Anvil::getModules();

        if (empty($modules)) {
            $this->warn('No modules registered');
            return self::SUCCESS;
        }

        if ($this->option('detailed')) {
            $this->displayDetailed($modules);
        } else {
            $this->displaySimple($modules);
        }

        return self::SUCCESS;
    }

    protected function displaySimple(array $modules): void
    {
        $this->info('Registered Modules:');
        $this->newLine();

        $rows = [];
        foreach ($modules as $name => $module) {
            $metadata = $module->getMetadata();
            $rows[] = [
                $name,
                $metadata->version,
                $module->getState()->value,
                count($metadata->dependencies),
            ];
        }

        $this->table(
            ['Name', 'Version', 'State', 'Dependencies'],
            $rows
        );
    }

    protected function displayDetailed(array $modules): void
    {
        foreach ($modules as $name => $module) {
            $metadata = $module->getMetadata();

            $this->info("Module: {$name}");
            $this->line("  Version: {$metadata->version}");
            $this->line("  Description: {$metadata->description}");
            $this->line("  Author: {$metadata->author}");
            $this->line("  State: {$module->getState()->value}");
            $this->line("  Path: {$metadata->path}");
            $this->line("  Namespace: {$metadata->namespace}");

            if (!empty($metadata->dependencies)) {
                $this->line("  Dependencies: " . implode(', ', $metadata->dependencies));
            }

            if (!empty($metadata->provides)) {
                $this->line("  Provides: " . implode(', ', $metadata->provides));
            }

            $this->newLine();
        }
    }
}
