<?php

namespace IronFlow\Contracts;

/**
 * SeedableInterface
 *
 * Allows module to define database seeders.
 */
interface SeedableInterface
{
    /**
     * Get the seeders for this module.
     *
     * @return array
     */
    public function getSeeders(): array;

    /**
     * Get the seeder path.
     *
     * @return string
     */
    public function getSeederPath(): string;

    /**
     * Seed the module database.
     *
     * @param string|null $seederClass
     * @return void
     */
    public function seed(?string $seederClass = null): void;

    /**
     * Get seeder priority (higher = runs first).
     *
     * @return int
     */
    public function getSeederPriority(): int;
}
