<?php

declare(strict_types=1);

namespace IronFlow\Events\Events;

use IronFlow\Core\BaseModule;

class ModuleBooting
{
    public function __construct(
        public readonly BaseModule $module
    ) {}
}
