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
        Schema::create('creator_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('period_month', 7); // e.g., 2026-09
            $table->unsignedBigInteger('stars_received')->default(0);
            $table->decimal('stars_amount', 12, 2)->default(0.00);
            $table->decimal('subscription_amount', 12, 2)->default(0.00);
            $table->decimal('ad_rev_share', 12, 2)->default(0.00);
            $table->decimal('gross_total', 12, 2)->default(0.00);
            $table->decimal('platform_fee', 12, 2)->default(0.00);
            $table->decimal('net_payout', 12, 2)->default(0.00);
            $table->string('payout_status')->default('pending'); // pending, processing, paid, hold
            $table->timestamps();

            $table->unique(['user_id', 'period_month']);
            $table->index(['period_month', 'payout_status']);
        });

        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('objective')->default('reach'); // traffic, conversions, engagement, reach
            $table->decimal('total_budget', 12, 2);
            $table->decimal('daily_budget', 12, 2);
            $table->decimal('amount_spent', 12, 2)->default(0.00);
            $table->string('status')->default('active'); // active, paused, completed, draft
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->json('targeting_criteria')->nullable(); // age, gender, locations, interests
            $table->json('ad_creatives')->nullable(); // headline, media_url, call_to_action
            $table->unsignedBigInteger('impressions_count')->default(0);
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'start_date']);
        });

        Schema::create('security_risk_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_fingerprint')->index();
            $table->float('risk_score')->default(0.0); // 0.0 (safe) - 100.0 (high risk)
            $table->string('risk_level')->default('low'); // low, medium, high, critical
            $table->string('last_ip', 45);
            $table->string('country_code', 3)->default('BD');
            $table->boolean('is_suspicious')->default(false);
            $table->boolean('mfa_required')->default(false);
            $table->json('anomaly_flags')->nullable(); // geo_shift, fast_typing, unknown_device, credential_stuffing
            $table->timestamp('last_assessed_at');
            $table->timestamps();

            $table->unique(['user_id', 'device_fingerprint']);
            $table->index(['risk_level', 'is_suspicious']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_risk_profiles');
        Schema::dropIfExists('ad_campaigns');
        Schema::dropIfExists('creator_earnings');
    }
};
