<?php

declare(strict_types=1);

namespace IronFlow\Core;

/**
 * ModuleMetadata
 *
 * Defines module configuration and requirements
 */
class ModuleMetadata
{
    public function __construct(
        public readonly string $name,
        public readonly string $version = '1.0.0',
        public readonly string $description = '',
        public readonly array $authors = [],
        public readonly array $dependencies = [],
        public readonly bool $required = false,
        public readonly bool $enabled = true,
        public readonly int $priority = 0,
        public readonly array $provides = [],
        public readonly bool $allowOverride = false,
    ) {}

    /**
     * Create metadata from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? throw new \InvalidArgumentException('Module name is required'),
            version: $data['version'] ?? '1.0.0',
            description: $data['description'] ?? '',
            authors: $data['authors'] ?? [],
            dependencies: $data['dependencies'] ?? [],
            required: $data['required'] ?? false,
            enabled: $data['enabled'] ?? true,
            priority: $data['priority'] ?? 0,
            provides: $data['provides'] ?? [],
            allowOverride: $data['allowOverride'] ?? false,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'authors' => $this->authors,
            'dependencies' => $this->dependencies,
            'required' => $this->required,
            'enabled' => $this->enabled,
            'priority' => $this->priority,
            'provides' => $this->provides,
            'allowOverride' => $this->allowOverride,
        ];
    }

    /**
     * Check if module has dependencies
     */
    public function hasDependencies(): bool
    {
        return !empty($this->dependencies);
    }

    /**
     * Check if module depends on another
     */
    public function dependsOn(string $moduleName): bool
    {
        return in_array($moduleName, $this->dependencies);
    }

    /**
     * Validate metadata
     */
    public function validate(): bool
    {
        if (empty($this->name)) {
            throw new \InvalidArgumentException('Module name cannot be empty');
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $this->name)) {
            throw new \InvalidArgumentException(
                'Module name must start with a letter and contain only alphanumeric characters and underscores'
            );
        }

        if (!preg_match('/^\d+\.\d+\.\d+/', $this->version)) {
            throw new \InvalidArgumentException('Module version must follow semantic versioning (x.y.z)');
        }

        return true;
    }
}

