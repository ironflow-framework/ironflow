<?php

declare(strict_types=1);

namespace IronFlow\Exceptions;

/**
 * ModuleConflictException
 *
 * Thrown when modules have conflicting services or configurations
 */
class ModuleConflictException extends IronFlowException
{
    public function __construct(string $service, array $modules, int $code = 0, ?\Throwable $previous = null)
    {
        $moduleList = implode(', ', $modules);
        $message = "Service '{$service}' conflict between modules: {$moduleList}";
        parent::__construct($message, $code, $previous);
    }
}
