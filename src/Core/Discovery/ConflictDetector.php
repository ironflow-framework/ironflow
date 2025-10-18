<?php

namespace IronFlow\Core\Discovery;

use Illuminate\Contracts\Foundation\Application;
use IronFlow\Contracts\{RoutableInterface, ViewableInterface, ConfigurableInterface};
use Illuminate\Support\Facades\Log;

class ConflictDetector
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Detect conflicts between modules
     */
    public function detect(array $modules): array
    {
        $conflicts = [
            'routes' => [],
            'views' => [],
            'config' => [],
            'services' => [],
        ];

        // Detect route conflicts
        $conflicts['routes'] = $this->detectRouteConflicts($modules);

        // Detect view namespace conflicts
        $conflicts['views'] = $this->detectViewConflicts($modules);

        // Detect config key conflicts
        $conflicts['config'] = $this->detectConfigConflicts($modules);

        // Detect service name conflicts
        $conflicts['services'] = $this->detectServiceConflicts($modules);

        return array_filter($conflicts);
    }

    /**
     * Detect route conflicts
     */
    protected function detectRouteConflicts(array $modules): array
    {
        $conflicts = [];
        $routes = [];

        foreach ($modules as $name => $module) {
            if (!$module instanceof RoutableInterface) {
                continue;
            }

            // This is simplified - in reality, you'd parse route files
            $moduleRoutes = $this->extractRoutes($module);

            foreach ($moduleRoutes as $route) {
                if (isset($routes[$route])) {
                    $conflicts[] = [
                        'type' => 'route',
                        'path' => $route,
                        'modules' => [$routes[$route], $name],
                    ];
                } else {
                    $routes[$route] = $name;
                }
            }
        }

        return $conflicts;
    }

    /**
     * Detect view namespace conflicts
     */
    protected function detectViewConflicts(array $modules): array
    {
        $conflicts = [];
        $namespaces = [];

        foreach ($modules as $name => $module) {
            if (!$module instanceof ViewableInterface) {
                continue;
            }

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

        return $conflicts;
    }

    /**
     * Detect config key conflicts
     */
    protected function detectConfigConflicts(array $modules): array
    {
        $conflicts = [];
        $keys = [];

        foreach ($modules as $name => $module) {
            if (!$module instanceof ConfigurableInterface) {
                continue;
            }

            $key = $module->getConfigKey();

            if (isset($keys[$key])) {
                $conflicts[] = [
                    'type' => 'config_key',
                    'key' => $key,
                    'modules' => [$keys[$key], $name],
                ];
            } else {
                $keys[$key] = $name;
            }
        }

        return $conflicts;
    }

    /**
     * Detect service name conflicts
     */
    protected function detectServiceConflicts(array $modules): array
    {
        $conflicts = [];
        $services = [];

        foreach ($modules as $name => $module) {
            $exposed = array_keys($module->expose());

            foreach ($exposed as $serviceName) {
                $fullName = strtolower($name) . '.' . $serviceName;

                if (isset($services[$fullName])) {
                    $conflicts[] = [
                        'type' => 'service',
                        'name' => $fullName,
                        'modules' => [$services[$fullName], $name],
                    ];
                } else {
                    $services[$fullName] = $name;
                }
            }
        }

        return $conflicts;
    }

    /**
     * Handle detected conflicts
     */
    public function handle(array $conflicts): void
    {
        foreach ($conflicts as $type => $typeConflicts) {
            $action = config("ironflow.conflicts.{$type}", 'warning');

            foreach ($typeConflicts as $conflict) {
                $this->handleConflict($conflict, $action);
            }
        }
    }

    /**
     * Handle a single conflict
     */
    protected function handleConflict(array $conflict, string $action): void
    {
        $message = $this->formatConflictMessage($conflict);

        match ($action) {
            'exception' => throw new \RuntimeException($message),
            'warning' => Log::warning($message),
            'override' => Log::info($message . ' (override mode)'),
            default => null,
        };
    }

    /**
     * Format conflict message
     */
    protected function formatConflictMessage(array $conflict): string
    {
        $modules = implode(', ', $conflict['modules']);

        return "Conflict: {$conflict['type']} conflict between modules: {$modules}";}

    /**
     * Extract routes from module (simplified)
     */
    protected function extractRoutes(RoutableInterface $module): array
    {
        // In a real implementation, you would parse the route files
        // For now, return empty array
        return [];
    }
}
