<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Permissions\ModulePermissionManager;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'ironflow:permissions:sync';
    protected $description = 'Sync module permissions to database';

    public function handle(ModulePermissionManager $permissionManager): int
    {
        $this->info('Syncing module permissions...');

        $permissionManager->syncToDatabase();

        $permissions = $permissionManager->getAllPermissions();
        $roles = $permissionManager->getAllRoles();

        $this->info("Synced " . count($permissions) . " permissions");
        $this->info("Synced " . count($roles) . " roles");

        $this->table(
            ['Module', 'Permission', 'Description'],
            collect($permissions)->map(fn($p) => [
                $p['module'],
                $p['key'],
                $p['description'],
            ])
        );

        return self::SUCCESS;
    }
}
