<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * ViewableInterface
 *
 * Allows module to register views with custom namespace and paths.
 */
interface ViewableInterface
{
    /**
     * Get the view namespace for this module.
     *
     * @return string
     */
    public function getViewNamespace(): string;

    /**
     * Get the view paths for this module.
     *
     * @return array
     */
    public function getViewPaths(): array;
}
