<?php

declare(strict_types=1);

namespace IronFlow\Permissions;

use Closure;
use Illuminate\Http\Request;

/**
 * CheckModulePermission Middleware
 */
class CheckModulePermission
{
    public function __construct(protected ModulePermissionSystem $permissions) {}

    public function handle(Request $request, Closure $next, string $moduleName, string $permission): mixed
    {
        if (!$this->permissions->check($moduleName, $permission)) {
            abort(403, "Unauthorized to access {$moduleName}.{$permission}");
        }

        return $next($request);
    }
}
