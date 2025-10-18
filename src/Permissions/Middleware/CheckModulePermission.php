<?php

declare(strict_types=1);

namespace IronFlow\Permissions\Middleware;

use Closure;
use Illuminate\Http\Request;
use IronFlow\Permissions\ModulePermissionManager;

class CheckModulePermission
{
    protected ModulePermissionManager $permissionManager;

    public function __construct(ModulePermissionManager $permissionManager)
    {
        $this->permissionManager = $permissionManager;
    }

    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$this->permissionManager->userHasPermission($user, $permission)) {
            abort(403, "You don't have permission: {$permission}");
        }

        return $next($request);
    }
}
