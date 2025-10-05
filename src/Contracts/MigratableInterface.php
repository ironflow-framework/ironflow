<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * MigratableInterface
 *
 * Optional interface for modules with migrations
 */
interface MigratableInterface
{
    /**
     * Get migrations path
     */
    public function migrationsPath(): string;

    /**
     * Run module migrations
     */
    public function migrate(): void;

    /**
     * Rollback module migrations
     */
    public function rollback(): void;
}
