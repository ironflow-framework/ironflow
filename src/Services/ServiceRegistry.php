<?php

declare(strict_types=1);

namespace IronFlow\Services;

use Illuminate\Contracts\Foundation\Application;
use IronFlow\Exceptions\ServiceNotFoundException;
use Illuminate\Support\Facades\Log;

class ServiceRegistry
{
    protected Application $app;
    protected array $publicServices = [];
    protected array $linkedServices = [];
    protected array $serviceInstances = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register public services from a module
     */
    public function registerPublicServices(string $moduleName, array $services): void
    {
        foreach ($services as $serviceName => $serviceClass) {
            $fullName = $this->buildServiceName($moduleName, $serviceName);

            if (isset($this->publicServices[$fullName])) {
                $this->handleConflict($fullName, $moduleName);
            }

            $this->publicServices[$fullName] = [
                'module' => $moduleName,
                'class' => $serviceClass,
                'registered_at' => now(),
            ];

            // Bind to Laravel container
            $this->app->singleton($fullName, function ($app) use ($serviceClass) {
                return $app->make($serviceClass);
            });

            Log::debug("Public service {$fullName} registered from module {$moduleName}");
        }
    }

    /**
     * Register linked services (accessible only by specific modules)
     */
    public function registerLinkedServices(string $moduleName, array $services): void
    {
        foreach ($services as $serviceName => $config) {
            $fullName = $this->buildServiceName($moduleName, $serviceName);

            $this->linkedServices[$fullName] = [
                'module' => $moduleName,
                'class' => $config['class'] ?? $config,
                'allowed_modules' => $config['allowed'] ?? [],
                'registered_at' => now(),
            ];

            Log::debug("Linked service {$fullName} registered from module {$moduleName}");
        }
    }

    /**
     * Resolve a service
     */
    public function resolve(string $serviceName, ?string $moduleContext = null): mixed
    {
        // Try public services first
        if (isset($this->publicServices[$serviceName])) {
            return $this->getInstance($serviceName, $this->publicServices[$serviceName]);
        }

        // Try linked services
        if (isset($this->linkedServices[$serviceName])) {
            $service = $this->linkedServices[$serviceName];

            // Check access permission
            if ($moduleContext && !in_array($moduleContext, $service['allowed_modules'] ?? [])) {
                throw new ServiceNotFoundException(
                    "Service {$serviceName} is not accessible from module {$moduleContext}"
                );
            }

            return $this->getInstance($serviceName, $service);
        }

        throw new ServiceNotFoundException("Service {$serviceName} not found");
    }

    /**
     * Get or create service instance
     */
    protected function getInstance(string $serviceName, array $serviceData): mixed
    {
        if (isset($this->serviceInstances[$serviceName])) {
            return $this->serviceInstances[$serviceName];
        }

        $instance = $this->app->make($serviceData['class']);

        if (isset($serviceData['interface']) && !($instance instanceof $serviceData['interface'])) {
            throw new \RuntimeException(
                "Service {$serviceName} must implement {$serviceData['interface']}"
            );
        }

        $this->serviceInstances[$serviceName] = $instance;

        return $instance;
    }

    /**
     * Unregister all services from a module
     */
    public function unregisterModule(string $moduleName): void
    {
        // Remove public services
        $this->publicServices = array_filter(
            $this->publicServices,
            fn($service) => $service['module'] !== $moduleName
        );

        // Remove linked services
        $this->linkedServices = array_filter(
            $this->linkedServices,
            fn($service) => $service['module'] !== $moduleName
        );

        // Clear instances
        $this->serviceInstances = array_filter(
            $this->serviceInstances,
            function ($instance, $name) use ($moduleName) {
                return !str_starts_with($name, $moduleName . '.');
            },
            ARRAY_FILTER_USE_BOTH
        );

        Log::info("All services unregistered for module {$moduleName}");
    }

    /**
     * Build full service name
     */
    protected function buildServiceName(string $moduleName, string $serviceName): string
    {
        return strtolower($moduleName) . '.' . $serviceName;
    }

    /**
     * Handle service name conflicts
     */
    protected function handleConflict(string $serviceName, string $moduleName): void
    {
        $conflictAction = config('ironflow.conflicts.services', 'exception');

        $existingModule = $this->publicServices[$serviceName]['module'];
        $message = "Service name conflict: {$serviceName} already registered by module {$existingModule}";

        match ($conflictAction) {
            'exception' => throw new \RuntimeException($message),
            'warning' => Log::warning($message),
            'override' => Log::info("{$message}. Overriding with module {$moduleName}"),
            default => null,
        };
    }

    /**
     * Get all public services
     */
    public function getPublicServices(): array
    {
        return $this->publicServices;
    }

    /**
     * Get all linked services
     */
    public function getLinkedServices(): array
    {
        return $this->linkedServices;
    }

    /**
     * Check if service exists
     */
    public function has(string $serviceName): bool
    {
        return isset($this->publicServices[$serviceName]) || isset($this->linkedServices[$serviceName]);
    }
}
