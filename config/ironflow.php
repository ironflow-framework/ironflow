<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Modules Path
    |--------------------------------------------------------------------------
    |
    | Define where your modules are located. Multiple paths are supported.
    |
    */
    'paths' => [
        base_path('modules'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lazy Loading Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which components should be loaded eagerly vs lazily.
    |
    */
    'lazy_loading' => [
        'enabled' => true,
        'eager' => ['routes', 'views', 'config'],
        'lazy' => ['services', 'events', 'commands', 'middleware'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Manifest Cache
    |--------------------------------------------------------------------------
    |
    | Cache module discovery for better performance in production.
    |
    */
    'cache' => [
        'enabled' => env('IRONFLOW_CACHE_ENABLED', env('APP_ENV') === 'production'),
        'key' => 'ironflow.manifest',
        'ttl' => 3600, // 1 hour
        'path' => storage_path('framework/cache/ironflow-manifest.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Conflict Detection
    |--------------------------------------------------------------------------
    |
    | Define how conflicts between modules should be handled.
    | Options: 'exception', 'warning', 'override', 'ignore'
    |
    */
    'conflicts' => [
        'routes' => 'warning',
        'views' => 'exception',
        'config' => 'override',
        'services' => 'exception',
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Events
    |--------------------------------------------------------------------------
    |
    | Enable/disable module event system and configure priorities.
    |
    */
    'events' => [
        'enabled' => true,
        'history' => [
            'enabled' => env('IRONFLOW_EVENT_HISTORY', false),
            'max_items' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Discovery
    |--------------------------------------------------------------------------
    |
    | Automatically discover modules on boot.
    |
    */
    'auto_discover' => env('IRONFLOW_AUTO_DISCOVER', true),

    /*
    |--------------------------------------------------------------------------
    | Exception Handling
    |--------------------------------------------------------------------------
    |
    | Configure how module exceptions should be handled.
    |
    */
    'exceptions' => [
        'rollback_on_boot_failure' => true,
        'log_exceptions' => true,
        'throw_on_missing_dependency' => true,
    ],
];
