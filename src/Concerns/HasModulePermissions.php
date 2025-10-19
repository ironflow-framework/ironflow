<?php

namespace IronFlow\Concerns;

use IronFlow\Models\{Permission, Role};

trait HasModulePermissions
{
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'ironflow_permission_user');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'ironflow_role_user');
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('key', $permission)->exists()
            || $this->roles()->whereHas('permissions', function ($query) use ($permission) {
                $query->where('key', $permission);
            })->exists();
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function assignRole(string|Role $role): self
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching([$role->id]);
        return $this;
    }

    public function assignPermission(string|Permission $permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('key', $permission)->firstOrFail();
        }

        $this->permissions()->syncWithoutDetaching([$permission->id]);
        return $this;
    }

    public function removeRole(string|Role $role): self
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role) {
            $this->roles()->detach($role->id);
        }

        return $this;
    }

    public function removePermission(string|Permission $permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('key', $permission)->first();
        }

        if ($permission) {
            $this->permissions()->detach($permission->id);
        }

        return $this;
    }

    public function syncRoles(array $roles): self
    {
        $roleIds = collect($roles)->map(function ($role) {
            return is_string($role)
                ? Role::where('name', $role)->firstOrFail()->id
                : $role->id;
        });

        $this->roles()->sync($roleIds);
        return $this;
    }

    public function getPermissionKeys(): array
    {
        return $this->permissions()
            ->pluck('key')
            ->merge(
                $this->roles()
                    ->with('permissions')
                    ->get()
                    ->pluck('permissions')
                    ->flatten()
                    ->pluck('key')
            )
            ->unique()
            ->toArray();
    }
}
