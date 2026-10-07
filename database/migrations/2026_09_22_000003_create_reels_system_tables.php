<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Dedicated Reels System:
     * Short-form vertical video (9:16), multi-quality rendition tracking,
     * views, watch duration, and engagement.
     */
    public function up(): void
    {
        // 1. Reels Table
        Schema::create('reels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('caption')->nullable();
            $table->string('audio_title')->nullable();
            $table->string('audio_artist')->nullable();
            $table->decimal('duration', 8, 2)->nullable(); // Seconds
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('aspect_ratio', 10)->default('9:16');
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->string('privacy', 20)->default('public')->index(); // public, friends, only_me
            $table->string('status', 20)->default('ready')->index(); // ready, processing, failed
            $table->string('location')->nullable();
            $table->boolean('allow_comments')->default(true);
            $table->boolean('allow_duet')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
        });

        // 2. Reel Media Table (Renditions & Quality Versions)
        Schema::create('reel_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reel_id')->constrained('reels')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('quality', 20)->default('original'); // original, 1080p, 720p, 480p, 360p
            $table->string('video_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('hls_path')->nullable();
            $table->string('mime_type', 100);
            $table->unsignedInteger('bitrate')->nullable();
            $table->unsignedBigInteger('size'); // Bytes
            $table->timestamps();

            $table->index(['reel_id', 'quality']);
        });

        // 3. Reel Views Table
        Schema::create('reel_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reel_id')->constrained('reels')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->decimal('watch_time_seconds', 8, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['reel_id', 'created_at']);
        });

        // 4. Reel Reactions Table
        Schema::create('reel_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reel_id')->constrained('reels')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20)->default('like'); // like, love, haha, wow, sad, angry
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['reel_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reel_reactions');
        Schema::dropIfExists('reel_views');
        Schema::dropIfExists('reel_media');
        Schema::dropIfExists('reels');
    }
};
