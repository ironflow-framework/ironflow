<?php

declare(strict_types=1);

namespace IronFlow\Exceptions;

/**
 * DependencyException
 *
 * Thrown when there are dependency resolution issues.
 */
class DependencyException extends ModuleException
{
    protected array $missingDependencies = [];

    public function setMissingDependencies(array $dependencies): self
    {
        $this->missingDependencies = $dependencies;
        return $this;
    }

    public function getMissingDependencies(): array
    {
        return $this->missingDependencies;
    }
}
