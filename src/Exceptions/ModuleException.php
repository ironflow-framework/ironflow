<?php

declare(strict_types=1);

namespace IronFlow\Exceptions;

/**
 * ModuleException
 *
 * Base exception for all module-related errors.
 */
class ModuleException extends \Exception
{
    protected string $moduleName;

    public function setModuleName(string $name): self
    {
        $this->moduleName = $name;
        return $this;
    }

    public function getModuleName(): string
    {
        return $this->moduleName ?? 'Unknown';
    }
}
