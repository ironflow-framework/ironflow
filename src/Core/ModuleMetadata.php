<?php

declare(strict_types=1);

namespace IronFlow\Core;

/**
 * ModuleMetaData
 *
 * Encapsulates all metadata for a module including dependencies,
 * versions, authors, and configuration.
 *
 * @author Aure Dulvresse
 * @package IronFlow/Core
 * @since 1.0.0
 */
class ModuleMetaData
{
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly string $description = '',
        public readonly string $author = '',
        public readonly array $dependencies = [],
        public readonly array $provides = [],
        public readonly string $path = '',
        public readonly string $namespace = '',
    ) {
        $this->validate();
    }

    protected function validate(): void
    {
        if (empty($this->name)) {
            throw new \InvalidArgumentException('Module name cannot be empty');
        }

        if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $this->name)) {
            throw new \InvalidArgumentException(
                "Module name '{$this->name}' must start with uppercase and contain only alphanumeric characters"
            );
        }

        if (!preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9]+)?$/', $this->version)) {
            throw new \InvalidArgumentException(
                "Version '{$this->version}' must follow semantic versioning (e.g., 1.0.0 or 1.0.0-beta)"
            );
        }

        foreach ($this->dependencies as $dep) {
            if (!is_string($dep) || empty($dep)) {
                throw new \InvalidArgumentException('Dependencies must be non-empty strings');
            }
        }
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'author' => $this->author,
            'dependencies' => $this->dependencies,
            'provides' => $this->provides,
            'path' => $this->path,
            'namespace' => $this->namespace,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            version: $data['version'],
            description: $data['description'] ?? '',
            author: $data['author'] ?? '',
            dependencies: $data['dependencies'] ?? [],
            provides: $data['provides'] ?? [],
            path: $data['path'] ?? '',
            namespace: $data['namespace'] ?? '',
        );
    }

    public function hasDependency(string $moduleName): bool
    {
        return in_array($moduleName, $this->dependencies, true);
    }

    public function provides(string $service): bool
    {
        return in_array($service, $this->provides, true);
    }

    /**
     * Check version compatibility
     */
    public function isCompatibleWith(string $requiredVersion): bool
    {
        return version_compare($this->version, $requiredVersion, '>=');
    }
}
