<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Modules Path
    |--------------------------------------------------------------------------
    |
    | Define where IronFlow should look for modules.
    | Default: base_path('modules')
    |
    */
    'path' => env('IRONFLOW_MODULES_PATH', base_path('modules')),

    /*
    |--------------------------------------------------------------------------
    | Module Namespace
    |--------------------------------------------------------------------------
    |
    | Base namespace for all modules.
    |
    */
    'namespace' => 'Modules',

    /*
    |--------------------------------------------------------------------------
    | Auto Discovery
    |--------------------------------------------------------------------------
    |
    | Enable automatic discovery and registration of modules.
    |
    */
    'auto_discover' => env('IRONFLOW_AUTO_DISCOVER', true),

    /*
    |--------------------------------------------------------------------------
    | Module Priority
    |--------------------------------------------------------------------------
    |
    | Define boot priority order. Modules with higher priority boot first.
    | You can override module priorities here.
    |
    */
    'priorities' => [
        // 'Core' => 100,
        // 'Auth' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure module lifecycle logging.
    |
    */
    'logging' => [
        'enabled' => env('IRONFLOW_LOGGING', true),
        'channel' => env('IRONFLOW_LOG_CHANNEL', 'stack'),
        'level' => env('IRONFLOW_LOG_LEVEL', 'info'),
        'log_events' => [
            'registered' => true,
            'booted' => true,
            'failed' => true,
            'enabled' => true,
            'disabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable detailed debugging information for module operations.
    |
    */
    'debug' => env('IRONFLOW_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Cache module registry and dependencies for performance.
    |
    */
    'cache' => [
        'enabled' => env('IRONFLOW_CACHE', true),
        'key' => 'ironflow.modules',
        'ttl' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Lazy Loading
    |--------------------------------------------------------------------------
    |
    | Configure lazy loading behavior to improve performance.
    | Modules are only loaded when actually needed.
    |
    */
    'lazy_load' => [
        // Enable lazy loading
        'enabled' => env('IRONFLOW_LAZY_LOAD', true),

        // Modules that are always loaded (eager loading)
        'eager' => [
            'Core',
            'Auth',
            // Add modules that must always be loaded
        ],

        // Modules that can be lazy loaded
        // If empty, all non-eager modules are lazy loaded
        'lazy' => [
            // 'Blog',
            // 'Shop',
            // 'Forum',
        ],

        // Lazy loading strategies
        'strategies' => [
            'route' => true,      // Load on route match
            'service' => true,    // Load on service access
            'event' => true,      // Load on event trigger
            'command' => true,    // Load on artisan command
        ],

        // Preload conditions (smart preloading)
        'preload' => [
            // Preload modules based on route patterns
            'routes' => [
                '#^admin/#' => ['Admin', 'Settings'],
                '#^api/#' => ['Api'],
                '#^blog/#' => ['Blog', 'Comments'],
            ],

            // Preload modules based on time of day (24h format)
            'time' => [
                '08-12' => ['Analytics', 'Reports'], // Morning
                '18-23' => ['Blog', 'Forum'],        // Evening
            ],

            // Preload modules based on user role
            'roles' => [
                'admin' => ['Admin', 'Settings', 'Analytics'],
                'editor' => ['Blog', 'Media'],
                'user' => ['Blog', 'Comments'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Conflict Detection
    |--------------------------------------------------------------------------
    |
    | Enable detection and resolution of conflicts between modules.
    |
    */
    'conflict_detection' => [
        'enabled' => true,
        'routes' => true,
        'migrations' => true,
        'views' => true,
        'config' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Strategy
    |--------------------------------------------------------------------------
    |
    | Define how module migrations should be handled.
    | Options: 'prefix', 'suffix', 'namespace'
    |
    */
    'migration_strategy' => 'prefix',

    /*
    |--------------------------------------------------------------------------
    | Service Exposure
    |--------------------------------------------------------------------------
    |
    | Configure how modules expose services to each other.
    |
    */
    'service_exposure' => [
        'strict_mode' => true, // Only allow exposure to linked modules
        'allow_public' => true, // Allow public service exposure
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for exporting modules to Packagist.
    |
    */
    'export' => [
        'output_path' => storage_path('ironflow/exports'),
        'include_tests' => true,
        'include_docs' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stub Customization
    |--------------------------------------------------------------------------
    |
    | Path to custom stubs for module generation.
    |
    */
    'stubs' => [
        'path' => resource_path('stubs/ironflow'),
        'custom' => false,
    ],
];
