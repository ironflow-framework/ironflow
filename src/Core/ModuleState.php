<?php

declare(strict_types=1);

namespace IronFlow\Core;

use Carbon\Carbon;
use IronFlow\Exceptions\StateException;

/**
 * ModuleState
 *
 * Manages the lifecycle state of a module with transition validation
 * and event history tracking.
 * 
 * @author Aure Dulvresse
 * @package IronFlow/Core
 * @since 1.0.0
 */
class ModuleState
{
    /**
     * Module states
     */
    public const STATE_REGISTERED = 'registered';
    public const STATE_PRELOADED = 'preloaded';
    public const STATE_BOOTING = 'booting';
    public const STATE_BOOTED = 'booted';
    public const STATE_FAILED = 'failed';
    public const STATE_DISABLED = 'disabled';

    /**
     * @var string Current state
     */
    protected string $currentState = self::STATE_REGISTERED;

    /**
     * @var array State transition history
     */
    protected array $history = [];

    /**
     * @var array|null Last error information
     */
    protected ?array $lastError = null;

    /**
     * @var array Valid state transitions
     */
    protected array $validTransitions = [
        self::STATE_REGISTERED => [self::STATE_PRELOADED, self::STATE_FAILED, self::STATE_DISABLED],
        self::STATE_PRELOADED => [self::STATE_BOOTING, self::STATE_FAILED, self::STATE_DISABLED],
        self::STATE_BOOTING => [self::STATE_BOOTED, self::STATE_FAILED],
        self::STATE_BOOTED => [self::STATE_DISABLED, self::STATE_FAILED],
        self::STATE_FAILED => [self::STATE_REGISTERED, self::STATE_DISABLED],
        self::STATE_DISABLED => [self::STATE_REGISTERED],
    ];

    /**
     * Create a new ModuleState instance.
     *
     * @param string $initialState
     */
    public function __construct(string $initialState = self::STATE_REGISTERED)
    {
        $this->currentState = $initialState;
        $this->recordTransition($initialState, 'Initial state');
    }

    /**
     * Get current state.
     *
     * @return string
     */
    public function getCurrentState(): string
    {
        return $this->currentState;
    }

    /**
     * Transition to a new state.
     *
     * @param string $newState
     * @param string|null $reason
     * @return void
     * @throws StateException
     */
    public function transitionTo(string $newState, ?string $reason = null): void
    {
        if (!$this->canTransitionTo($newState)) {
            throw new StateException(
                "Invalid state transition from '{$this->currentState}' to '{$newState}'"
            );
        }

        $oldState = $this->currentState;
        $this->currentState = $newState;
        $this->recordTransition($newState, $reason, $oldState);
    }

    /**
     * Check if can transition to given state.
     *
     * @param string $state
     * @return bool
     */
    public function canTransitionTo(string $state): bool
    {
        return in_array($state, $this->validTransitions[$this->currentState] ?? []);
    }

    /**
     * Check if module is in a specific state.
     *
     * @param string $state
     * @return bool
     */
    public function is(string $state): bool
    {
        return $this->currentState === $state;
    }

    /**
     * Check if module is registered.
     *
     * @return bool
     */
    public function isRegistered(): bool
    {
        return $this->is(self::STATE_REGISTERED);
    }

    /**
     * Check if module is preloaded.
     *
     * @return bool
     */
    public function isPreloaded(): bool
    {
        return $this->is(self::STATE_PRELOADED);
    }

    /**
     * Check if module is booting.
     *
     * @return bool
     */
    public function isBooting(): bool
    {
        return $this->is(self::STATE_BOOTING);
    }

    /**
     * Check if module is booted.
     *
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->is(self::STATE_BOOTED);
    }

    /**
     * Check if module failed.
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->is(self::STATE_FAILED);
    }

    /**
     * Check if module is disabled.
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->is(self::STATE_DISABLED);
    }

    /**
     * Mark module as failed with error details.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function markAsFailed(\Throwable $exception): void
    {
        $this->lastError = [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'occurred_at' => Carbon::now()->toDateTimeString(),
        ];

        try {
            $this->transitionTo(self::STATE_FAILED, $exception->getMessage());
        } catch (StateException $e) {
            // Force transition even if invalid
            $this->currentState = self::STATE_FAILED;
            $this->recordTransition(self::STATE_FAILED, $exception->getMessage());
        }
    }

    /**
     * Get last error details.
     *
     * @return array|null
     */
    public function getLastError(): ?array
    {
        return $this->lastError;
    }

    /**
     * Clear last error.
     *
     * @return void
     */
    public function clearError(): void
    {
        $this->lastError = null;
    }

    /**
     * Get state history.
     *
     * @return array
     */
    public function getHistory(): array
    {
        return $this->history;
    }

    /**
     * Get the time module entered current state.
     *
     * @return Carbon|null
     */
    public function getCurrentStateTimestamp(): ?Carbon
    {
        $current = end($this->history);
        return $current ? Carbon::parse($current['timestamp']) : null;
    }

    /**
     * Get duration in current state (in seconds).
     *
     * @return float
     */
    public function getDurationInCurrentState(): float
    {
        $timestamp = $this->getCurrentStateTimestamp();
        return $timestamp ? Carbon::now()->diffInSeconds($timestamp) : 0;
    }

    /**
     * Record a state transition.
     *
     * @param string $state
     * @param string|null $reason
     * @param string|null $from
     * @return void
     */
    protected function recordTransition(string $state, ?string $reason = null, ?string $from = null): void
    {
        $this->history[] = [
            'state' => $state,
            'from' => $from,
            'reason' => $reason,
            'timestamp' => Carbon::now()->toDateTimeString(),
        ];
    }

    /**
     * Convert state to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'current_state' => $this->currentState,
            'last_error' => $this->lastError,
            'history' => $this->history,
        ];
    }
}
