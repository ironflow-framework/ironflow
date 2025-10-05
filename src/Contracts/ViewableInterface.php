<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * ViewableInterface
 *
 * Optional interface for modules with views
 */
interface ViewableInterface
{
    /**
     * Get views path
     */
    public function viewsPath(): string;

    /**
     * Get view namespace
     */
    public function viewNamespace(): string;
}
