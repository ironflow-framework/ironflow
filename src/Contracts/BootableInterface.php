<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * BootableInterface
 *
 * Allows module to execute custom boot logic.
 */
interface BootableInterface
{
    /**
     * Boot the module.
     *
     * @return void
     */
    public function bootModule(): void;

    /**
     * Return module is booted
     * @return bool
     */
    public function isBooted(): bool;
}
