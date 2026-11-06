<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Connectly Parental Control Core Schema.
     */
    public function up(): void
    {
        // 1. Families Table
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('timezone', 64)->default('UTC');
            $table->enum('status', ['active', 'suspended', 'closed'])->default('active');
            $table->timestamps();

            $table->index('owner_user_id');
            $table->index('status');
        });

        // 2. Family Members Table
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 100);
            $table->enum('role', ['parent', 'guardian', 'child'])->default('child');
            $table->enum('membership_status', ['active', 'invited', 'suspended', 'left'])->default('active');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['family_id', 'user_id']);
            $table->index(['family_id', 'role']);
        });

        // 3. Child Devices Table
        Schema::create('child_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('child_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->uuid('device_uuid')->unique();
            $table->string('device_name', 120);
            $table->string('platform', 32)->default('android');
            $table->string('android_version', 32)->nullable();
            $table->string('app_version', 32)->nullable();
            $table->string('device_token_hash', 128)->nullable()->index();
            $table->enum('device_status', ['enrolled', 'active', 'inactive', 'revoked'])->default('enrolled');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['family_id', 'device_status']);
            $table->index('child_member_id');
        });

        // 4. Device Pairing Codes Table
        Schema::create('device_pairing_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('child_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->string('code_hash', 64)->index();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->unsignedTinyInteger('failed_attempts')->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['family_id', 'expires_at']);
        });

        // 5. Screen Time Records Table
        Schema::create('screen_time_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_device_id')->constrained('child_devices')->cascadeOnDelete();
            $table->foreignId('child_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->string('application_package', 191);
            $table->date('usage_date');
            $table->unsignedInteger('foreground_duration_seconds')->default(0);
            $table->timestamp('collection_started_at')->nullable();
            $table->timestamp('collection_ended_at')->nullable();
            $table->string('source', 32)->default('usage_stats');
            $table->timestamps();

            $table->unique(['child_device_id', 'application_package', 'usage_date'], 'unique_device_pkg_date');
            $table->index(['child_member_id', 'usage_date']);
        });

        // 6. App Usage Logs Table
        Schema::create('app_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_device_id')->constrained('child_devices')->cascadeOnDelete();
            $table->string('application_package', 191);
            $table->string('usage_type', 32)->default('foreground');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamp('event_started_at');
            $table->timestamp('event_ended_at')->nullable();
            $table->timestamp('collected_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['child_device_id', 'event_started_at']);
        });

        // 7. Device Restrictions Table
        Schema::create('device_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('child_device_id')->nullable()->constrained('child_devices')->cascadeOnDelete();
            $table->foreignId('child_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->string('restriction_type', 64);
            $table->string('application_package', 191)->nullable();
            $table->json('configuration_json');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('policy_version')->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['family_id', 'child_member_id']);
            $table->index(['restriction_type', 'enabled']);
        });

        // 8. Remote Commands Table
        Schema::create('remote_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('child_device_id')->constrained('child_devices')->cascadeOnDelete();
            $table->uuid('command_uuid')->unique();
            $table->string('command_type', 64);
            $table->json('payload')->nullable();
            $table->enum('command_status', [
                'queued', 'sent', 'acknowledged', 'completed', 'expired', 'rejected', 'failed',
            ])->default('queued');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['child_device_id', 'command_status']);
            $table->index(['family_id', 'issued_at']);
        });

        // 9. Consent Requests Table
        Schema::create('consent_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('child_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->foreignId('child_device_id')->constrained('child_devices')->cascadeOnDelete();
            $table->string('request_type', 64);
            $table->text('request_reason')->nullable();
            $table->string('requested_value', 128)->nullable();
            $table->enum('request_status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->text('parent_response')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['family_id', 'request_status']);
            $table->index(['child_member_id', 'expires_at']);
        });

        // 10. Parental Media Sessions Table
        Schema::create('parental_media_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('child_device_id')->constrained('child_devices')->cascadeOnDelete();
            $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
            $table->string('session_type', 32)->default('supervisory_audio');
            $table->string('livekit_room_id', 128)->unique();
            $table->enum('session_status', ['pending_consent', 'active', 'completed', 'terminated', 'rejected'])->default('pending_consent');
            $table->timestamp('child_consent_at')->nullable();
            $table->timestamp('parent_consent_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('termination_reason', 128)->nullable();
            $table->timestamps();

            $table->index(['family_id', 'session_status']);
        });

        // 11. Child Security Alerts Table
        Schema::create('child_security_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('child_device_id')->constrained('child_devices')->cascadeOnDelete();
            $table->string('alert_type', 64);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('description');
            $table->enum('alert_status', ['active', 'acknowledged', 'resolved', 'dismissed'])->default('active');
            $table->timestamp('detected_at')->useCurrent();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['family_id', 'alert_status']);
            $table->index(['child_device_id', 'severity']);
        });

        // 12. Family Audit Logs Table
        Schema::create('family_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 32)->default('parent');
            $table->string('action', 64);
            $table->string('resource_type', 64);
            $table->string('resource_id', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['family_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_audit_logs');
        Schema::dropIfExists('child_security_alerts');
        Schema::dropIfExists('parental_media_sessions');
        Schema::dropIfExists('consent_requests');
        Schema::dropIfExists('remote_commands');
        Schema::dropIfExists('device_restrictions');
        Schema::dropIfExists('app_usage_logs');
        Schema::dropIfExists('screen_time_records');
        Schema::dropIfExists('device_pairing_codes');
        Schema::dropIfExists('child_devices');
        Schema::dropIfExists('family_members');
        Schema::dropIfExists('families');
    }
};
