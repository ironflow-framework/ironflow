<?php

declare(strict_types=1);

namespace IronFlow\Events;

/**
 * ModuleEvent
 *
 * Event object for module events.
 */
class ModuleEvent
{
    public function __construct(
        public string $moduleName,
        public string $eventName,
        public array $data = [],
        public ?string $timestamp = null
    ) {
        $this->timestamp = $timestamp ?? now()->toDateTimeString();
    }

    public function getData(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function toArray(): array
    {
        return [
            'module' => $this->moduleName,
            'event' => $this->eventName,
            'data' => $this->data,
            'timestamp' => $this->timestamp,
        ];
    }
}