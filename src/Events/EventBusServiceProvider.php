<?php

declare(strict_types=1);

namespace IronFlow\Events;

use Illuminate\Support\ServiceProvider;

/**
 * EventBusServiceProvider
 */

class EventBusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('ironflow.eventbus', function () {
            return new ModuleEventBus();
        });
    }

    public function boot(): void
    {
        // Enable debug in local environment
        if (config('app.debug') && config('ironflow.event_bus.debug', false)) {
            ModuleEventBus::enableDebug();
        }
    }
}