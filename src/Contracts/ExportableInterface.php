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
     * Get package name for Packagist
     * Format: vendor/package-name
     */
    public function getPackageName(): string;

    /**
     * Get package description
     */
    public function getPackageDescription(): string;

    /**
     * Get package keywords for Packagist
     */
    public function getPackageKeywords(): array;

    /**
     * Get package license (MIT, GPL-3.0, etc.)
     */
    public function getPackageLicense(): string;

    /**
     * Get package homepage URL
     */
    public function getPackageHomepage(): ?string;

    /**
     * Get package authors
     * Format: [['name' => 'John Doe', 'email' => 'john@example.com', 'homepage' => '...', 'role' => 'Developer']]
     */
    public function getPackageAuthors(): array;

    /**
     * Get package dependencies (composer require)
     */
    public function getPackageDependencies(): array;

    /**
     * Get package dev dependencies (composer require-dev)
     */
    public function getPackageDevDependencies(): array;

    /**
     * Get files/folders to exclude from package
     */
    public function getExcludedPaths(): array;

    /**
     * Get additional composer.json data
     */
    public function getAdditionalComposerData(): array;

    /**
     * Prepare module for publication (optional hook)
     * Called before packaging
     */
    public function beforePublish(): void;

    /**
     * Post-publish hook (optional)
     */
    public function afterPublish(): void;
}