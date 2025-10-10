<?php

declare(strict_types=1);

namespace IronFlow\Exceptions;

/**
 * ExposureException
 *
 * Thrown when there are service exposure/access issues.
 */
class ExposureException extends ModuleException
{
    protected ?string $serviceName = null;
    protected ?string $requesterModule = null;

    public function setServiceName(string $name): self
    {
        $this->serviceName = $name;
        return $this;
    }

    public function setRequesterModule(string $name): self
    {
        $this->requesterModule = $name;
        return $this;
    }

    public function getServiceName(): ?string
    {
        return $this->serviceName;
    }

    public function getRequesterModule(): ?string
    {
        return $this->requesterModule;
    }
}
