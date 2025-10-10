<?php

declare(strict_types=1);

namespace IronFlow\Exceptions;

/**
 * StateException
 *
 * Thrown when an invalid state transition is attempted.
 */
class StateException extends ModuleException
{
    protected ?string $fromState = null;
    protected ?string $toState = null;

    public function setTransition(string $from, string $to): self
    {
        $this->fromState = $from;
        $this->toState = $to;
        return $this;
    }

    public function getFromState(): ?string
    {
        return $this->fromState;
    }

    public function getToState(): ?string
    {
        return $this->toState;
    }
}
