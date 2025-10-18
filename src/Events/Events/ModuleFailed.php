<?php

declare(strict_types=1);

namespace IronFlow\Events\Events;

use IronFlow\Core\BaseModule;

class ModuleFailed
{
    public function __construct(
        public readonly BaseModule $module,
        public readonly \Throwable $error
    ) {}
}
