<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * RoutableInterface
 *
 * Allows module to define its own routes.
 */
interface RoutableInterface
{
    /**
     * Get the route files for this module.
     *
     * @return array
     */
    public function getRoutesPath(): array;

    /**
     * Get route middleware for this module.
     *
     * @return array
     */
    public function getRouteMiddleware(): array;
}
