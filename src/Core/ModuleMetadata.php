<?php

declare(strict_types=1);

namespace IronFlow\Core;

use Illuminate\Support\Collection;

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
    /**
     * @var string Module unique name
     */
    protected string $name;

    /**
     * @var string Module version (semantic versioning)
     */
    protected string $version;

    /**
     * @var string Module description
     */
    protected string $description;

    /**
     * @var array List of module authors
     */
    protected array $authors;

    /**
     * @var array List of module dependencies (module names)
     */
    protected array $dependencies;

    /**
     * @var array List of required modules that must be present
     */
    protected array $required;

    /**
     * @var bool Whether the module is enabled
     */
    protected bool $enabled;

    /**
     * @var int Module boot priority (higher = boots first)
     */
    protected int $priority;

    /**
     * @var array Services/features this module provides
     */
    protected array $provides;

    /**
     * @var bool Whether this module can be overridden by others
     */
    protected bool $allowOverride;

    /**
     * @var array Modules that are explicitly linked (can access exposed services)
     */
    protected array $linkedModules;

    /**
     * @var array Additional metadata
     */
    protected array $extra;

    /**
     * Create a new ModuleMetaData instance.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->name = $data['name'] ?? '';
        $this->version = $data['version'] ?? '1.0.0';
        $this->description = $data['description'] ?? '';
        $this->authors = $data['authors'] ?? [];
        $this->dependencies = $data['dependencies'] ?? [];
        $this->required = $data['required'] ?? [];
        $this->enabled = $data['enabled'] ?? true;
        $this->priority = $data['priority'] ?? 50;
        $this->provides = $data['provides'] ?? [];
        $this->allowOverride = $data['allowOverride'] ?? false;
        $this->linkedModules = $data['linkedModules'] ?? [];
        $this->extra = $data['extra'] ?? [];
    }

    /**
     * Get module name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get module version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get module description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get module authors.
     *
     * @return array
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * Get module dependencies.
     *
     * @return array
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Check if module has specific dependency.
     *
     * @param string $moduleName
     * @return bool
     */
    public function hasDependency(string $moduleName): bool
    {
        return in_array($moduleName, $this->dependencies);
    }

    /**
     * Get required modules.
     *
     * @return array
     */
    public function getRequired(): array
    {
        return $this->required;
    }

    /**
     * Check if module is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable the module.
     *
     * @return void
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable the module.
     *
     * @return void
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Get module priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Set module priority.
     *
     * @param int $priority
     * @return void
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * Get services provided by this module.
     *
     * @return array
     */
    public function getProvides(): array
    {
        return $this->provides;
    }

    /**
     * Check if module can be overridden.
     *
     * @return bool
     */
    public function allowsOverride(): bool
    {
        return $this->allowOverride;
    }

    /**
     * Get linked modules.
     *
     * @return array
     */
    public function getLinkedModules(): array
    {
        return $this->linkedModules;
    }

    /**
     * Check if module is linked with another.
     *
     * @param string $moduleName
     * @return bool
     */
    public function isLinkedWith(string $moduleName): bool
    {
        return in_array($moduleName, $this->linkedModules);
    }

    /**
     * Add a linked module.
     *
     * @param string $moduleName
     * @return void
     */
    public function addLinkedModule(string $moduleName): void
    {
        if (!$this->isLinkedWith($moduleName)) {
            $this->linkedModules[] = $moduleName;
        }
    }

    /**
     * Get extra metadata.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getExtra(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->extra;
        }

        return $this->extra[$key] ?? $default;
    }

    /**
     * Set extra metadata.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setExtra(string $key, mixed $value): void
    {
        $this->extra[$key] = $value;
    }

    /**
     * Convert to array.
     *
     * @return array
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
            'linkedModules' => $this->linkedModules,
            'extra' => $this->extra,
        ];
    }

    /**
     * Convert to collection.
     *
     * @return Collection
     */
    public function toCollection(): Collection
    {
        return collect($this->toArray());
    }
}
