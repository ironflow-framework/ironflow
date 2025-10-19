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
     * Register routes
     * @return void
     */
    public function registerRoutes(): void;

    /**
     * Get the route files for this module.
     *
     * @return string
     */
    public function getRoutesPath(): string;

    /**
     * Get route middleware for this module.
     *
     * @return array
     */
    public function getRouteMiddleware(): array;
}
