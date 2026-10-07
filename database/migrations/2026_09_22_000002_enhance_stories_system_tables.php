<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enhance stories table with enterprise Facebook-style features:
     * Multi-media attachment, reactions, replies, location, status, and clean expiration tracking.
     */
    public function up(): void
    {
        // 1. Add missing enterprise columns to stories
        Schema::table('stories', function (Blueprint $table) {
            if (! Schema::hasColumn('stories', 'location')) {
                $table->string('location')->nullable()->after('background_color');
            }
            if (! Schema::hasColumn('stories', 'status')) {
                $table->string('status', 20)->default('ready')->index()->after('type'); // ready, processing, failed
            }
            if (! Schema::hasColumn('stories', 'is_expired')) {
                $table->boolean('is_expired')->default(false)->index()->after('expires_at');
            }
            if (! Schema::hasColumn('stories', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->index()->after('is_expired');
            }
            if (! Schema::hasColumn('stories', 'allow_replies')) {
                $table->boolean('allow_replies')->default(true)->after('privacy');
            }
            if (! Schema::hasColumn('stories', 'reactions_count')) {
                $table->unsignedInteger('reactions_count')->default(0)->after('views_count');
            }
            if (! Schema::hasColumn('stories', 'replies_count')) {
                $table->unsignedInteger('replies_count')->default(0)->after('reactions_count');
            }
            if (! Schema::hasColumn('stories', 'font_family')) {
                $table->string('font_family', 50)->nullable()->after('background_color');
            }
            if (! Schema::hasColumn('stories', 'music_title')) {
                $table->string('music_title')->nullable()->after('font_family');
            }
            if (! Schema::hasColumn('stories', 'emoji')) {
                $table->string('emoji', 20)->nullable()->after('music_title');
            }
        });

        // 2. Story Media Table (Multiple photos / videos in a single story sequence)
        Schema::create('story_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(0);
            $table->unsignedSmallInteger('duration')->default(5); // Playback seconds (e.g. 5s for photo, video duration for video)
            $table->timestamps();

            $table->index(['story_id', 'order']);
        });

        // 3. Story Reactions Table
        Schema::create('story_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20)->default('like'); // like, love, care, haha, wow, sad, angry
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['story_id', 'user_id']);
        });

        // 4. Story Replies Table
        Schema::create('story_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();

            $table->index(['story_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_replies');
        Schema::dropIfExists('story_reactions');
        Schema::dropIfExists('story_media');

        Schema::table('stories', function (Blueprint $table) {
            $columns = [
                'location', 'status', 'is_expired', 'is_archived',
                'allow_replies', 'reactions_count', 'replies_count',
                'font_family', 'music_title', 'emoji',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('stories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
