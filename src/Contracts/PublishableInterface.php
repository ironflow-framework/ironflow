<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * PublishableInterface
 *
 * Allows module to publish assets, config, and other files.
 */
interface PublishableInterface
{
    /**
     * Get publishable assets.
     *
     * @return array ['source' => 'destination']
     */
    public function getPublishableAssets(): array;

    /**
     * Get publishable config files.
     *
     * @return array
     */
    public function getPublishableConfig(): array;

    /**
     * Get publishable views.
     *
     * @return array
     */
    public function getPublishableViews(): array;
}
