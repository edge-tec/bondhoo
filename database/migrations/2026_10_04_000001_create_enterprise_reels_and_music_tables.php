<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enterprise Reels, Stories, Music, Comments, Shares, and Saves architecture.
     */
    public function up(): void
    {
        // 1. Music Tracks Table (Licensed / Platform Library)
        if (! Schema::hasTable('music_tracks')) {
            Schema::create('music_tracks', function (Blueprint $table) {
                $table->id();
                $table->string('title', 150)->index();
                $table->string('artist', 120)->index();
                $table->string('album', 120)->nullable();
                $table->string('audio_url', 500);
                $table->string('cover_url', 500)->nullable();
                $table->decimal('duration', 8, 2)->default(30.0); // Duration in seconds
                $table->string('genre', 50)->default('pop')->index();
                $table->string('language', 30)->default('bn')->index();
                $table->json('tags')->nullable();
                $table->unsignedSmallInteger('bpm')->nullable();
                $table->string('license_type', 50)->default('royalty_free'); // royalty_free, cc_by, platform_licensed
                $table->string('license_holder', 150)->nullable();
                $table->boolean('is_admin_approved')->default(true)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('usages_count')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'genre', 'is_admin_approved']);
            });
        }

        // 2. Music Usages Table (Reels & Stories tracking)
        if (! Schema::hasTable('music_usages')) {
            Schema::create('music_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('music_track_id')->constrained('music_tracks')->cascadeOnDelete();
                $table->string('usable_type', 60); // App\Models\Reel or App\Models\Story
                $table->unsignedBigInteger('usable_id');
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('start_time_seconds', 8, 2)->default(0.0);
                $table->decimal('duration_seconds', 8, 2)->default(15.0);
                $table->unsignedTinyInteger('volume_percent')->default(100);
                $table->timestamps();

                $table->index(['usable_type', 'usable_id']);
                $table->index(['music_track_id', 'created_at']);
            });
        }

        // 3. Reel Comments Table
        if (! Schema::hasTable('reel_comments')) {
            Schema::create('reel_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reel_id')->constrained('reels')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('reel_comments')->cascadeOnDelete();
                $table->text('comment');
                $table->unsignedInteger('likes_count')->default(0);
                $table->unsignedInteger('replies_count')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['reel_id', 'parent_id', 'created_at']);
            });
        }

        // 4. Reel Comment Likes Table
        if (! Schema::hasTable('reel_comment_likes')) {
            Schema::create('reel_comment_likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reel_comment_id')->constrained('reel_comments')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['reel_comment_id', 'user_id']);
            });
        }

        // 5. Reel Shares Table
        if (! Schema::hasTable('reel_shares')) {
            Schema::create('reel_shares', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reel_id')->constrained('reels')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('platform', 40)->default('internal'); // internal, whatsapp, facebook, twitter, copy_link
                $table->timestamp('created_at')->useCurrent();

                $table->index(['reel_id', 'created_at']);
            });
        }

        // 6. Reel Saves / Bookmarks Table
        if (! Schema::hasTable('reel_saves')) {
            Schema::create('reel_saves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reel_id')->constrained('reels')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['reel_id', 'user_id']);
                $table->index(['user_id', 'created_at']);
            });
        }

        // 7. Add Enterprise Editor & Music Columns to Reels Table
        Schema::table('reels', function (Blueprint $table) {
            if (! Schema::hasColumn('reels', 'cover_image_path')) {
                $table->string('cover_image_path')->nullable()->after('audio_artist');
            }
            if (! Schema::hasColumn('reels', 'music_track_id')) {
                $table->foreignId('music_track_id')->nullable()->after('cover_image_path')->constrained('music_tracks')->nullOnDelete();
            }
            if (! Schema::hasColumn('reels', 'audio_volume')) {
                $table->unsignedTinyInteger('audio_volume')->default(100)->after('music_track_id');
            }
            if (! Schema::hasColumn('reels', 'music_volume')) {
                $table->unsignedTinyInteger('music_volume')->default(100)->after('audio_volume');
            }
            if (! Schema::hasColumn('reels', 'music_start_offset')) {
                $table->decimal('music_start_offset', 8, 2)->default(0.0)->after('music_volume');
            }
            if (! Schema::hasColumn('reels', 'trim_start')) {
                $table->decimal('trim_start', 8, 2)->default(0.0)->after('music_start_offset');
            }
            if (! Schema::hasColumn('reels', 'trim_end')) {
                $table->decimal('trim_end', 8, 2)->nullable()->after('trim_start');
            }
            if (! Schema::hasColumn('reels', 'rotation_deg')) {
                $table->smallInteger('rotation_deg')->default(0)->after('trim_end');
            }
            if (! Schema::hasColumn('reels', 'crop_aspect')) {
                $table->string('crop_aspect', 10)->default('9:16')->after('rotation_deg');
            }
            if (! Schema::hasColumn('reels', 'saves_count')) {
                $table->unsignedInteger('saves_count')->default(0)->after('shares_count');
            }
            if (! Schema::hasColumn('reels', 'is_draft')) {
                $table->boolean('is_draft')->default(false)->index()->after('status');
            }
        });

        // 8. Add Enterprise Music & Interactive Columns to Stories Table
        Schema::table('stories', function (Blueprint $table) {
            if (! Schema::hasColumn('stories', 'music_track_id')) {
                $table->foreignId('music_track_id')->nullable()->after('music_title')->constrained('music_tracks')->nullOnDelete();
            }
            if (! Schema::hasColumn('stories', 'music_start_offset')) {
                $table->decimal('music_start_offset', 8, 2)->default(0.0)->after('music_track_id');
            }
            if (! Schema::hasColumn('stories', 'music_volume')) {
                $table->unsignedTinyInteger('music_volume')->default(100)->after('music_start_offset');
            }
            if (! Schema::hasColumn('stories', 'interactive_sticker')) {
                $table->json('interactive_sticker')->nullable()->after('emoji');
            }
            if (! Schema::hasColumn('stories', 'is_draft')) {
                $table->boolean('is_draft')->default(false)->index()->after('is_archived');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $columns = ['music_track_id', 'music_start_offset', 'music_volume', 'interactive_sticker', 'is_draft'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('stories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('reels', function (Blueprint $table) {
            $columns = [
                'cover_image_path', 'music_track_id', 'audio_volume', 'music_volume',
                'music_start_offset', 'trim_start', 'trim_end', 'rotation_deg',
                'crop_aspect', 'saves_count', 'is_draft',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('reels', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('reel_saves');
        Schema::dropIfExists('reel_shares');
        Schema::dropIfExists('reel_comment_likes');
        Schema::dropIfExists('reel_comments');
        Schema::dropIfExists('music_usages');
        Schema::dropIfExists('music_tracks');
    }
};
