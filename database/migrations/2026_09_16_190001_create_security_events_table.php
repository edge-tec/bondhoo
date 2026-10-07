<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * সিকিউরিটি অপারেশন্স সেন্টার (SOC) ইভেন্ট লগ টেবিল।
     */
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 100)->index(); // failed_login, impossible_travel, session_anomaly, malware_detected, waf_blocked, rate_limited
            $table->string('severity', 20)->default('warning')->index(); // info, warning, high, critical
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('country_code', 10)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('details')->nullable();
            $table->boolean('resolved')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
