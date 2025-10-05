<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Core\Anvil;

/**
 * BootOrderCommand
 */
class BootOrderCommand extends Command
{
    protected $signature = 'ironflow:boot-order';

    protected $description = 'Display the module boot order';

    public function handle(Anvil $anvil): int
    {
        $anvil->load();
        $bootOrder = $anvil->getBootOrder();

        if (empty($bootOrder)) {
            $this->warn('No modules registered or boot order not calculated.');
            return self::SUCCESS;
        }

        $this->info('Module Boot Order:');
        $this->newLine();

        foreach ($bootOrder as $index => $moduleName) {
            $order = $index + 1;
            $module = $anvil->getModule($moduleName);
            $metadata = $module ? $module->metadata() : null;

            $deps = $metadata && !empty($metadata->dependencies)
                ? ' (depends on: ' . implode(', ', $metadata->dependencies) . ')'
                : '';

            $this->line("  {$order}. {$moduleName}{$deps}");
        }

        return self::SUCCESS;
    }
}
