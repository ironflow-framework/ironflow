<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Facades\Anvil;

/**
 * InfoCommand
 */
class InfoModuleCommand extends Command
{
    protected $signature = 'ironflow:info {module : The module name}';

    protected $description = 'Display detailed information about a module';

    public function handle(): int
    {
        $moduleName = $this->argument('module');
        $module = Anvil::getModule($moduleName);

        if (!$module) {
            $this->error("Module '{$moduleName}' not found.");
            return self::FAILURE;
        }

        $modulePath = config('ironflow.path') . '/' . $moduleName;
        $metadata = $module->getMetadata();

        $this->info("Module: {$metadata->getName()}");
        $this->newLine();

        $this->line("  <fg=cyan>Version:</> {$metadata->getVersion()}");
        $this->line("  <fg=cyan>Description:</> {$metadata->getDescription()}");
        $this->line("  <fg=cyan>Authors:</> " . implode(', ', $metadata->getAuthors()));
        $this->line("  <fg=cyan>Status:</> " . ($metadata->isEnabled() ? 'Enabled' : 'Disabled'));
        $this->line("  <fg=cyan>Required:</> " . ($metadata->getRequired() ? 'Yes' : 'No'));
        $this->line("  <fg=cyan>Priority:</> {$metadata->getPriority()}");

        $this->newLine();
        $this->line("  <fg=cyan>Dependencies:</>");
        if (empty($metadata->getDependencies())) {
            $this->line("    None");
        } else {
            foreach ($metadata->getDependencies() as $dep) {
                $depModule = Anvil::getModule($dep);
                $status = $depModule ? '✓' : '✗';
                $this->line("    {$status} {$dep}");
            }
        }

        $this->newLine();
        $this->line("  <fg=cyan>Linked Modules:</>");
        if (empty($metadata->getLinkedModules())) {
            $this->line("    None");
        } else {
            foreach ($metadata->getLinkedModules() as $dep) {
                $depModule = Anvil::getModule($dep);
                $status = $depModule ? '✓' : '✗';
                $this->line("    {$status} {$dep}");
            }
        }

        $this->newLine();
        $this->line("  <fg=cyan>Provides:</>");
        if (empty($metadata->getProvides())) {
            $this->line("    None");
        } else {
            foreach ($metadata->getProvides() as $service) {
                $this->line("    • {$service}");
            }
        }

        $this->newLine();
        $this->line("  <fg=cyan>Path:</> {$modulePath}");

        return self::SUCCESS;
    }
}
