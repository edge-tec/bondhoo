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
        // 1. Extend user_profiles table with enterprise v3 attributes
        Schema::table('user_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('user_profiles', 'middle_name')) {
                $table->string('middle_name', 100)->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('user_profiles', 'religion')) {
                $table->string('religion', 50)->nullable()->after('gender');
            }
            if (! Schema::hasColumn('user_profiles', 'blood_group')) {
                $table->string('blood_group', 10)->nullable()->after('religion');
            }
            if (! Schema::hasColumn('user_profiles', 'division')) {
                $table->string('division', 100)->nullable()->after('country');
            }
            if (! Schema::hasColumn('user_profiles', 'district')) {
                $table->string('district', 100)->nullable()->after('division');
            }
            if (! Schema::hasColumn('user_profiles', 'upazila')) {
                $table->string('upazila', 100)->nullable()->after('district');
            }
            if (! Schema::hasColumn('user_profiles', 'pronouns')) {
                $table->string('pronouns', 50)->nullable()->after('display_name');
            }
            if (! Schema::hasColumn('user_profiles', 'category')) {
                $table->string('category', 100)->nullable()->after('headline');
            }
            if (! Schema::hasColumn('user_profiles', 'portfolio')) {
                $table->string('portfolio', 255)->nullable()->after('website');
            }
            if (! Schema::hasColumn('user_profiles', 'whatsapp')) {
                $table->string('whatsapp', 50)->nullable()->after('portfolio');
            }
            if (! Schema::hasColumn('user_profiles', 'telegram')) {
                $table->string('telegram', 50)->nullable()->after('whatsapp');
            }
            if (! Schema::hasColumn('user_profiles', 'signal')) {
                $table->string('signal', 50)->nullable()->after('telegram');
            }
            if (! Schema::hasColumn('user_profiles', 'messenger')) {
                $table->string('messenger', 100)->nullable()->after('signal');
            }
            if (! Schema::hasColumn('user_profiles', 'hobbies')) {
                $table->json('hobbies')->nullable()->after('interests');
            }
            if (! Schema::hasColumn('user_profiles', 'favorite_music')) {
                $table->json('favorite_music')->nullable()->after('hobbies');
            }
            if (! Schema::hasColumn('user_profiles', 'favorite_books')) {
                $table->json('favorite_books')->nullable()->after('favorite_music');
            }
            if (! Schema::hasColumn('user_profiles', 'favorite_movies')) {
                $table->json('favorite_movies')->nullable()->after('favorite_books');
            }
            if (! Schema::hasColumn('user_profiles', 'is_professional_mode')) {
                $table->boolean('is_professional_mode')->default(false)->after('joined_date');
            }
        });

        // 2. Profile Photos History
        if (! Schema::hasTable('profile_photos')) {
            Schema::create('profile_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
                $table->string('photo_url', 255);
                $table->boolean('is_current')->default(false)->index();
                $table->string('caption', 255)->nullable();
                $table->string('frame', 100)->nullable();
                $table->string('privacy', 20)->default('public');
                $table->timestamps();

                $table->index(['user_id', 'is_current']);
            });
        }

        // 3. Cover Photos History
        if (! Schema::hasTable('cover_photos')) {
            Schema::create('cover_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
                $table->string('cover_url', 255);
                $table->boolean('is_current')->default(false)->index();
                $table->string('caption', 255)->nullable();
                $table->integer('position_y')->default(50);
                $table->string('privacy', 20)->default('public');
                $table->timestamps();

                $table->index(['user_id', 'is_current']);
            });
        }

        // 4. Extend friendships with advanced categories
        Schema::table('friendships', function (Blueprint $table) {
            if (! Schema::hasColumn('friendships', 'is_favorite')) {
                $table->boolean('is_favorite')->default(false)->after('status')->index();
            }
            if (! Schema::hasColumn('friendships', 'is_close_friend')) {
                $table->boolean('is_close_friend')->default(false)->after('is_favorite')->index();
            }
            if (! Schema::hasColumn('friendships', 'is_restricted')) {
                $table->boolean('is_restricted')->default(false)->after('is_close_friend')->index();
            }
            if (! Schema::hasColumn('friendships', 'is_muted')) {
                $table->boolean('is_muted')->default(false)->after('is_restricted');
            }
            if (! Schema::hasColumn('friendships', 'muted_until')) {
                $table->timestamp('muted_until')->nullable()->after('is_muted');
            }
            if (! Schema::hasColumn('friendships', 'snoozed_until')) {
                $table->timestamp('snoozed_until')->nullable()->after('muted_until');
            }
        });

        // 5. Photo Albums & Items
        if (! Schema::hasTable('photo_albums')) {
            Schema::create('photo_albums', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title', 191);
                $table->text('description')->nullable();
                $table->string('type', 50)->default('custom'); // profile_photos, cover_photos, timeline, custom
                $table->string('privacy', 20)->default('public');
                $table->timestamps();

                $table->index(['user_id', 'type']);
            });
        }

        if (! Schema::hasTable('photo_album_items')) {
            Schema::create('photo_album_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('album_id')->constrained('photo_albums')->cascadeOnDelete();
                $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
                $table->string('media_url', 255)->nullable();
                $table->string('caption', 255)->nullable();
                $table->string('location', 150)->nullable();
                $table->json('tagged_user_ids')->nullable();
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->timestamps();

                $table->index(['album_id', 'display_order']);
            });
        }

        // 6. Saved Items & Collections
        if (! Schema::hasTable('saved_items')) {
            Schema::create('saved_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('item_type', 50); // post, video, reel, photo, link
                $table->unsignedBigInteger('item_id');
                $table->string('collection_name', 100)->default('All Saved');
                $table->timestamps();

                $table->unique(['user_id', 'item_type', 'item_id']);
                $table->index(['user_id', 'collection_name']);
            });
        }

        // 7. Story Highlights
        if (! Schema::hasTable('story_highlights')) {
            Schema::create('story_highlights', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title', 100);
                $table->string('cover_url', 255)->nullable();
                $table->timestamps();

                $table->index('user_id');
            });
        }

        if (! Schema::hasTable('story_highlight_items')) {
            Schema::create('story_highlight_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('highlight_id')->constrained('story_highlights')->cascadeOnDelete();
                $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['highlight_id', 'story_id']);
            });
        }

        // 8. User Activity Logs
        if (! Schema::hasTable('user_activity_logs')) {
            Schema::create('user_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('action_type', 50)->index(); // profile_update, avatar_change, cover_change, post_create, comment_create, friend_add, login, etc.
                $table->string('description', 255);
                $table->string('ip_address', 45)->nullable();
                $table->string('device', 100)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
        Schema::dropIfExists('story_highlight_items');
        Schema::dropIfExists('story_highlights');
        Schema::dropIfExists('saved_items');
        Schema::dropIfExists('photo_album_items');
        Schema::dropIfExists('photo_albums');

        Schema::table('friendships', function (Blueprint $table) {
            $table->dropColumn(['is_favorite', 'is_close_friend', 'is_restricted', 'is_muted', 'muted_until', 'snoozed_until']);
        });

        Schema::dropIfExists('cover_photos');
        Schema::dropIfExists('profile_photos');

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'middle_name', 'religion', 'blood_group', 'division', 'district', 'upazila',
                'pronouns', 'category', 'portfolio', 'whatsapp', 'telegram', 'signal', 'messenger',
                'hobbies', 'favorite_music', 'favorite_books', 'favorite_movies', 'is_professional_mode',
            ]);
        });
    }
};
