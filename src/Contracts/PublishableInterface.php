<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * PublishableInterface
 *
 * Optional interface for modules that can be published
 */
interface PublishableInterface
{
    /**
     * Get publishable assets
     */
    public function publishables(): array;

    /**
     * Publish module assets
     */
    public function publish(?string $tag = null): void;
}
