<?php

declare(strict_types=1);

namespace IronFlow\Exceptions;

/**
 * InvalidModuleException
 *
 * Thrown when a module is invalid or misconfigured
 */
class InvalidModuleException extends IronFlowException
{
    public function __construct(string $message = 'Invalid module configuration', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
