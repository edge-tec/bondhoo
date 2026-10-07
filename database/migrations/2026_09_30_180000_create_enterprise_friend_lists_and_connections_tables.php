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
        // 1. Custom Friend Lists (Family, Work, College, Close Friends, etc.)
        if (! Schema::hasTable('friend_lists')) {
            Schema::create('friend_lists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('slug', 120);
                $table->string('type', 32)->default('custom'); // custom, family, work, school, close_friends, favorites
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false);
                $table->timestamps();

                $table->unique(['user_id', 'slug']);
                $table->index(['user_id', 'type']);
            });
        }

        // 2. Friend List Members
        if (! Schema::hasTable('friend_list_members')) {
            Schema::create('friend_list_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('friend_list_id')->constrained('friend_lists')->cascadeOnDelete();
                $table->foreignId('friend_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['friend_list_id', 'friend_id']);
                $table->index(['friend_id']);
            });
        }

        // 3. Extend friendships table with timestamps for enterprise analytics and timeline sorting
        Schema::table('friendships', function (Blueprint $table) {
            if (! Schema::hasColumn('friendships', 'requested_at')) {
                $table->timestamp('requested_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('friendships', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('requested_at');
            }
            if (! Schema::hasColumn('friendships', 'declined_at')) {
                $table->timestamp('declined_at')->nullable()->after('accepted_at');
            }
            if (! Schema::hasColumn('friendships', 'interacted_at')) {
                $table->timestamp('interacted_at')->nullable()->after('declined_at');
            }
        });

        // 4. Extend privacy_settings with social connection privacy rules
        Schema::table('privacy_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('privacy_settings', 'who_can_send_friend_requests')) {
                $table->string('who_can_send_friend_requests', 32)->default('everyone')->after('friends_list_visibility');
            }
            if (! Schema::hasColumn('privacy_settings', 'who_can_follow_me')) {
                $table->string('who_can_follow_me', 32)->default('everyone')->after('who_can_send_friend_requests');
            }
            if (! Schema::hasColumn('privacy_settings', 'mutual_friends_visibility')) {
                $table->string('mutual_friends_visibility', 32)->default('everyone')->after('who_can_follow_me');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friend_list_members');
        Schema::dropIfExists('friend_lists');

        Schema::table('friendships', function (Blueprint $table) {
            if (Schema::hasColumn('friendships', 'interacted_at')) {
                $table->dropColumn('interacted_at');
            }
            if (Schema::hasColumn('friendships', 'declined_at')) {
                $table->dropColumn('declined_at');
            }
            if (Schema::hasColumn('friendships', 'accepted_at')) {
                $table->dropColumn('accepted_at');
            }
            if (Schema::hasColumn('friendships', 'requested_at')) {
                $table->dropColumn('requested_at');
            }
        });

        Schema::table('privacy_settings', function (Blueprint $table) {
            if (Schema::hasColumn('privacy_settings', 'mutual_friends_visibility')) {
                $table->dropColumn('mutual_friends_visibility');
            }
            if (Schema::hasColumn('privacy_settings', 'who_can_follow_me')) {
                $table->dropColumn('who_can_follow_me');
            }
            if (Schema::hasColumn('privacy_settings', 'who_can_send_friend_requests')) {
                $table->dropColumn('who_can_send_friend_requests');
            }
        });
    }
};
