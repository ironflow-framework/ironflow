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
    protected array $serviceInterfaces = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register public services from a module
     */
    public function registerPublicServices(string $moduleName, array $services): void
    {
        foreach ($services as $serviceName => $config) {
            $fullName = $this->buildServiceName($moduleName, $serviceName);

            if (isset($this->publicServices[$fullName])) {
                $this->handleConflict($fullName, $moduleName);
            }

            // Support array config avec interface
            if (is_array($config)) {
                $serviceClass = $config['class'];
                $interface = $config['interface'] ?? null;
            } else {
                $serviceClass = $config;
                $interface = null;
            }

            $this->publicServices[$fullName] = [
                'module' => $moduleName,
                'class' => $serviceClass,
                'interface' => $interface,
                'registered_at' => now(),
            ];

            // Bind to Laravel container avec résolution automatique des dépendances
            $this->app->singleton($fullName, function ($app) use ($serviceClass, $interface, $fullName) {
                try {
                    // Utiliser make() qui résout automatiquement les dépendances
                    $instance = $app->make($serviceClass);

                    // Vérifier l'interface si spécifiée
                    if ($interface && !($instance instanceof $interface)) {
                        throw new \RuntimeException(
                            "Service {$fullName} must implement {$interface}"
                        );
                    }

                    return $instance;
                } catch (\Throwable $e) {
                    Log::error("Failed to instantiate service {$fullName}", [
                        'class' => $serviceClass,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            });

            Log::debug("Public service {$fullName} registered from module {$moduleName}");
        }
    }

    /**
     * Resolve a service avec meilleure gestion d'erreurs
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
                    "Service '{$serviceName}' is not accessible from module '{$moduleContext}'. " .
                    "Allowed modules: " . implode(', ', $service['allowed_modules'] ?? [])
                );
            }

            return $this->getInstance($serviceName, $service);
        }

        // Service not found - provide helpful message
        $availableServices = array_merge(
            array_keys($this->publicServices),
            array_keys($this->linkedServices)
        );

        throw new ServiceNotFoundException(
            "Service '{$serviceName}' not found. " .
            "Available services: " . implode(', ', $availableServices) . ". " .
            "Did you mean: " . $this->suggestService($serviceName, $availableServices)
        );
    }

    /**
     * Get or create service instance avec meilleure gestion
     */
    protected function getInstance(string $serviceName, array $serviceData): mixed
    {
        if (isset($this->serviceInstances[$serviceName])) {
            return $this->serviceInstances[$serviceName];
        }

        try {
            // Utiliser le container qui gère automatiquement les dépendances
            $instance = $this->app->make($serviceName);

            $this->serviceInstances[$serviceName] = $instance;

            Log::debug("Service {$serviceName} instantiated successfully");

            return $instance;
        } catch (\Throwable $e) {
            Log::error("Failed to resolve service {$serviceName}", [
                'class' => $serviceData['class'],
                'module' => $serviceData['module'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceNotFoundException(
                "Failed to instantiate service '{$serviceName}': {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Suggest similar service name
     */
    protected function suggestService(string $needle, array $haystack): ?string
    {
        $shortest = -1;
        $closest = null;

        foreach ($haystack as $service) {
            $lev = levenshtein($needle, $service);
            if ($lev <= $shortest || $shortest < 0) {
                $closest = $service;
                $shortest = $lev;
            }
        }

        return $shortest <= 3 ? $closest : null;
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
