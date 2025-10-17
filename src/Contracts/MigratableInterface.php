<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * MigratableInterface
 *
 * Allows module to manage database migrations.
 */
interface MigratableInterface
{
    /**
     * Get the migration path for this module.
     *
     * @return string
     */
    public function getMigrationPath(): string;

    /**
     * Get migration table prefix to avoid conflicts.
     *
     * @return string
     */
    public function getMigrationPrefix(): string;
}
