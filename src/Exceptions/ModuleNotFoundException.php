<?php

declare(strict_types=1);

namespace IronFlow\Exceptions;

/**
 * ModuleNotFoundException
 *
 * Thrown when a required module is not found
 */
class ModuleNotFoundException extends ModuleException
{
    public function __construct(string $moduleName, int $code = 0, ?\Throwable $previous = null)
    {
        $message = "Module '{$moduleName}' not found";
        parent::__construct($message, $code, $previous);
        $this->setContext($moduleName, 'discovery');
    }
}
