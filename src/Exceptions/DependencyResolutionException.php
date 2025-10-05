<?php

declare(strict_types=1);

namespace IronFlow\Exceptions;

/**
 * DependencyResolutionException
 *
 * Thrown when module dependencies cannot be resolved
 */
class DependencyResolutionException extends IronFlowException
{
    public function __construct(string $message = 'Unable to resolve module dependencies', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
