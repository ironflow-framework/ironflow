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
    protected string $moduleName = '';
    protected string $phase = '';

   public function setContext(string $moduleName, string $phase = ''): self
    {
        $this->moduleName = $moduleName;
        $this->phase = $phase;
        return $this;
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function getPhase(): string
    {
        return $this->phase;
    }

    public function getContext(): array
    {
        return [
            'module' => $this->moduleName,
            'phase' => $this->phase,
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }

    public function __toString(): string
    {
        $context = $this->getContext();
        return "ModuleException in module '{$context['module']}' during phase '{$context['phase']}': {$context['message']} (File: {$context['file']}, Line: {$context['line']})\nTrace:\n{$context['trace']}";
    }
}
