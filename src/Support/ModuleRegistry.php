<?php

declare(strict_types=1);

namespace IronFlow\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use IronFlow\Core\BaseModule;

/**
 * ModuleRegistry
 *
 * Central registry for all registered modules.
 */
class ModuleRegistry
{
    protected Collection $modules;

    public function __construct()
    {
        $this->modules = collect();
    }

    public function register(string $name, BaseModule $module): void
    {
        $this->modules->put($name, $module);
        $this->updateCache();
    }

    public function get(string $name): ?BaseModule
    {
        return $this->modules->get($name);
    }

    public function has(string $name): bool
    {
        return $this->modules->has($name);
    }

    public function all(): Collection
    {
        return $this->modules;
    }

    protected function updateCache(): void
    {
        if (config('ironflow.cache.enabled', true)) {
            Cache::put(
                config('ironflow.cache.key', 'ironflow.modules'),
                $this->modules->keys()->toArray(),
                config('ironflow.cache.ttl', 3600)
            );
        }
    }
}
