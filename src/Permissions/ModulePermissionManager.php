<?php

declare(strict_types=1);

namespace IronFlow\Permissions;

use Illuminate\Contracts\Foundation\Application;
use IronFlow\Core\BaseModule;
use IronFlow\Contracts\PermissibleInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;

class ModulePermissionManager
{
    protected Application $app;
    protected array $permissions = [];
    protected array $roles = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register permissions from a module
     */
    public function registerModulePermissions(BaseModule $module): void
    {
        if (!$module instanceof PermissibleInterface) {
            return;
        }

        $moduleName = strtolower($module->getName());
        $permissions = $module->definePermissions();

        foreach ($permissions as $key => $description) {
            $fullKey = "{$moduleName}.{$key}";

            $this->permissions[$fullKey] = [
                'module' => $moduleName,
                'key' => $key,
                'description' => $description,
                'full_key' => $fullKey,
            ];

            // Register with Laravel Gate
            Gate::define($fullKey, function ($user) use ($fullKey) {
                return $this->userHasPermission($user, $fullKey);
            });
        }

        // Register roles
        $roles = $module->defineRoles();
        foreach ($roles as $roleName => $rolePermissions) {
            $fullRoleName = "{$moduleName}.{$roleName}";
            $this->roles[$fullRoleName] = array_map(
                fn($p) => "{$moduleName}.{$p}",
                $rolePermissions
            );
        }
    }

    /**
     * Check if user has permission
     */
    public function userHasPermission($user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        // Super admin bypass
        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return true;
        }

        // Check cache first
        $cacheKey = "ironflow.permissions.{$user->id}.{$permission}";

        return Cache::remember($cacheKey, 3600, function () use ($user, $permission) {
            // Check direct permissions
            if (method_exists($user, 'hasPermission') && $user->hasPermission($permission)) {
                return true;
            }

            // Check via roles
            if (method_exists($user, 'roles')) {
                foreach ($user->roles as $role) {
                    if ($this->roleHasPermission($role->name, $permission)) {
                        return true;
                    }
                }
            }

            return false;
        });
    }

    /**
     * Check if role has permission
     */
    public function roleHasPermission(string $roleName, string $permission): bool
    {
        return isset($this->roles[$roleName]) && in_array($permission, $this->roles[$roleName]);
    }

    /**
     * Get all permissions for a module
     */
    public function getModulePermissions(string $moduleName): array
    {
        $moduleName = strtolower($moduleName);

        return array_filter(
            $this->permissions,
            fn($p) => $p['module'] === $moduleName
        );
    }

    /**
     * Get all registered permissions
     */
    public function         getAllPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Get all roles
     */
    public function getAllRoles(): array
    {
        return $this->roles;
    }

    /**
     * Sync permissions to database
     */
    public function syncToDatabase(): void
    {
        $permissionModel = config('ironflow.permissions.model', \IronFlow\Models\Permission::class);

        foreach ($this->permissions as $fullKey => $data) {
            $permissionModel::updateOrCreate(
                ['key' => $fullKey],
                [
                    'module' => $data['module'],
                    'description' => $data['description'],
                ]
            );
        }
    }

    /**
     * Clear permission cache for user
     */
    public function clearUserCache($userId): void
    {
        $keys = Cache::get("ironflow.permissions.user.{$userId}.keys", []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget("ironflow.permissions.user.{$userId}.keys");
    }
}
