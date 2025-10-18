<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

interface PermissibleInterface
{
    /**
     * Define module permissions
     *
     * @return array Structure: ['permission-key' => 'Description']
     */
    public function definePermissions(): array;

    /**
     * Get permission groups for organization
     *
     * @return array Structure: ['group-name' => ['permission-key-1', 'permission-key-2']]
     */
    public function getPermissionGroups(): array;

    /**
     * Define default roles with their permissions
     *
     * @return array Structure: ['role-name' => ['permission-key-1', 'permission-key-2']]
     */
    public function defineRoles(): array;

    /**
     * Check if module requires authentication for access
     */
    public function requiresAuthentication(): bool;

    /**
     * Get module-specific middleware for permission checks
     */
    public function getPermissionMiddleware(): array;
}
