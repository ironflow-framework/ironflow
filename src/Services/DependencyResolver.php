<?php

declare(strict_types=1);

namespace IronFlow\Services;

use Illuminate\Support\Facades\Log;
use IronFlow\Core\BaseModule;
use IronFlow\Exceptions\ModuleException;

class DependencyResolver
{
    /**
     * Resolve modules in dependency order
     */
    public function resolve(array $modules): array
    {
        $resolved = [];
        $unresolved = [];

        foreach ($modules as $name => $module) {
            try {
                $this->resolveDependencies($name, $module, $modules, $resolved, $unresolved);
            } catch (ModuleException $e) {
                // Re-throw avec plus de contexte
                throw new ModuleException(
                    $e->getMessage() . "\n" .
                        "Resolution path: " . implode(' → ', array_keys($unresolved)),
                    0,
                    $e
                );
            }
        }

        return $resolved;
    }

    /**
     * Recursively resolve dependencies for a module
     */
    protected function resolveDependencies(
        string $name,
        BaseModule $module,
        array $allModules,
        array &$resolved,
        array &$unresolved
    ): void {
        $unresolved[$name] = true;

        $dependencies = $module->getMetadata()->dependencies;

        foreach ($dependencies as $dependency) {
            if (!isset($allModules[$dependency])) {
                $availableModules = array_keys($allModules);
                $suggestion = $this->findSimilarModule($dependency, $availableModules);

                $message = "Module '{$name}' depends on '{$dependency}', but it is not registered.\n" .
                    "Available modules: " . implode(', ', $availableModules);

                if ($suggestion) {
                    $message .= "\nDid you mean '{$suggestion}'?";
                }

                $message .= "\n\nSuggestions:\n" .
                    "  1. Install module: composer require vendor/{$dependency}\n" .
                    "  2. Create module: php artisan ironflow:module:make {$dependency}\n" .
                    "  3. Remove dependency from {$name}Module metadata";

                if (config('ironflow.exceptions.throw_on_missing_dependency', true)) {
                    throw new ModuleException($message);
                }

                Log::warning($message);
                continue;
            }

            if (!in_array($dependency, $resolved)) {
                if (isset($unresolved[$dependency])) {
                    $cycle = $this->formatCycle($unresolved, $dependency);
                    throw new ModuleException(
                        "Circular dependency detected:\n{$cycle}\n\n" .
                            "Solution: Remove one of the dependencies to break the cycle."
                    );
                }

                $this->resolveDependencies(
                    $dependency,
                    $allModules[$dependency],
                    $allModules,
                    $resolved,
                    $unresolved
                );
            }
        }

        $resolved[] = $name;
        unset($unresolved[$name]);
    }

    /**
     * Find similar module name using Levenshtein distance
     */
    protected function findSimilarModule(string $needle, array $haystack): ?string
    {
        $shortest = -1;
        $closest = null;

        foreach ($haystack as $module) {
            $lev = levenshtein(strtolower($needle), strtolower($module));

            if ($lev <= $shortest || $shortest < 0) {
                $closest = $module;
                $shortest = $lev;
            }
        }

        return $shortest <= 3 ? $closest : null;
    }

    /**
     * Format circular dependency cycle
     */
    protected function formatCycle(array $unresolved, string $start): string
    {
        $modules = array_keys($unresolved);
        $startIndex = array_search($start, $modules);
        $cycle = array_slice($modules, $startIndex);
        $cycle[] = $start; // Close the cycle

        return "  " . implode("\n  → ", $cycle);
    }

    /**
     * Get dependency tree
     */
    public function getTree(array $modules): array
    {
        $tree = [];

        foreach ($modules as $name => $module) {
            $tree[$name] = [
                'dependencies' => $module->getMetadata()->dependencies,
                'dependents' => $this->getDependents($name, $modules),
                'depth' => $this->calculateDepth($name, $modules),
            ];
        }

        return $tree;
    }

    /**
     * Get modules that depend on a given module
     */
    protected function getDependents(string $moduleName, array $modules): array
    {
        $dependents = [];

        foreach ($modules as $name => $module) {
            if ($module->getMetadata()->hasDependency($moduleName)) {
                $dependents[] = $name;
            }
        }

        return $dependents;
    }

    /**
     * Calculate dependency depth (0 = no dependencies)
     */
    protected function calculateDepth(string $moduleName, array $modules, array $visited = []): int
    {
        if (in_array($moduleName, $visited)) {
            return 0; // Circular, return 0
        }

        $visited[] = $moduleName;
        $module = $modules[$moduleName];
        $dependencies = $module->getMetadata()->dependencies;

        if (empty($dependencies)) {
            return 0;
        }

        $maxDepth = 0;
        foreach ($dependencies as $dependency) {
            if (isset($modules[$dependency])) {
                $depth = $this->calculateDepth($dependency, $modules, $visited);
                $maxDepth = max($maxDepth, $depth + 1);
            }
        }

        return $maxDepth;
    }
}
