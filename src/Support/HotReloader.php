<?php

declare(strict_types=1);

namespace IronFlow\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use IronFlow\Core\Anvil;
use IronFlow\Core\BaseModule;

/**
 * HotReloader
 *
 * Hot reload modules during development without restarting server.
 */
class HotReloader
{
    protected Anvil $anvil;
    protected array $watchedFiles = [];
    protected array $fileHashes = [];
    protected bool $enabled = false;

    public function __construct(Anvil $anvil)
    {
        $this->anvil = $anvil;
        $this->enabled = config('ironflow.hot_reload.enabled', false);
    }

    /**
     * Start watching modules for changes.
     *
     * @return void
     */
    public function watch(): void
    {
        if (!$this->enabled) {
            return;
        }

        $modules = $this->anvil->getModules();

        foreach ($modules as $name => $module) {
            $this->watchModule($name, $module);
        }

        Log::info("[IronFlow HotReload] Watching {$modules->count()} modules");
    }

    /**
     * Watch a specific module for changes.
     *
     * @param string $name
     * @param BaseModule $module
     * @return void
     */
    protected function watchModule(string $name, BaseModule $module): void
    {
        $modulePath = $this->getModulePath($module);
        $watchPaths = config('ironflow.hot_reload.watch_paths', [
            'ModuleClass.php',
            'Routes/*.php',
            'config/*.php',
            'Http/Controllers/*.php',
            'Services/*.php',
        ]);

        foreach ($watchPaths as $pattern) {
            $files = $this->findFiles($modulePath, $pattern);

            foreach ($files as $file) {
                $this->watchedFiles[$name][] = $file;
                $this->fileHashes[$file] = $this->getFileHash($file);
            }
        }
    }

    /**
     * Check for changes and reload if necessary.
     *
     * @return array Changed modules
     */
    public function checkAndReload(): array
    {
        if (!$this->enabled) {
            return [];
        }

        $changed = [];

        foreach ($this->watchedFiles as $moduleName => $files) {
            foreach ($files as $file) {
                if (!File::exists($file)) {
                    continue;
                }

                $currentHash = $this->getFileHash($file);
                $previousHash = $this->fileHashes[$file] ?? null;

                if ($currentHash !== $previousHash) {
                    $changed[$moduleName] = $file;
                    $this->fileHashes[$file] = $currentHash;
                    break; // One change per module is enough
                }
            }
        }

        foreach (array_keys($changed) as $moduleName) {
            $this->reloadModule($moduleName, $changed[$moduleName]);
        }

        return $changed;
    }

    /**
     * Reload a specific module.
     *
     * @param string $moduleName
     * @param string $changedFile
     * @return void
     */
    public function reloadModule(string $moduleName, string $changedFile): void
    {
        try {
            Log::info("[IronFlow HotReload] Reloading module: {$moduleName}", [
                'changed_file' => basename($changedFile),
            ]);

            $module = $this->anvil->getModule($moduleName);

            if (!$module) {
                return;
            }

            // Disable module
            $module->disable();

            // Clear opcache for the changed files
            $this->clearOpcache($moduleName);

            // Clear module cache
            $this->anvil->clearCache();

            // Re-register and boot
            $this->anvil->registerModule(get_class($module));

            $reloadedModule = $this->anvil->getModule($moduleName);
            $reloadedModule->enable();
            $reloadedModule->boot(app());

            Log::info("[IronFlow HotReload] Module reloaded successfully: {$moduleName}");

            // Dispatch event
            event('ironflow.module.hot_reloaded', [
                'module' => $moduleName,
                'file' => basename($changedFile),
            ]);
        } catch (\Throwable $e) {
            Log::error("[IronFlow HotReload] Failed to reload module: {$moduleName}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear opcache for module files.
     *
     * @param string $moduleName
     * @return void
     */
    protected function clearOpcache(string $moduleName): void
    {
        if (!function_exists('opcache_invalidate')) {
            return;
        }

        $files = $this->watchedFiles[$moduleName] ?? [];

        foreach ($files as $file) {
            if (File::exists($file)) {
                opcache_invalidate($file, true);
            }
        }
    }

    /**
     * Get file hash for change detection.
     *
     * @param string $file
     * @return string
     */
    protected function getFileHash(string $file): string
    {
        return md5_file($file);
    }

    /**
     * Find files matching pattern in path.
     *
     * @param string $basePath
     * @param string $pattern
     * @return array
     */
    protected function findFiles(string $basePath, string $pattern): array
    {
        $fullPattern = $basePath . '/' . $pattern;
        return glob($fullPattern) ?: [];
    }

    /**
     * Get module path.
     *
     * @param BaseModule $module
     * @return string
     */
    protected function getModulePath(BaseModule $module): string
    {
        $reflection = new \ReflectionClass($module);
        return dirname($reflection->getFileName());
    }

    /**
     * Enable hot reload.
     *
     * @return void
     */
    public function enable(): void
    {
        $this->enabled = true;
        $this->watch();
    }

    /**
     * Disable hot reload.
     *
     * @return void
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if hot reload is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get watched files.
     *
     * @return array
     */
    public function getWatchedFiles(): array
    {
        return $this->watchedFiles;
    }

    /**
     * Get statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $totalFiles = 0;
        foreach ($this->watchedFiles as $files) {
            $totalFiles += count($files);
        }

        return [
            'enabled' => $this->enabled,
            'watched_modules' => count($this->watchedFiles),
            'watched_files' => $totalFiles,
            'modules' => array_keys($this->watchedFiles),
        ];
    }
}
