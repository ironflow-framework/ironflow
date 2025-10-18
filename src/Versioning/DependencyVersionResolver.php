<?php

declare(strict_types=1);

namespace IronFlow\Versioning;

use IronFlow\Core\BaseModule;
use IronFlow\Exceptions\VersionConflictException;

class DependencyVersionResolver
{
    protected VersionManager $versionManager;

    public function __construct()
    {
        $this->versionManager = new VersionManager();
    }

    /**
     * Resolve module dependencies with version constraints
     */
    public function resolve(array $modules): array
    {
        $resolved = [];
        $constraints = [];

        // Collect all version constraints
        foreach ($modules as $name => $module) {
            $metadata = $module->getMetadata();

            foreach ($metadata->dependencies as $depName => $constraint) {
                if (is_int($depName)) {
                    // Old format: just module name
                    $depName = $constraint;
                    $constraint = '*';
                }

                $constraints[$depName][] = [
                    'from' => $name,
                    'constraint' => $constraint,
                ];
            }
        }

        // Validate all constraints can be satisfied
        foreach ($constraints as $moduleName => $moduleConstraints) {
            if (!isset($modules[$moduleName])) {
                throw new \RuntimeException(
                    "Module {$moduleName} required by: " .
                    implode(', ', array_column($moduleConstraints, 'from'))
                );
            }

            $actualVersion = $modules[$moduleName]->getMetadata()->version;

            foreach ($moduleConstraints as $constraintInfo) {
                if (!$this->versionManager->satisfies($actualVersion, $constraintInfo['constraint'])) {
                    throw new VersionConflictException(
                        "Version conflict for module '{$moduleName}':\n" .
                        "  Module '{$constraintInfo['from']}' requires: {$constraintInfo['constraint']}\n" .
                        "  But installed version is: {$actualVersion}\n\n" .
                        "Suggestions:\n" .
                        "  1. Update {$moduleName} to compatible version\n" .
                        "  2. Update {$constraintInfo['from']} dependencies\n" .
                        "  3. Use version constraint: composer require vendor/{$moduleName}:{$constraintInfo['constraint']}"
                    );
                }
            }
        }

        return $modules;
    }
}

