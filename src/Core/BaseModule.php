<?php

declare(strict_types=1);

namespace IronFlow\Core;

use Illuminate\Foundation\Application;
use IronFlow\Exceptions\ModuleException;
use IronFlow\Contracts\{
    BootableInterface,
    ExposableInterface
};

/**
 * BaseModule
 *
 * Base class for all IronFlow modules.
 */
abstract class BaseModule implements BootableInterface, ExposableInterface
{
    protected Application $app;
    protected ModuleState $state = ModuleState::UNREGISTERED;
    protected ModuleMetaData $metadata;
    protected array $config = [];
    protected ?\Throwable $lastError = null;

    public function __construct()
    {
        $this->app = app();
        $this->metadata = $this->defineMetadata();
    }

    /**
     * Define module metadata - must be implemented by child classes
     */
    abstract protected function defineMetadata(): ModuleMetaData;

    /**
     * Register module services, bindings, etc.
     */
    public function register(): void
    {
        // Override in child classes if needed
    }

    /**
     * Boot module - load routes, views, etc.
     */
    public function bootModule(): void
    {
        // Override in child classes if needed
    }

    /**
     * Expose public services
     */
    public function expose(): array
    {
        return [];
    }

    /**
     * Expose services accessible only to specific linked modules
     */
    public function exposeLinked(): array
    {
        return [];
    }

    /**
     * Check if module is booted
     */
    public function isBooted(): bool
    {
        return $this->state === ModuleState::BOOTED;
    }

    /**
     * Get module metadata
     */
    public function getMetadata(): ModuleMetaData
    {
        return $this->metadata;
    }

    /**
     * Get module name
     */
    public function getName(): string
    {
        return $this->metadata->name;
    }

    /**
     * Get module state
     */
    public function getState(): ModuleState
    {
        return $this->state;
    }

    /**
     * Set module state with validation
     */
    public function setState(ModuleState $state): void
    {
        if (!$this->state->canTransitionTo($state)) {
            throw new ModuleException(
                "Invalid state transition from {$this->state->value} to {$state->value} for module {$this->getName()}"
            );
        }

        $this->state = $state;
    }

    /**
     * Get module configuration
     */
    public function getConfig(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        return data_get($this->config, $key, $default);
    }

    /**
     * Set module configuration
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Mark module as failed with error
     */
    public function markAsFailed(\Throwable $error): void
    {
        $this->lastError = $error;
        $this->state = ModuleState::FAILED;
    }

    /**
     * Get last error
     */
    public function getLastError(): ?\Throwable
    {
        return $this->lastError;
    }

    /**
     * Check if module is in active state
     */
    public function isActive(): bool
    {
        return $this->state->isActive();
    }

    /**
     * Get module path
     */
    public function getPath(?string $subPath = null): string
    {
        $basePath = $this->metadata->path;
        return $subPath ? $basePath . '/' . ltrim($subPath, '/') : $basePath;
    }

    /**
     * Get module namespace
     */
    public function getNamespace(): string
    {
        return $this->metadata->namespace;
    }
}
