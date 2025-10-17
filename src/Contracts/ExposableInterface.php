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
     * Returns an array with 'public' and 'linked' keys:
     * [
     *     'public' => [...services available to all],
     *     'linked' => [...services available to linked modules only]
     * ]
     *
     * @return array
     */
    public function expose(): array;
}
