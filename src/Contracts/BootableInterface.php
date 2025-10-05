<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * BootableInterface
 *
 * Optional interface for modules that need early boot hooks
 */
interface BootableInterface
{
    /**
     * Called before the module boots
     */
    public function booting(): void;

    /**
     * Called after the module boots
     */
    public function booted(): void;
}
