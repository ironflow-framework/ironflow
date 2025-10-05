<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * RoutableInterface
 *
 * Optional interface for modules that define routes
 */
interface RoutableInterface
{
    /**
     * Register module routes
     */
    public function registerRoutes(): void;

    /**
     * Get route prefix
     */
    public function routePrefix(): ?string;

    /**
     * Get route middleware
     */
    public function routeMiddleware(): array;
}
