<?php

declare(strict_types=1);

namespace IronFlow\Exceptions;


class ConflictException extends \Exception
{
    protected array $conflicts = [];

    public function setConflicts(array $conflicts): self
    {
        $this->conflicts = $conflicts;
        return $this;
    }

    public function getConflicts(): array
    {
        return $this->conflicts;
    }
}
