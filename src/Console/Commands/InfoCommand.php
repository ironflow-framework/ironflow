<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Core\Anvil;

/**
 * InfoCommand
 */
class InfoCommand extends Command
{
    protected $signature = 'ironflow:info {module : The module name}';

    protected $description = 'Display detailed information about a module';

    public function handle(Anvil $anvil): int
    {
        $moduleName = $this->argument('module');
        $module = $anvil->getModule($moduleName);

        if (!$module) {
            $this->error("Module '{$moduleName}' not found.");
            return self::FAILURE;
        }

        $metadata = $module->metadata();

        $this->info("Module: {$metadata->name}");
        $this->newLine();

        $this->line("  <fg=cyan>Version:</> {$metadata->version}");
        $this->line("  <fg=cyan>Description:</> {$metadata->description}");
        $this->line("  <fg=cyan>Authors:</> " . implode(', ', $metadata->authors));
        $this->line("  <fg=cyan>Status:</> " . ($metadata->enabled ? 'Enabled' : 'Disabled'));
        $this->line("  <fg=cyan>Required:</> " . ($metadata->required ? 'Yes' : 'No'));
        $this->line("  <fg=cyan>Priority:</> {$metadata->priority}");

        $this->newLine();
        $this->line("  <fg=cyan>Dependencies:</>");
        if (empty($metadata->dependencies)) {
            $this->line("    None");
        } else {
            foreach ($metadata->dependencies as $dep) {
                $depModule = $anvil->getModule($dep);
                $status = $depModule ? '✓' : '✗';
                $this->line("    {$status} {$dep}");
            }
        }

        $this->newLine();
        $this->line("  <fg=cyan>Provides:</>");
        if (empty($metadata->provides)) {
            $this->line("    None");
        } else {
            foreach ($metadata->provides as $service) {
                $this->line("    • {$service}");
            }
        }

        $this->newLine();
        $this->line("  <fg=cyan>Path:</> {$module->path()}");

        return self::SUCCESS;
    }
}
