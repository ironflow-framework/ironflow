<?php

declare(strict_types=1);

namespace IronFlow\Exceptions;

/**
 * ModuleBootException
 *
 * Thrown when a module fails to boot
 */
class ModuleBootException extends IronFlowException
{
    public function __construct(string $moduleName, string $reason = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = "Module '{$moduleName}' failed to boot";
        if ($reason) {
            $message .= ": {$reason}";
        }
        parent::__construct($message, $code, $previous);
    }
}
