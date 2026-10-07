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
        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            $table->uuid('channel_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('stream_key')->unique();
            $table->string('ingest_url');
            $table->string('playback_url');
            $table->string('status')->default('ready'); // ready, live, ended, error
            $table->unsignedInteger('viewers_count')->default(0);
            $table->unsignedInteger('peak_viewers')->default(0);
            $table->unsignedInteger('total_reactions')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('health_stats')->nullable(); // bitrate, fps, dropped_frames
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'viewers_count']);
        });

        Schema::create('live_stream_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index(['live_stream_id', 'created_at']);
        });

        Schema::create('live_stream_gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('gift_type'); // rose, diamond, crown, rocket
            $table->unsignedInteger('coin_amount')->default(1);
            $table->timestamps();

            $table->index(['live_stream_id', 'coin_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_stream_gifts');
        Schema::dropIfExists('live_stream_comments');
        Schema::dropIfExists('live_streams');
    }
};
