<?php

declare(strict_types=1);

namespace IronFlow\Contracts;

/**
 * ExportableInterface
 *
 * Allows module to be packaged for Packagist distribution.
 */
interface ExportableInterface
{
    /**
     * Prepare module for export to Packagist.
     *
     * Returns array with export configuration:
     * [
     *     'files' => [...files to include],
     *     'assets' => [...assets to include],
     *     'config' => [...config to include],
     *     'stubs' => [...stubs to include],
     *     'exclude' => [...patterns to exclude]
     * ]
     *
     * @return array
     */
    public function export(): array;

    /**
     * Get the package name for composer.
     *
     * @return string
     */
    public function getPackageName(): string;

    /**
     * Get package description.
     *
     * @return string
     */
    public function getPackageDescription(): string;

    /**
     * Get package dependencies (composer require).
     *
     * @return array
     */
    public function getPackageDependencies(): array;

    /**
     * Get package autoload configuration.
     *
     * @return array
     */
    public function getPackageAutoload(): array;
}
