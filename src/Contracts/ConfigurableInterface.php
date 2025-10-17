<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * ConfigurableInterface
 *
 * Allows module to have its own configuration.
 */
interface ConfigurableInterface
{
    /**
     * Get the config file path.
     *
     * @return string
     */
    public function getConfigPath(): string;

    /**
     * Get the config key name.
     *
     * @return string
     */
    public function getConfigKey(): string;

    /**
     * Merge module config with application config.
     *
     * @return void
     */
    public function mergeConfig(): void;
}
