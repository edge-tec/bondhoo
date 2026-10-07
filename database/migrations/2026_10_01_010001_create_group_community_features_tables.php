<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create Enterprise Community Features tables.
     */
    public function up(): void
    {
        // 1. Group Rules
        Schema::create('group_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description');
            $table->string('enforcement_level', 30)->default('warning'); // warning, strike, removal
            $table->string('warning_action')->nullable();
            $table->string('strike_action')->nullable();
            $table->string('removal_action')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['group_id', 'sort_order']);
        });

        // 2. Membership Screening Questions
        Schema::create('group_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->text('question');
            $table->string('type', 30)->default('text'); // text, multiple_choice, checkbox, boolean
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['group_id', 'sort_order']);
        });

        // 3. Screening Question Answers
        Schema::create('group_question_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('group_questions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('answer');
            $table->timestamps();

            $table->unique(['group_id', 'question_id', 'user_id'], 'grp_qa_unique');
        });

        // 4. Group Polls
        Schema::create('group_polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->text('question');
            $table->boolean('is_multiple_choice')->default(false);
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('can_change_vote')->default(true);
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->unsignedInteger('total_votes_count')->default(0);
            $table->timestamps();

            $table->index(['group_id', 'created_at']);
        });

        // 5. Poll Options
        Schema::create('group_poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('group_polls')->cascadeOnDelete();
            $table->string('option_text');
            $table->unsignedInteger('votes_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 6. Poll Votes
        Schema::create('group_poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('group_polls')->cascadeOnDelete();
            $table->foreignId('poll_option_id')->constrained('group_poll_options')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['poll_id', 'user_id', 'poll_option_id'], 'grp_pv_unique');
        });

        // 7. Group Events
        Schema::create('group_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_online')->default(false);
            $table->string('meeting_url')->nullable();
            $table->timestamp('start_time')->index();
            $table->timestamp('end_time')->nullable();
            $table->string('timezone', 50)->default('UTC');
            $table->unsignedInteger('attendees_count')->default(0);
            $table->timestamps();

            $table->index(['group_id', 'start_time']);
        });

        // 8. Event Attendees
        Schema::create('group_event_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('group_events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('going'); // going, interested, not_going
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });

        // 9. Group Announcements
        Schema::create('group_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('cta_text', 50)->nullable();
            $table->string('cta_url')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'is_pinned']);
        });

        // 10. Featured Content
        Schema::create('group_featured_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('item_type', 30); // post, event, guide, poll, announcement
            $table->unsignedBigInteger('item_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'item_type', 'item_id']);
        });

        // 11. Group Guides / Knowledge Base
        Schema::create('group_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['group_id', 'sort_order']);
        });

        // 12. Guide Sections
        Schema::create('group_guide_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')->constrained('group_guides')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->json('resources')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 13. Group Files & Documents
        Schema::create('group_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('file_url');
            $table->string('file_type', 50)->default('document'); // document, spreadsheet, pdf, archive, other
            $table->unsignedBigInteger('file_size')->default(0); // in bytes
            $table->unsignedInteger('downloads_count')->default(0);
            $table->timestamps();

            $table->index(['group_id', 'file_type']);
        });

        // 14. Group Invitations
        Schema::create('group_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('inviter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invitee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->string('role', 20)->default('member');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_uses')->default(1);
            $table->unsignedInteger('uses_count')->default(0);
            $table->string('status', 20)->default('pending')->index(); // pending, accepted, revoked, expired
            $table->timestamps();
        });

        // 15. Group Moderation Actions & Audit
        Schema::create('group_moderation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('moderator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50); // approve_post, reject_post, delete_post, warn_member, mute_member, ban_member, unban_member, strike_member, update_settings
            $table->string('target_type', 50)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'created_at']);
        });

        // 16. Group Member Strikes & Warnings
        Schema::create('group_member_strikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('moderator_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->string('action_taken', 50)->nullable(); // warning, mute_24h, mute_7d, restricted, banned
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'user_id']);
        });

        // 17. Group Reports
        Schema::create('group_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reportable_type', 50); // post, comment, member, media, event
            $table->unsignedBigInteger('reportable_id');
            $table->string('reason_category', 50); // spam, harassment, hate, violence, fraud, sexual, copyright, misinformation, other
            $table->text('description')->nullable();
            $table->json('evidence')->nullable();
            $table->string('status', 30)->default('pending')->index(); // pending, reviewing, action_taken, dismissed, closed
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'status']);
        });

        // 18. Notification Preferences
        Schema::create('group_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('preference', 30)->default('all'); // all, highlights, friends, admin_only, off
            $table->timestamps();

            $table->unique(['group_id', 'user_id']);
        });

        // 19. Group Analytics Snapshots
        Schema::create('group_analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('total_members')->default(0);
            $table->unsignedInteger('new_members')->default(0);
            $table->unsignedInteger('active_members')->default(0);
            $table->unsignedInteger('posts_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('reactions_count')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['group_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_analytics_snapshots');
        Schema::dropIfExists('group_notification_preferences');
        Schema::dropIfExists('group_reports');
        Schema::dropIfExists('group_member_strikes');
        Schema::dropIfExists('group_moderation_actions');
        Schema::dropIfExists('group_invitations');
        Schema::dropIfExists('group_files');
        Schema::dropIfExists('group_guide_sections');
        Schema::dropIfExists('group_guides');
        Schema::dropIfExists('group_featured_items');
        Schema::dropIfExists('group_announcements');
        Schema::dropIfExists('group_event_attendees');
        Schema::dropIfExists('group_events');
        Schema::dropIfExists('group_poll_votes');
        Schema::dropIfExists('group_poll_options');
        Schema::dropIfExists('group_polls');
        Schema::dropIfExists('group_question_answers');
        Schema::dropIfExists('group_questions');
        Schema::dropIfExists('group_rules');
    }
};
