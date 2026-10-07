<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Upgrade groups to Enterprise Community Operating System V2.
     */
    public function up(): void
    {
        // 1. Upgrade Groups Table with V2 Community Identity & Health Columns
        Schema::table('groups', function (Blueprint $table) {
            if (! Schema::hasColumn('groups', 'community_type')) {
                $table->string('community_type', 40)->default('interest')->index()->after('group_type');
            }
            if (! Schema::hasColumn('groups', 'tags')) {
                $table->json('tags')->nullable()->after('subcategory');
            }
            if (! Schema::hasColumn('groups', 'official_status')) {
                $table->string('official_status', 30)->default('standard')->after('verification_status');
            }
            if (! Schema::hasColumn('groups', 'health_score')) {
                $table->decimal('health_score', 5, 2)->default(100.00)->after('status');
            }
            if (! Schema::hasColumn('groups', 'health_metrics')) {
                $table->json('health_metrics')->nullable()->after('health_score');
            }
        });

        // 2. Structured Community Discussions (Q&A, Solved/Unsolved & Knowledge Exchange)
        Schema::create('group_discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('content');
            $table->string('type', 30)->default('discussion')->index(); // discussion, question, feedback
            $table->boolean('is_solved')->default(false)->index();
            $table->unsignedBigInteger('accepted_answer_id')->nullable()->index();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('replies_count')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['group_id', 'created_at']);
        });

        // 3. Threaded Discussion Replies
        Schema::create('group_discussion_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_id')->constrained('group_discussions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('group_discussion_replies')->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_accepted_answer')->default(false);
            $table->unsignedInteger('upvotes_count')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['discussion_id', 'created_at']);
        });

        // 4. Community Contributor Badges & Recognition
        Schema::create('group_member_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('badge_type', 40); // helpful_contributor, discussion_starter, event_organizer, guide_contributor, resource_contributor, community_mentor
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['group_id', 'user_id', 'badge_type'], 'grp_mem_badge_unique');
        });

        // 5. Community Subscriptions (Granular Activity Notifications)
        Schema::create('group_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subscribable_type', 40); // discussion, event, guide, announcement, poll
            $table->unsignedBigInteger('subscribable_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['group_id', 'user_id', 'subscribable_type', 'subscribable_id'], 'grp_sub_unique');
        });

        // 6. Community Bookmarks & Saved Resources
        Schema::create('group_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('bookmarkable_type', 40); // discussion, post, guide, event, resource
            $table->unsignedBigInteger('bookmarkable_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['group_id', 'user_id', 'bookmarkable_type', 'bookmarkable_id'], 'grp_bm_unique');
        });

        // 7. Community Custom Roles
        Schema::create('group_custom_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->string('color', 20)->default('#6366f1');
            $table->json('permissions');
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['group_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_custom_roles');
        Schema::dropIfExists('group_bookmarks');
        Schema::dropIfExists('group_subscriptions');
        Schema::dropIfExists('group_member_badges');
        Schema::dropIfExists('group_discussion_replies');
        Schema::dropIfExists('group_discussions');

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn([
                'community_type',
                'tags',
                'official_status',
                'health_score',
                'health_metrics',
            ]);
        });
    }
};
