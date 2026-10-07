<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ১. ব্যর্থ লগইন প্রচেষ্টা ট্র্যাকিং টেবিল (Failed Login Attempts)
        Schema::create('failed_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('identifier')->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('attempted_at')->useCurrent()->index();
            $table->timestamps();
        });

        // ২. সাময়িক বা স্থায়ীভাবে ব্লকড ইউজার/আইপি টেবিল (Blocked Users / Security Lockouts)
        Schema::create('blocked_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('identifier')->nullable()->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('reason');
            $table->string('blocked_type', 50)->default('account_lock')->index(); // account_lock, brute_force, ip_ban, admin_block
            $table->timestamp('blocked_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_users');
        Schema::dropIfExists('failed_login_attempts');
    }
};
