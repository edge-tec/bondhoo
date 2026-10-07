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
        // 1. Calls Table (Audio, Video, Group Calling)
        if (! Schema::hasTable('calls')) {
            Schema::create('calls', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
                $table->foreignId('caller_id')->constrained('users')->cascadeOnDelete();
                $table->string('call_type', 32); // audio, video, group_audio, group_video
                $table->string('status', 32)->default('initiating'); // initiating, ringing, active, ended, rejected, missed, busy, failed
                $table->string('room_id', 64)->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->unsignedInteger('duration_seconds')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['conversation_id', 'status']);
                $table->index(['caller_id', 'created_at']);
            });
        }

        // 2. Call Participants Table
        if (! Schema::hasTable('call_participants')) {
            Schema::create('call_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('call_id')->constrained('calls')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 32)->default('participant'); // caller, callee, participant
                $table->string('status', 32)->default('ringing'); // ringing, accepted, rejected, left, missed, busy
                $table->timestamp('joined_at')->nullable();
                $table->timestamp('left_at')->nullable();
                $table->unsignedInteger('duration_seconds')->default(0);
                $table->boolean('is_muted')->default(false);
                $table->boolean('is_camera_off')->default(false);
                $table->boolean('is_screen_sharing')->default(false);
                $table->json('device_info')->nullable();
                $table->timestamps();

                $table->unique(['call_id', 'user_id']);
                $table->index(['user_id', 'status']);
            });
        }

        // 3. Pinned Messages Table
        if (! Schema::hasTable('pinned_messages')) {
            Schema::create('pinned_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
                $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
                $table->foreignId('pinned_by_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('pinned_at')->useCurrent();

                $table->unique(['conversation_id', 'message_id']);
                $table->index(['conversation_id', 'pinned_at']);
            });
        }

        // 4. Saved Messages Table
        if (! Schema::hasTable('saved_messages')) {
            Schema::create('saved_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
                $table->timestamp('saved_at')->useCurrent();

                $table->unique(['user_id', 'message_id']);
                $table->index(['user_id', 'saved_at']);
            });
        }

        // 5. Device Sessions Table (Multi-Device Management)
        if (! Schema::hasTable('device_sessions')) {
            Schema::create('device_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('device_id', 128)->index();
                $table->string('device_name', 128)->nullable();
                $table->string('platform', 32)->default('web'); // web, android, ios, desktop, windows, macos, linux
                $table->string('app_version', 32)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('push_token')->nullable();
                $table->timestamp('last_active_at')->nullable();
                $table->boolean('is_revoked')->default(false);
                $table->timestamps();

                $table->index(['user_id', 'is_revoked']);
            });
        }

        // 6. Messenger Sync Events Table (Event Replay & Offline Sync)
        if (! Schema::hasTable('messenger_sync_events')) {
            Schema::create('messenger_sync_events', function (Blueprint $table) {
                $table->id(); // sequential integer sequence for synchronization
                $table->uuid('event_uuid')->unique();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('conversation_id')->nullable()->index();
                $table->string('event_type', 64)->index();
                $table->json('payload');
                $table->timestamp('created_at')->useCurrent()->index();

                $table->index(['user_id', 'id']);
                $table->index(['conversation_id', 'id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messenger_sync_events');
        Schema::dropIfExists('device_sessions');
        Schema::dropIfExists('saved_messages');
        Schema::dropIfExists('pinned_messages');
        Schema::dropIfExists('call_participants');
        Schema::dropIfExists('calls');
    }
};
