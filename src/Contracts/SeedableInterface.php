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
     * Get the seeder path.
     *
     * @return string
     */
    public function getSeedersPath(): string;

    /**
     * Seed the module database.
     *
     * @return void
     */
    public function runSeeders(): void;
}
