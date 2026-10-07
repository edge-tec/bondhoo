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
        // 1. Email Verifications
        Schema::create('email_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('otp_hash', 64)->index();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        // 2. Mobile Verifications
        Schema::create('mobile_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 30)->index();
            $table->string('otp_hash', 64)->index();
            $table->string('gateway', 30)->default('log'); // ssl_wireless, bulksmsbd, twilio, infobip, log
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        // 3. Central OTP Codes Ledger
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('identifier', 100)->index(); // email or phone
            $table->string('purpose', 50)->index(); // registration, login_2fa, password_reset, verify_phone, verify_email
            $table->string('code_hash', 64)->index();
            $table->timestamp('expires_at');
            $table->timestamp('resend_available_at')->nullable();
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->unsignedSmallInteger('max_retries')->default(3);
            $table->boolean('is_used')->default(false)->index();
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        // 4. Password Histories (prevent old password reuse)
        Schema::create('password_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('password_hash');
            $table->timestamp('created_at')->useCurrent();
        });

        // 5. Login Histories
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('device_type', 30)->nullable(); // mobile, tablet, desktop
            $table->string('country', 10)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('status', 30)->default('success'); // success, failed, blocked, suspicious
            $table->string('failure_reason')->nullable();
            $table->boolean('is_suspicious')->default(false)->index();
            $table->timestamps();
        });

        // 6. User Sessions & Active Devices
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('token_id')->nullable()->index();
            $table->string('device_name', 100)->nullable();
            $table->string('device_fingerprint', 64)->nullable()->index();
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('location', 100)->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamp('last_active_at')->useCurrent();
            $table->timestamps();
        });

        // 7. Trusted Devices
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_fingerprint', 64)->index();
            $table->string('device_name', 100)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('trusted_until')->nullable();
            $table->timestamp('last_used_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'device_fingerprint']);
        });

        // 8. Failed Logins Monitor
        Schema::create('failed_logins', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 150)->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('attempted_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_logins');
        Schema::dropIfExists('trusted_devices');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('login_histories');
        Schema::dropIfExists('password_histories');
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('mobile_verifications');
        Schema::dropIfExists('email_verifications');
    }
};
