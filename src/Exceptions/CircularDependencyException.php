<?php

declare(strict_types=1);

namespace IronFlow\Exceptions;

/**
 * CircularDependencyException
 *
 * Thrown when a circular dependency is detected between modules
 */
class CircularDependencyException extends IronFlowException
{
    public function __construct(string $message = 'Circular dependency detected', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
