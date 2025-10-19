<?php

declare(strict_types=1);

namespace IronFlow\Core;

/**
 * ModuleState
 *
 * Manages the lifecycle state of a module with transition validation
 * and event history tracking.
 *
 * @author Aure Dulvresse
 * @package IronFlow/Core
 * @since 4.0.0
 */
enum ModuleState: string
{
    case UNREGISTERED = 'unregistered';
    case REGISTERED = 'registered';
    case PRELOADED = 'preloaded';
    case BOOTING = 'booting';
    case BOOTED = 'booted';
    case FAILED = 'failed';
    case DISABLED = 'disabled';

    public function canTransitionTo(self $state): bool
    {
        return match ($this) {
            self::UNREGISTERED => $state === self::REGISTERED,
            self::REGISTERED => in_array($state, [self::PRELOADED, self::FAILED, self::DISABLED]),
            self::PRELOADED => in_array($state, [self::BOOTING, self::FAILED, self::DISABLED]),
            self::BOOTING => in_array($state, [self::BOOTED, self::FAILED]),
            self::BOOTED => in_array($state, [self::DISABLED]),
            self::FAILED => $state === self::DISABLED,
            self::DISABLED => false,
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::REGISTERED, self::PRELOADED, self::BOOTING, self::BOOTED]);
    }

    public function isBootable(): bool
    {
        return $this === self::PRELOADED;
    }
}
