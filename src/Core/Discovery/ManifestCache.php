<?php

declare(strict_types=1);

namespace IronFlow\Core\Discovery;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;

class ManifestCache
{
    protected Application $app;
    protected string $cachePath;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->cachePath = config('ironflow.cache.path', storage_path('framework/cache/ironflow-manifest.json'));
    }

    /**
     * Check if cache exists and is valid
     */
    public function exists(): bool
    {
        if (!File::exists($this->cachePath)) {
            return false;
        }

        $ttl = config('ironflow.cache.ttl', 3600);
        $lastModified = File::lastModified($this->cachePath);

        return (time() - $lastModified) < $ttl;
    }

    /**
     * Load manifest from cache
     */
    public function load(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $content = File::get($this->cachePath);
        return json_decode($content, true) ?? [];
    }

    /**
     * Save manifest to cache
     */
    public function save(array $manifest): void
    {
        $directory = dirname($this->cachePath);

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($this->cachePath, json_encode($manifest, JSON_PRETTY_PRINT));
    }

    /**
     * Clear cache
     */
    public function clear(): void
    {
        if (File::exists($this->cachePath)) {
            File::delete($this->cachePath);
        }
    }

    /**
     * Get cache path
     */
    public function getPath(): string
    {
        return $this->cachePath;
    }
}
