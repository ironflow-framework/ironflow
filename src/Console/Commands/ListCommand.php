<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Core\Anvil;

/**
 * ListCommand
 */
class ListCommand extends Command
{
    protected $signature = 'ironflow:list
                            {--enabled : Show only enabled modules}
                            {--disabled : Show only disabled modules}
                            {--format=table : Output format (table, json)}';

    protected $description = 'List all registered IronFlow modules';

    public function handle(Anvil $anvil): int
    {
        $modules = $anvil->getModules();

        if ($modules->isEmpty()) {
            $this->warn('No modules registered. Run "php artisan ironflow:discover" first.');
            return self::SUCCESS;
        }

        $filter = null;
        if ($this->option('enabled')) {
            $filter = 'enabled';
        } elseif ($this->option('disabled')) {
            $filter = 'disabled';
        }

        $data = $modules->map(function ($moduleData) use ($filter) {
            $metadata = $moduleData['metadata'];
            $state = $moduleData['state']->value;

            if ($filter === 'enabled' && !$metadata->enabled) {
                return null;
            }
            if ($filter === 'disabled' && $metadata->enabled) {
                return null;
            }

            return [
                'name' => $metadata->name,
                'version' => $metadata->version,
                'status' => $metadata->enabled ? '✓ Enabled' : '✗ Disabled',
                'state' => $state,
                'dependencies' => implode(', ', $metadata->dependencies) ?: 'None',
                'required' => $metadata->required ? 'Yes' : 'No',
            ];
        })->filter()->values();

        if ($data->isEmpty()) {
            $this->info('No modules match the filter criteria.');
            return self::SUCCESS;
        }

        if ($this->option('format') === 'json') {
            $this->line($data->toJson(JSON_PRETTY_PRINT));
        } else {
            $this->table(
                ['Name', 'Version', 'Status', 'State', 'Dependencies', 'Required'],
                $data->toArray()
            );
        }

        return self::SUCCESS;
    }
}
