<?php

declare(strict_types=1);

namespace IronFlow\Permissions;

/**
 * PermissionableInterface
 *
 * Interface for modules with permissions.
 */
interface PermissionableInterface
{
    /**
     * Get module permissions.
     *
     * @return array
     */
    public function getPermissions(): array;

    /**
     * Check if module has permission.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool;

    /**
     * Grant permission.
     *
     * @param string $permission
     * @param string|array $roles
     * @return void
     */
    public function grantPermission(string $permission, string|array $roles): void;

    /**
     * Revoke permission.
     *
     * @param string $permission
     * @param string|array $roles
     * @return void
     */
    public function revokePermission(string $permission, string|array $roles): void;
}