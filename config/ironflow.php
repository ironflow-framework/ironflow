<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto Discovery
    |--------------------------------------------------------------------------
    |
    | When enabled, IronFlow will automatically discover and register modules
    | from the modules path during application bootstrap.
    |
    */

    'auto_discover' => env('IRONFLOW_AUTO_DISCOVER', true),

    /*
    |--------------------------------------------------------------------------
    | Auto Boot
    |--------------------------------------------------------------------------
    |
    | When enabled, IronFlow will automatically boot all registered modules
    | during application bootstrap.
    |
    */

    'auto_boot' => env('IRONFLOW_AUTO_BOOT', true),

    /*
    |--------------------------------------------------------------------------
    | Modules Path
    |--------------------------------------------------------------------------
    |
    | The base directory where modules are stored. This path is relative to
    | the application's base path.
    |
    */

    'modules_path' => env('IRONFLOW_MODULES_PATH', app_path('Modules')),

    /*
    |--------------------------------------------------------------------------
    | Cache Modules
    |--------------------------------------------------------------------------
    |
    | When enabled, discovered modules will be cached for faster boot times.
    | Use `php artisan ironflow:cache:clear` to clear the cache.
    |
    */

    'cache_modules' => env('IRONFLOW_CACHE_MODULES', true),

    /*
    |--------------------------------------------------------------------------
    | Throw On Boot Failure
    |--------------------------------------------------------------------------
    |
    | When enabled, IronFlow will throw an exception if any module fails to
    | boot. Otherwise, the error will be logged and the application will
    | continue running.
    |
    */

    'throw_on_boot_failure' => env('IRONFLOW_THROW_ON_BOOT_FAILURE', false),

    /*
    |--------------------------------------------------------------------------
    | Module Namespace
    |--------------------------------------------------------------------------
    |
    | The base namespace for modules. This is used when generating new modules.
    |
    */

    'namespace' => 'App\\Modules',

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, IronFlow will enforce strict validation of module
    | dependencies and metadata.
    |
    */

    'strict_mode' => env('IRONFLOW_STRICT_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Priority Boot
    |--------------------------------------------------------------------------
    |
    | Enable priority-based boot ordering. Modules with higher priority
    | values will boot first (after dependency resolution).
    |
    */

    'priority_boot' => env('IRONFLOW_PRIORITY_BOOT', true),

    /*
    |--------------------------------------------------------------------------
    | Allow Override
    |--------------------------------------------------------------------------
    |
    | Allow modules to override global application services and configurations.
    | Disable this for stricter control over service resolution.
    |
    */

    'allow_override' => env('IRONFLOW_ALLOW_OVERRIDE', false),

    /*
    |--------------------------------------------------------------------------
    | Register Routes
    |--------------------------------------------------------------------------
    |
    | Automatically register module routes during boot. You can disable this
    | if you prefer manual route registration.
    |
    */

    'register_routes' => env('IRONFLOW_REGISTER_ROUTES', true),

    /*
    |--------------------------------------------------------------------------
    | Register Views
    |--------------------------------------------------------------------------
    |
    | Automatically register module views during boot.
    |
    */

    'register_views' => env('IRONFLOW_REGISTER_VIEWS', true),

    /*
    |--------------------------------------------------------------------------
    | Register Migrations
    |--------------------------------------------------------------------------
    |
    | Automatically register module migrations to be run with artisan migrate.
    |
    */

    'register_migrations' => env('IRONFLOW_REGISTER_MIGRATIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Disabled Modules
    |--------------------------------------------------------------------------
    |
    | Manually disable specific modules by name. These modules will not be
    | registered or booted, regardless of their metadata configuration.
    |
    */

    'disabled_modules' => [
        // 'ExampleModule',
    ],

];
