<?php

declare(strict_types=1);

namespace IronFlow\Permissions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use IronFlow\Facades\Anvil;

/**
 * ModulePermissionSystem
 *
 * Central permission manager for modules.
 */
class ModulePermissionSystem
{
    protected array $permissions = [];
    protected string $cacheKey = 'ironflow.permissions';
    protected int $cacheTtl = 3600;

    public function __construct()
    {
        $this->loadPermissions();
        $this->registerGates();
    }

    /**
     * Load permissions from all modules.
     *
     * @return void
     */
    protected function loadPermissions(): void
    {
        $this->permissions = Cache::remember($this->cacheKey, $this->cacheTtl, function () {
            $permissions = [];
            $modules = Anvil::getModules();

            foreach ($modules as $name => $module) {
                if ($module instanceof PermissionableInterface) {
                    $permissions[$name] = $module->getPermissions();
                }
            }

            return $permissions;
        });
    }

    /**
     * Register Laravel gates for module permissions.
     *
     * @return void
     */
    protected function registerGates(): void
    {
        foreach ($this->permissions as $moduleName => $modulePermissions) {
            foreach ($modulePermissions as $permission => $roles) {
                $gateName = "{$moduleName}.{$permission}";
                
                Gate::define($gateName, function ($user) use ($roles) {
                    if (is_array($roles)) {
                        return in_array($user->role ?? 'guest', $roles);
                    }
                    return ($user->role ?? 'guest') === $roles;
                });
            }
        }
    }

    /**
     * Check if user can access module permission.
     *
     * @param string $moduleName
     * @param string $permission
     * @param mixed $user
     * @return bool
     */
    public function check(string $moduleName, string $permission, mixed $user = null): bool
    {
        $gateName = "{$moduleName}.{$permission}";
        $user = $user ?? auth()->user();

        if (!$user) {
            return $this->isPublicPermission($moduleName, $permission);
        }

        return Gate::forUser($user)->allows($gateName);
    }

    /**
     * Check if permission is public.
     *
     * @param string $moduleName
     * @param string $permission
     * @return bool
     */
    public function isPublicPermission(string $moduleName, string $permission): bool
    {
        $roles = $this->permissions[$moduleName][$permission] ?? [];
        
        if (is_array($roles)) {
            return in_array('guest', $roles) || in_array('*', $roles);
        }

        return $roles === 'guest' || $roles === '*';
    }

    /**
     * Get all permissions for a module.
     *
     * @param string $moduleName
     * @return array
     */
    public function getModulePermissions(string $moduleName): array
    {
        return $this->permissions[$moduleName] ?? [];
    }

    /**
     * Get all permissions.
     *
     * @return array
     */
    public function getAllPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Grant permission to roles.
     *
     * @param string $moduleName
     * @param string $permission
     * @param string|array $roles
     * @return void
     */
    public function grant(string $moduleName, string $permission, string|array $roles): void
    {
        if (!isset($this->permissions[$moduleName])) {
            $this->permissions[$moduleName] = [];
        }

        $currentRoles = $this->permissions[$moduleName][$permission] ?? [];
        
        if (!is_array($currentRoles)) {
            $currentRoles = [$currentRoles];
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        $this->permissions[$moduleName][$permission] = array_unique(array_merge($currentRoles, $roles));
        $this->clearCache();
        $this->registerGates();
    }

    /**
     * Revoke permission from roles.
     *
     * @param string $moduleName
     * @param string $permission
     * @param string|array $roles
     * @return void
     */
    public function revoke(string $moduleName, string $permission, string|array $roles): void
    {
        if (!isset($this->permissions[$moduleName][$permission])) {
            return;
        }

        $currentRoles = $this->permissions[$moduleName][$permission];
        
        if (!is_array($currentRoles)) {
            $currentRoles = [$currentRoles];
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        $this->permissions[$moduleName][$permission] = array_diff($currentRoles, $roles);
        $this->clearCache();
        $this->registerGates();
    }

    /**
     * Get permissions by role.
     *
     * @param string $role
     * @return array
     */
    public function getPermissionsByRole(string $role): array
    {
        $result = [];

        foreach ($this->permissions as $moduleName => $modulePermissions) {
            foreach ($modulePermissions as $permission => $roles) {
                if (is_array($roles) && in_array($role, $roles)) {
                    $result[] = "{$moduleName}.{$permission}";
                } elseif ($roles === $role) {
                    $result[] = "{$moduleName}.{$permission}";
                }
            }
        }

        return $result;
    }

    /**
     * Clear permission cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }

    /**
     * Export permissions to array.
     *
     * @return array
     */
    public function export(): array
    {
        return $this->permissions;
    }

    /**
     * Import permissions from array.
     *
     * @param array $permissions
     * @return void
     */
    public function import(array $permissions): void
    {
        $this->permissions = $permissions;
        $this->clearCache();
        $this->registerGates();
    }
}