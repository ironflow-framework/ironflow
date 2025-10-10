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
}
