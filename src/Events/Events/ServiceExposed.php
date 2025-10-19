<?php

declare(strict_types=1);

namespace IronFlow\Events\Events;

class ServiceExposed
{
    public function __construct(
        public readonly string $moduleName,
        public readonly string $serviceName,
        public readonly string $serviceClass
    ) {}
}
