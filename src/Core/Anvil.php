<?php

declare(strict_types=1);

namespace IronFlow\Core;

use IronFlow\Contracts\ModuleInterface;
use IronFlow\Exceptions\CircularDependencyException;
use IronFlow\Exceptions\ModuleNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Anvil - The IronFlow Module Manager
 *
 * Responsible for loading, registering, and managing module lifecycle
 */
class Anvil
{
    protected Collection $modules;
    protected Collection $loadedModules;
    protected array $bootOrder = [];
    protected bool $isBooted = false;

    public function __construct()
    {
        $this->modules = collect();
        $this->loadedModules = collect();
    }

    /**
     * Register a module with the Anvil
     */
    public function register(ModuleInterface $module): self
    {
        $metadata = $module->metadata();

        if (!$metadata->enabled) {
            Log::info("Module {$metadata->name} is disabled, skipping registration");
            return $this;
        }

        $this->modules->put($metadata->name, [
            'instance' => $module,
            'metadata' => $metadata,
            'state' => ModuleState::REGISTERED,
        ]);

        Log::info("Module {$metadata->name} registered successfully");

        return $this;
    }

    /**
     * Load all registered modules
     */
    public function load(): self
    {
        $this->validateDependencies();
        $this->calculateBootOrder();

        return $this;
    }

    /**
     * Boot all modules in dependency order
     */
    public function boot(): void
    {
        if ($this->isBooted) {
            return;
        }

        foreach ($this->bootOrder as $moduleName) {
            $this->bootModule($moduleName);
        }

        $this->isBooted = true;
        Log::info('All IronFlow modules booted successfully');
    }

    /**
     * Boot a single module
     */
    protected function bootModule(string $name): void
    {
        $moduleData = $this->modules->get($name);

        if (!$moduleData) {
            throw new ModuleNotFoundException("Module {$name} not found");
        }

        try {
            $this->updateModuleState($name, ModuleState::BOOTING);

            // Preload dependencies first
            $this->preloadDependencies($moduleData['metadata']);

            // Boot the module
            $moduleData['instance']->boot();

            $this->updateModuleState($name, ModuleState::BOOTED);
            $this->loadedModules->put($name, $moduleData);

            Log::info("Module {$name} booted successfully");
        } catch (\Exception $e) {
            $this->updateModuleState($name, ModuleState::FAILED);
            Log::error("Module {$name} failed to boot: {$e->getMessage()}");

            if ($moduleData['metadata']->required) {
                throw $e;
            }
        }
    }

    /**
     * Preload module dependencies
     */
    protected function preloadDependencies(ModuleMetadata $metadata): void
    {
        foreach ($metadata->dependencies as $dependency) {
            if (!$this->isModuleBooted($dependency)) {
                $this->bootModule($dependency);
            }
        }
    }

    /**
     * Validate all module dependencies
     */
    protected function validateDependencies(): void
    {
        foreach ($this->modules as $name => $data) {
            $this->checkCircularDependencies($name, []);
            $this->checkMissingDependencies($data['metadata']);
        }
    }

    /**
     * Check for circular dependencies
     */
    protected function checkCircularDependencies(string $moduleName, array $visited): void
    {
        if (in_array($moduleName, $visited)) {
            $cycle = implode(' -> ', array_merge($visited, [$moduleName]));
            throw new CircularDependencyException(
                "Circular dependency detected: {$cycle}"
            );
        }

        $moduleData = $this->modules->get($moduleName);
        if (!$moduleData) {
            return;
        }

        $visited[] = $moduleName;
        $dependencies = $moduleData['metadata']->dependencies;

        foreach ($dependencies as $dependency) {
            $this->checkCircularDependencies($dependency, $visited);
        }
    }

    /**
     * Check for missing dependencies
     */
    protected function checkMissingDependencies(ModuleMetadata $metadata): void
    {
        foreach ($metadata->dependencies as $dependency) {
            if (!$this->modules->has($dependency)) {
                $message = "Module {$metadata->name} depends on missing module: {$dependency}";

                if ($metadata->required) {
                    throw new ModuleNotFoundException($message);
                }

                Log::warning($message);
            }
        }
    }

    /**
     * Calculate boot order using topological sort
     */
    protected function calculateBootOrder(): void
    {
        $sorted = [];
        $visiting = [];

        foreach ($this->modules->keys() as $name) {
            $this->topologicalSort($name, $visiting, $sorted);
        }

        $this->bootOrder = $sorted;
    }

    /**
     * Topological sort helper
     */
    protected function topologicalSort(string $name, array &$visiting, array &$sorted): void
    {
        if (in_array($name, $sorted)) {
            return;
        }

        $moduleData = $this->modules->get($name);
        if (!$moduleData) {
            return;
        }

        $visiting[] = $name;

        foreach ($moduleData['metadata']->dependencies as $dependency) {
            if ($this->modules->has($dependency)) {
                $this->topologicalSort($dependency, $visiting, $sorted);
            }
        }

        $sorted[] = $name;
    }

    /**
     * Update module state
     */
    protected function updateModuleState(string $name, ModuleState $state): void
    {
        if ($this->modules->has($name)) {
            $moduleData = $this->modules->get($name);
            $moduleData['state'] = $state;
            $this->modules->put($name, $moduleData);
        }
    }

    /**
     * Check if module is booted
     */
    public function isModuleBooted(string $name): bool
    {
        $moduleData = $this->loadedModules->get($name);
        return $moduleData && $moduleData['state'] === ModuleState::BOOTED;
    }

    /**
     * Get a module instance
     */
    public function getModule(string $name): ?ModuleInterface
    {
        $moduleData = $this->modules->get($name);
        return $moduleData['instance'] ?? null;
    }

    /**
     * Get all registered modules
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    /**
     * Get boot order
     */
    public function getBootOrder(): array
    {
        return $this->bootOrder;
    }

    /**
     * Check if Anvil has booted
     */
    public function hasBooted(): bool
    {
        return $this->isBooted;
    }
}
