<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Facades\Anvil;

/**
 * ListModulesCommand
 *
 * List all registered modules with their status.
 */
class ListModulesCommand extends Command
{
    protected $signature = 'ironflow:module:list
                            {--enabled : Show only enabled modules}
                            {--disabled : Show only disabled modules}';

    protected $description = 'List all IronFlow modules';

    public function handle(): int
    {
        $modules = Anvil::getModules();

        if ($this->option('enabled')) {
            $modules = Anvil::getEnabledModules();
        } elseif ($this->option('disabled')) {
            $modules = Anvil::getDisabledModules();
        }

        if ($modules->isEmpty()) {
            $this->output->info('No modules found.');
            return 0;
        }

        $headers = ['Name', 'Version', 'Status', 'State', 'Priority', 'Dependencies'];
        $rows = [];

        foreach ($modules as $name => $module) {
            $metadata = $module->getMetadata();
            $state = $module->getState();

            $rows[] = [
                $name,
                $metadata->getVersion(),
                $metadata->isEnabled() ? '<info>Enabled</info>' : '<comment>Disabled</comment>',
                $this->formatState($state->getCurrentState()),
                $metadata->getPriority(),
                implode(', ', $metadata->getDependencies()) ?: '-',
            ];
        }

        $this->output->table($headers, $rows);

        $stats = Anvil::getStatistics();
        $this->newLine();
        $this->output->info("Total: {$stats['total']} | Enabled: {$stats['enabled']} | Disabled: {$stats['disabled']} | Failed: {$stats['failed']}");

        return 0;
    }

    protected function formatState(string $state): string
    {
        return match ($state) {
            'booted' => '<info>Booted</info>',
            'failed' => '<error>Failed</error>',
            'disabled' => '<comment>Disabled</comment>',
            default => $state,
        };
    }
}
