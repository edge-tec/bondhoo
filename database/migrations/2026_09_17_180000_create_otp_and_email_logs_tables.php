<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. OTP Logs Table
        Schema::create('otp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('identifier', 190)->index();
            $table->string('otp_code', 100);
            $table->string('type', 50)->default('password_reset')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_used')->default(false)->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamps();
        });

        // 2. Email Logs Table
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient', 190)->index();
            $table->string('subject');
            $table->string('mail_class')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('status', 30)->default('sent')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('otp_logs');
    }
};
