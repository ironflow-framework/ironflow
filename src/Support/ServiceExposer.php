<?php

declare(strict_types=1);

namespace IronFlow\Support;

use IronFlow\Exceptions\ExposureException;

/**
 * ServiceExposer
 *
 * Manages service exposure between modules with lazy loading support.
 */
class ServiceExposer
{
    protected array $exposedServices = [];
    protected ?LazyLoader $lazyLoader = null;

    /**
     * Set lazy loader.
     *
     * @param LazyLoader $lazyLoader
     * @return void
     */
    public function setLazyLoader(LazyLoader $lazyLoader): void
    {
        $this->lazyLoader = $lazyLoader;
    }

    /**
     * Expose services from a module.
     *
     * @param string $moduleName
     * @param array $services ['public' => [...], 'linked' => [...]]
     * @return void
     */
    public function expose(string $moduleName, array $services): void
    {
        $this->exposedServices[$moduleName] = [
            'public' => $services['public'] ?? [],
            'linked' => $services['linked'] ?? [],
        ];
    }

    /**
     * Get a service from a module (with lazy loading).
     *
     * @param string $moduleName
     * @param string $serviceName
     * @param string|null $requesterModule
     * @return mixed
     * @throws ExposureException
     */
    public function getService(string $moduleName, string $serviceName, ?string $requesterModule = null): mixed
    {
        // Try to lazy load module if not exposed yet
        if (!isset($this->exposedServices[$moduleName]) && $this->lazyLoader) {
            $this->lazyLoader->loadByService($moduleName, $serviceName);
        }

        if (!isset($this->exposedServices[$moduleName])) {
            throw new ExposureException("Module {$moduleName} does not expose any services");
        }

        $services = $this->exposedServices[$moduleName];

        // Check public services first
        if (isset($services['public'][$serviceName])) {
            return $services['public'][$serviceName];
        }

        // Check linked services
        if ($requesterModule && isset($services['linked'][$requesterModule][$serviceName])) {
            return $services['linked'][$requesterModule][$serviceName];
        }

        // Check if service exists but requester has no access
        $strictMode = config('ironflow.service_exposure.strict_mode', true);
        if ($strictMode) {
            throw new ExposureException(
                "Service {$serviceName} from module {$moduleName} is not accessible" .
                ($requesterModule ? " to module {$requesterModule}" : "")
            );
        }

        throw new ExposureException("Service {$serviceName} not found in module {$moduleName}");
    }

    /**
     * Check if module has a service.
     *
     * @param string $moduleName
     * @param string $serviceName
     * @return bool
     */
    public function hasService(string $moduleName, string $serviceName): bool
    {
        if (!isset($this->exposedServices[$moduleName])) {
            return false;
        }

        $services = $this->exposedServices[$moduleName];
        return isset($services['public'][$serviceName]);
    }

    /**
     * Get all exposed services from a module.
     *
     * @param string $moduleName
     * @return array
     */
    public function getExposedServices(string $moduleName): array
    {
        return $this->exposedServices[$moduleName] ?? ['public' => [], 'linked' => []];
    }
}
