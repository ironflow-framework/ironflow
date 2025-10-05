<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * ConfigurableInterface
 *
 * Optional interface for modules with configuration
 */
interface ConfigurableInterface
{
    /**
     * Get config path
     */
    public function configPath(): string;

    /**
     * Get config key
     */
    public function configKey(): string;

    /**
     * Merge module config with application config
     */
    public function mergeConfig(): void;
}

