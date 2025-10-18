<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * TranslatableInterface
 *
 * Allows module to provide translations.
 */
interface TranslatableInterface
{
    /**
     * Get the translation path for this module.
     *
     * @return string
     */
    public function getTranslationPath(): string;

    /**
     * Get translation namespace.
     *
     * @return string
     */
    public function getTranslationNamespace(): string;
}
