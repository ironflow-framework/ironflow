<?php

use IronFlow\Permissions\ModulePermissionManager;
use IronFlow\Models\{Permission, Role};
use App\Models\User;

beforeEach(function () {
    $this->permissionManager = app(ModulePermissionManager::class);
});

test('module permissions can be registered', function () {
    $module = new class extends \IronFlow\Core\BaseModule implements \IronFlow\Contracts\PermissibleInterface {
        protected function defineMetadata(): \IronFlow\Core\ModuleMetaData
        {
            return new \IronFlow\Core\ModuleMetaData(
                name: 'Blog',
                version: '1.0.0',
            );
        }

        public function definePermissions(): array
        {
            return [
                'view-posts' => 'View posts',
                'create-posts' => 'Create posts',
            ];
        }

        public function getPermissionGroups(): array
        {
            return [];
        }

        public function defineRoles(): array
        {
            return [];
        }

        public function requiresAuthentication(): bool
        {
            return true;
        }

        public function getPermissionMiddleware(): array
        {
            return [];
        }
    };

    $this->permissionManager->registerModulePermissions($module);

    $permissions = $this->permissionManager->getAllPermissions();

    expect($permissions)->toHaveKey('blog.view-posts')
        ->and($permissions)->toHaveKey('blog.create-posts');
});

test('user with permission has access', function () {
    $user = User::factory()->create();

    Permission::create([
        'key' => 'blog.view-posts',
        'module' => 'blog',
        'description' => 'View posts',
    ]);

    $user->assignPermission('blog.view-posts');

    expect($this->permissionManager->userHasPermission($user, 'blog.view-posts'))->toBeTrue();
});

test('wildcard permissions work correctly', function () {
    $user = User::factory()->create();

    Permission::create([
        'key' => 'blog.*',
        'module' => 'blog',
        'description' => 'All blog permissions',
    ]);

    $user->assignPermission('blog.*');

    expect($this->permissionManager->userHasPermission($user, 'blog.view-posts'))->toBeTrue()
        ->and($this->permissionManager->userHasPermission($user, 'blog.create-posts'))->toBeTrue()
        ->and($this->permissionManager->userHasPermission($user, 'blog.edit-posts'))->toBeTrue();
});

test('super admin bypasses all permissions', function () {
    $user = User::factory()->create();

    $superAdminRole = Role::create([
        'name' => 'super-admin',
        'display_name' => 'Super Administrator',
    ]);

    $user->assignRole($superAdminRole);

    expect($this->permissionManager->userHasPermission($user, 'any.permission'))->toBeTrue();
});
