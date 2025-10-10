<?php

declare(strict_types=1);

namespace IronFlow\Support;

use Illuminate\Support\Collection;
use IronFlow\Exceptions\DependencyException;

/**
 * DependencyResolver
 *
 * Resolves module dependencies and determines boot order.
 */
class DependencyResolver
{
    /**
     * Resolve module dependencies and return boot order.
     *
     * @param Collection $modules
     * @return array
     * @throws DependencyException
     */
    public function resolve(Collection $modules): array
    {
        $sorted = [];
        $visited = [];
        $visiting = [];

        foreach ($modules as $name => $module) {
            if (!isset($visited[$name])) {
                $this->visit($name, $modules, $visited, $visiting, $sorted);
            }
        }

        // Sort by priority (higher priority first)
        usort($sorted, function ($a, $b) use ($modules) {
            $priorityA = $modules->get($a)->getMetadata()->getPriority();
            $priorityB = $modules->get($b)->getMetadata()->getPriority();
            return $priorityB <=> $priorityA;
        });

        return $sorted;
    }

    /**
     * Visit a module in dependency graph (DFS).
     *
     * @param string $name
     * @param Collection $modules
     * @param array $visited
     * @param array $visiting
     * @param array $sorted
     * @return void
     * @throws DependencyException
     */
    protected function visit(
        string $name,
        Collection $modules,
        array &$visited,
        array &$visiting,
        array &$sorted
    ): void {
        if (isset($visiting[$name])) {
            throw new DependencyException("Circular dependency detected: {$name}");
        }

        if (isset($visited[$name])) {
            return;
        }

        $visiting[$name] = true;

        $module = $modules->get($name);
        if (!$module) {
            unset($visiting[$name]);
            return;
        }

        $dependencies = $module->getMetadata()->getDependencies();

        foreach ($dependencies as $dependency) {
            if (!$modules->has($dependency)) {
                throw new DependencyException(
                    "Module {$name} depends on {$dependency} which is not registered"
                );
            }

            $this->visit($dependency, $modules, $visited, $visiting, $sorted);
        }

        unset($visiting[$name]);
        $visited[$name] = true;
        $sorted[] = $name;
    }

    /**
     * Validate all dependencies are met.
     *
     * @param Collection $modules
     * @return array Missing dependencies
     */
    public function validateDependencies(Collection $modules): array
    {
        $missing = [];

        foreach ($modules as $name => $module) {
            $dependencies = $module->getMetadata()->getDependencies();
            $required = $module->getMetadata()->getRequired();

            foreach (array_merge($dependencies, $required) as $dependency) {
                if (!$modules->has($dependency)) {
                    $missing[$name][] = $dependency;
                }
            }
        }

        return $missing;
    }
}
