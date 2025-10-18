<?php

declare(strict_types=1);

namespace IronFlow\Services;

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
            $this->resolveDependencies($name, $module, $modules, $resolved, $unresolved);
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
                if (config('ironflow.exceptions.throw_on_missing_dependency', true)) {
                    throw new ModuleException(
                        "Module {$name} depends on {$dependency}, but it is not registered"
                    );
                }
                continue;
            }

            if (!in_array($dependency, $resolved)) {
                if (isset($unresolved[$dependency])) {
                    throw new ModuleException(
                        "Circular dependency detected: {$name} <-> {$dependency}"
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
     * Get dependency tree
     */
    public function getTree(array $modules): array
    {
        $tree = [];

        foreach ($modules as $name => $module) {
            $tree[$name] = [
                'dependencies' => $module->getMetadata()->dependencies,
                'dependents' => $this->getDependents($name, $modules),
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
}
