<?php

namespace IronFlow\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use IronFlow\Support\LazyLoader;
use Illuminate\Support\Facades\Log;

/**
 * LazyLoadModules Middleware
 *
 * Automatically loads modules based on the current route.
 */
class LazyLoadModules
{
    /**
     * @var LazyLoader
     */
    protected LazyLoader $lazyLoader;

    /**
     * Create a new middleware instance.
     *
     * @param LazyLoader $lazyLoader
     */
    public function __construct(LazyLoader $lazyLoader)
    {
        $this->lazyLoader = $lazyLoader;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $startTime = microtime(true);

        // Load module by route
        $route = $request->path();
        $module = $this->lazyLoader->loadByRoute($route);

        if ($module) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::debug("[IronFlow Middleware] Lazy loaded module for route", [
                'route' => $route,
                'module' => $module->getMetadata()->getName(),
                'duration' => "{$duration}ms",
            ]);
        }

        // Preload modules based on conditions
        $conditions = [
            'route' => $route,
            'role' => $request->user()?->role ?? 'guest',
        ];

        $this->lazyLoader->preload($conditions);

        return $next($request);
    }
}
