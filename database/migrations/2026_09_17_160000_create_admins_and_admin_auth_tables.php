<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Separate Admins Table for Enterprise Admin Guard
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('password');
            $table->enum('role', ['super_admin', 'admin', 'manager', 'moderator'])->default('admin')->index();
            $table->enum('status', ['active', 'suspended', 'banned'])->default('active')->index();
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Admin Sessions Table
        Schema::create('admin_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_name')->default('Admin Workstation');
            $table->longText('payload')->nullable();
            $table->integer('last_activity')->index();
            $table->timestamps();
        });

        // 3. Admin Login Histories Table
        Schema::create('admin_login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->cascadeOnDelete();
            $table->string('identifier')->nullable();
            $table->string('ip', 45);
            $table->string('browser')->nullable();
            $table->string('device')->nullable();
            $table->string('platform')->nullable();
            $table->timestamp('login_at');
            $table->timestamp('logout_at')->nullable();
            $table->boolean('success')->default(false)->index();
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });

        // 4. Admin Permissions Table
        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['admin_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_permissions');
        Schema::dropIfExists('admin_login_histories');
        Schema::dropIfExists('admin_sessions');
        Schema::dropIfExists('admins');
    }
};
