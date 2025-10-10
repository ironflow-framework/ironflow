<?php

declare(strict_types=1);

namespace IronFlow\Support;

use Illuminate\Support\Collection;

/**
 * ConflictDetector
 *
 * Detects conflicts between modules (routes, migrations, views, config).
 */
class ConflictDetector
{
    /**
     * Detect conflicts between modules.
     *
     * @param Collection $modules
     * @return array
     */
    public function detect(Collection $modules): array
    {
        $conflicts = [];

        if (config('ironflow.conflict_detection.routes', true)) {
            $conflicts['routes'] = $this->detectRouteConflicts($modules);
        }

        if (config('ironflow.conflict_detection.migrations', true)) {
            $conflicts['migrations'] = $this->detectMigrationConflicts($modules);
        }

        if (config('ironflow.conflict_detection.views', true)) {
            $conflicts['views'] = $this->detectViewConflicts($modules);
        }

        if (config('ironflow.conflict_detection.config', true)) {
            $conflicts['config'] = $this->detectConfigConflicts($modules);
        }

        return array_filter($conflicts);
    }

    /**
     * Detect route conflicts.
     *
     * @param Collection $modules
     * @return array
     */
    protected function detectRouteConflicts(Collection $modules): array
    {
        $routePrefixes = [];
        $conflicts = [];

        foreach ($modules as $name => $module) {
            if (method_exists($module, 'getRoutePrefix')) {
                $prefix = $module->getRoutePrefix();
                if ($prefix) {
                    if (isset($routePrefixes[$prefix])) {
                        $conflicts[] = [
                            'type' => 'route_prefix',
                            'prefix' => $prefix,
                            'modules' => [$routePrefixes[$prefix], $name],
                        ];
                    } else {
                        $routePrefixes[$prefix] = $name;
                    }
                }
            }
        }

        return $conflicts;
    }

    /**
     * Detect migration conflicts.
     *
     * @param Collection $modules
     * @return array
     */
    protected function detectMigrationConflicts(Collection $modules): array
    {
        $tables = [];
        $conflicts = [];

        foreach ($modules as $name => $module) {
            if (method_exists($module, 'getMigrationPath')) {
                $migrationPath = $module->getMigrationPath();
                if (is_dir($migrationPath)) {
                    $files = glob($migrationPath . '/*.php');
                    foreach ($files as $file) {
                        preg_match('/create_(\w+)_table/', basename($file), $matches);
                        if (isset($matches[1])) {
                            $tableName = $matches[1];
                            if (isset($tables[$tableName])) {
                                $conflicts[] = [
                                    'type' => 'migration_table',
                                    'table' => $tableName,
                                    'modules' => [$tables[$tableName], $name],
                                ];
                            } else {
                                $tables[$tableName] = $name;
                            }
                        }
                    }
                }
            }
        }

        return $conflicts;
    }

    /**
     * Detect view namespace conflicts.
     *
     * @param Collection $modules
     * @return array
     */
    protected function detectViewConflicts(Collection $modules): array
    {
        $namespaces = [];
        $conflicts = [];

        foreach ($modules as $name => $module) {
            if (method_exists($module, 'getViewNamespace')) {
                $namespace = $module->getViewNamespace();
                if (isset($namespaces[$namespace])) {
                    $conflicts[] = [
                        'type' => 'view_namespace',
                        'namespace' => $namespace,
                        'modules' => [$namespaces[$namespace], $name],
                    ];
                } else {
                    $namespaces[$namespace] = $name;
                }
            }
        }

        return $conflicts;
    }

    /**
     * Detect config key conflicts.
     *
     * @param Collection $modules
     * @return array
     */
    protected function detectConfigConflicts(Collection $modules): array
    {
        $configKeys = [];
        $conflicts = [];

        foreach ($modules as $name => $module) {
            if (method_exists($module, 'getConfigKey')) {
                $key = $module->getConfigKey();
                if (isset($configKeys[$key])) {
                    $conflicts[] = [
                        'type' => 'config_key',
                        'key' => $key,
                        'modules' => [$configKeys[$key], $name],
                    ];
                } else {
                    $configKeys[$key] = $name;
                }
            }
        }

        return $conflicts;
    }
}
