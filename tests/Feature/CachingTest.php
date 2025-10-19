<?php

use IronFlow\Facades\Anvil;

test('module manifest can be cached', function () {
    $module = createTestModule('CacheTest');

    Anvil::registerModule('CacheTest', get_class($module));
    Anvil::cacheManifest();

    $cache = app(\IronFlow\Core\Discovery\ManifestCache::class);

    expect($cache->exists())->toBeTrue();

    $manifest = $cache->load();
    expect($manifest)->toHaveKey('CacheTest');
});

test('cached manifest can be loaded', function () {
    $module = createTestModule('CachedModule');

    Anvil::registerModule('CachedModule', get_class($module));
    Anvil::cacheManifest();

    // Clear modules
    Anvil::clearCache();

    // Should load from cache on next discover
    config(['ironflow.cache.enabled' => true]);
    Anvil::discover();

    expect(Anvil::hasModule('CachedModule'))->toBeTrue();
});
