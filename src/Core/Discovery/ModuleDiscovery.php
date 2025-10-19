<?php

declare(strict_types=1);

namespace IronFlow\Core\Discovery;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use IronFlow\Core\BaseModule;

class ModuleDiscovery
{
    protected Application $app;
    protected array $scannedPaths = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Scan all configured paths for modules
     */
    public function scan(): array
    {
        $modules = [];
        $paths = config('ironflow.paths', [base_path('modules')]);

        foreach ($paths as $path) {
            if (!File::isDirectory($path)) {
                continue;
            }

            $discovered = $this->scanPath($path);
            $modules = array_merge($modules, $discovered);
        }

        return $modules;
    }

    /**
     * Scan a specific path for modules
     */
    protected function scanPath(string $path): array
    {
        $modules = [];
        $directories = File::directories($path);

        foreach ($directories as $directory) {
            $moduleName = basename($directory);
            $moduleClass = $this->findModuleClass($directory, $moduleName);

            if ($moduleClass && $this->isValidModule($moduleClass)) {
                $modules[$moduleName] = $moduleClass;
                $this->scannedPaths[$moduleName] = $directory;
            }
        }

        return $modules;
    }

    /**
     * Find the module class in a directory
     */
    protected function findModuleClass(string $directory, string $moduleName): ?string
    {
        // Convention: ModuleNameModule.php
        $expectedFile = $directory . '/' . $moduleName . 'Module.php';

        if (!File::exists($expectedFile)) {
            return null;
        }

        // Try to determine namespace from composer.json
        $namespace = $this->getNamespaceFromComposer($directory);

        if (!$namespace) {
            // Fallback to convention
            $namespace = 'Modules\\' . $moduleName;
        }

        return $namespace . '\\' . $moduleName . 'Module';
    }

    /**
     * Get namespace from module's composer.json
     */
    protected function getNamespaceFromComposer(string $directory): ?string
    {
        $composerFile = $directory . '/composer.json';

        if (!File::exists($composerFile)) {
            return null;
        }

        $composer = json_decode(File::get($composerFile), true);

        if (isset($composer['autoload']['psr-4'])) {
            $psr4 = $composer['autoload']['psr-4'];
            return key($psr4) ? rtrim(key($psr4), '\\') : null;
        }

        return null;
    }

    /**
     * Check if a class is a valid module
     */
    protected function isValidModule(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        try {
            $reflection = new \ReflectionClass($class);
            return $reflection->isSubclassOf(BaseModule::class) && !$reflection->isAbstract();
        } catch (\ReflectionException $e) {
            return false;
        }
    }

    /**
     * Get scanned paths
     */
    public function getScannedPaths(): array
    {
        return $this->scannedPaths;
    }
}
