<?php

declare(strict_types=1);

namespace IronFlow\Exceptions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;

class ExceptionHandler
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle bootstrap exceptions
     */
    public function handleBootstrapException(\Throwable $e): void
    {
        if (config('ironflow.exceptions.log_exceptions', true)) {
            Log::error('IronFlow bootstrap failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    /**
     * Handle module-specific exceptions
     */
    public function handleModuleException(string $moduleName, \Throwable $e, string $phase): void
    {
        if (config('ironflow.exceptions.log_exceptions', true)) {
            Log::error("Module {$moduleName} failed during {$phase}", [
                'module' => $moduleName,
                'phase' => $phase,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Store in session for debugging (optional)
        if ($this->app->bound('session')) {
            $this->app['session']->flash('ironflow.error', [
                'module' => $moduleName,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
