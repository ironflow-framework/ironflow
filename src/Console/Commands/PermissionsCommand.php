<?php

declare(strict_types=1);

namespace IronFlow\Console\Commands;

use Illuminate\Console\Command;
use IronFlow\Permissions\ModulePermissionSystem;

/**
 * PermissionsCommand
 */
class PermissionsCommand extends Command
{
    protected $signature = 'ironflow:permissions
                            {--module= : Filter by module}
                            {--role= : Filter by role}
                            {--export : Export to JSON}';
    protected $description = 'Manage module permissions';

    public function handle(ModulePermissionSystem $permissions): int
    {
        if ($this->option('export')) {
            $this->line(json_encode($permissions->export(), JSON_PRETTY_PRINT));
            return 0;
        }

        $module = $this->option('module');
        $role = $this->option('role');

        if ($role) {
            return $this->showRolePermissions($permissions, $role);
        }

        if ($module) {
            return $this->showModulePermissions($permissions, $module);
        }

        return $this->showAllPermissions($permissions);
    }

    protected function showAllPermissions(ModulePermissionSystem $permissions): int
    {
        $all = $permissions->getAllPermissions();

        if (empty($all)) {
            $this->output->info('No permissions defined.');
            return 0;
        }

        foreach ($all as $moduleName => $modulePermissions) {
            $this->output->info("Module: {$moduleName}");

            foreach ($modulePermissions as $permission => $roles) {
                $rolesStr = is_array($roles) ? implode(', ', $roles) : $roles;
                $this->line("  • {$permission}: {$rolesStr}");
            }

            $this->newLine();
        }

        return 0;
    }

    protected function showModulePermissions(ModulePermissionSystem $permissions, string $module): int
    {
        $modulePermissions = $permissions->getModulePermissions($module);

        if (empty($modulePermissions)) {
            $this->output->info("No permissions for module: {$module}");
            return 0;
        }

        $this->output->info("Permissions for {$module}:");
        $this->newLine();

        foreach ($modulePermissions as $permission => $roles) {
            $rolesStr = is_array($roles) ? implode(', ', $roles) : $roles;
            $this->line("  • {$permission}: {$rolesStr}");
        }

        return 0;
    }

    protected function showRolePermissions(ModulePermissionSystem $permissions, string $role): int
    {
        $rolePermissions = $permissions->getPermissionsByRole($role);

        if (empty($rolePermissions)) {
            $this->output->info("No permissions for role: {$role}");
            return 0;
        }

        $this->output->info("Permissions for role '{$role}':");
        $this->newLine();

        foreach ($rolePermissions as $permission) {
            $this->line("  • {$permission}");
        }

        return 0;
    }
}
