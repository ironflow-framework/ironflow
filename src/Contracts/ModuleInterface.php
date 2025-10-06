<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

use IronFlow\Core\ModuleMetadata;

/**
 * ModuleInterface
 *
 * Contract that all IronFlow modules must implement
 */
interface ModuleInterface
{
    /**
     * Get module metadata
     */
    public function metadata(): ModuleMetadata;

    /**
     * Register module services
     */
    public function register(): void;

    /**
     * Boot module (called after all modules are registered)
     */
    public function boot(): void;

    /**
     * Get module path
     */
    public function path(string $path = ''): string;

    /**
     * Get module namespace
     */
    public function namespace(): string;

}
