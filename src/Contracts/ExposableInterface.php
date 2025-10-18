<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * ExposableInterface
 *
 * Allows module to expose services to other modules in a controlled manner.
 */
interface ExposableInterface
{
    /**
     * Expose services from this module.
     *
     * @return array
     */
    public function expose(): array;

    /**
     * Expose services from this module to linked modules
     *
     * @return array
     */
    public function exposeLinked(): array;
}
