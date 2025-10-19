<?php

use IronFlow\Models\Permission;
use IronFlow\Permissions\ModulePermissionManager;
use App\Models\User;

test('permission wildcards match correctly', function () {
    $manager = app(ModulePermissionManager::class);

    $user = User::factory()->create();
    Permission::create(['key' => 'blog.*', 'module' => 'blog']);
    $user->assignPermission('blog.*');

    expect($manager->userHasPermission($user, 'blog.view-posts'))->toBeTrue()
        ->and($manager->userHasPermission($user, 'blog.create-posts'))->toBeTrue()
        ->and($manager->userHasPermission($user, 'blog.anything'))->toBeTrue()
        ->and($manager->userHasPermission($user, 'other.permission'))->toBeFalse();
});

test('nested wildcard permissions work', function () {
    $manager = app(ModulePermissionManager::class);

    $user = User::factory()->create();
    Permission::create(['key' => 'blog.posts.*', 'module' => 'blog']);
    $user->assignPermission('blog.posts.*');

    expect($manager->userHasPermission($user, 'blog.posts.view'))->toBeTrue()
        ->and($manager->userHasPermission($user, 'blog.posts.create'))->toBeTrue()
        ->and($manager->userHasPermission($user, 'blog.comments.view'))->toBeFalse();
});
