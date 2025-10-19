<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permissions table
        Schema::create('ironflow_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('module');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('module');
        });

        // Roles table
        Schema::create('ironflow_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('module')->nullable();
            $table->timestamps();

            $table->index('module');
        });

        // Permission-Role pivot
        Schema::create('ironflow_permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained('ironflow_permissions')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('ironflow_roles')->cascadeOnDelete();

            $table->primary(['permission_id', 'role_id']);
        });

        // Permission-User pivot
        Schema::create('ironflow_permission_user', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained('ironflow_permissions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->primary(['permission_id', 'user_id']);
        });

        // Role-User pivot
        Schema::create('ironflow_role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('ironflow_roles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->primary(['role_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ironflow_role_user');
        Schema::dropIfExists('ironflow_permission_user');
        Schema::dropIfExists('ironflow_permission_role');
        Schema::dropIfExists('ironflow_roles');
        Schema::dropIfExists('ironflow_permissions');
    }
};
