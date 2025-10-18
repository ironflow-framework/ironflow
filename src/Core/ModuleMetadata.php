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
        if (empty($name)) {
            throw new \InvalidArgumentException('Module name cannot be empty');
        }

        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            throw new \InvalidArgumentException('Version must follow semver format');
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
}
