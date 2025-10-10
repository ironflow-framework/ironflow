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
     * @return array ['web' => 'path/to/web.php', 'api' => 'path/to/api.php']
     */
    public function getRouteFiles(): array;

    /**
     * Get route middleware for this module.
     *
     * @return array
     */
    public function getRouteMiddleware(): array;

    /**
     * Get route prefix (if any).
     *
     * @return string|null
     */
    public function getRoutePrefix(): ?string;

    /**
     * Register module routes.
     *
     * @return void
     */
    public function registerRoutes(): void;
}
