<?php

declare(strict_types=1);

namespace IronFlow\Core;

/**
 * ModuleState Enum
 *
 * Represents the lifecycle state of a module
 */
enum ModuleState: string
{
    case REGISTERED = 'registered';
    case PRELOADED = 'preloaded';
    case BOOTING = 'booting';
    case BOOTED = 'booted';
    case FAILED = 'failed';
    case DISABLED = 'disabled';
}
