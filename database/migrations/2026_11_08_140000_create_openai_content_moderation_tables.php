<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Phase 16.9: OpenAI AI Content Moderation & Anti-Abuse Engine.
     */
    public function up(): void
    {
        // 1. Moderation Requests
        if (! Schema::hasTable('moderation_requests')) {
            Schema::create('moderation_requests', function (Blueprint $table) {
                $table->id();
                $table->string('uuid', 64)->unique();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('moderatable_type')->nullable();
                $table->unsignedBigInteger('moderatable_id')->nullable();
                $table->string('content_type', 32)->default('text'); // text, image, video, link, composite
                $table->string('status', 32)->default('pending'); // pending, approved, rejected, quarantined, manual_review, escalated
                $table->string('model', 64)->default('omni-moderation-latest');
                $table->boolean('flagged')->default(false);
                $table->json('categories')->nullable();
                $table->json('category_scores')->nullable();
                $table->string('action_taken', 64)->default('auto_approved'); // auto_approved, auto_rejected, queued_for_review, quarantined, admin_approved, admin_rejected
                $table->string('rejection_reason')->nullable();
                $table->text('error_message')->nullable();
                $table->json('raw_response')->nullable();
                $table->timestamps();

                $table->index(['moderatable_type', 'moderatable_id']);
                $table->index(['status', 'created_at']);
                $table->index('user_id');
            });
        }

        // 2. Moderation Results
        if (! Schema::hasTable('moderation_results')) {
            Schema::create('moderation_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('moderation_request_id')->constrained('moderation_requests')->cascadeOnDelete();
                $table->string('target_type')->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->boolean('flagged')->default(false);
                $table->json('categories')->nullable();
                $table->json('category_scores')->nullable();
                $table->string('policy_decision', 32)->default('approve'); // approve, reject, manual_review, quarantine
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('reviewer_notes')->nullable();
                $table->timestamps();

                $table->index(['target_type', 'target_id']);
            });
        }

        // 3. Link Scan Results (SSRF Safe)
        if (! Schema::hasTable('link_scan_results')) {
            Schema::create('link_scan_results', function (Blueprint $table) {
                $table->id();
                $table->text('url');
                $table->string('normalized_url', 1024)->index();
                $table->text('final_url')->nullable();
                $table->string('domain')->index();
                $table->string('status', 32)->default('safe'); // safe, suspicious, malicious, failed
                $table->integer('http_status')->nullable();
                $table->text('title')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_ssrf_blocked')->default(false);
                $table->string('blocked_ip', 64)->nullable();
                $table->integer('redirect_hops')->default(0);
                $table->json('flagged_categories')->nullable();
                $table->timestamp('scanned_at');
                $table->timestamps();
            });
        }

        // 4. Moderation Policy Settings
        if (! Schema::hasTable('moderation_policy_settings')) {
            Schema::create('moderation_policy_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value');
                $table->string('description')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 5. Moderation Audit Logs
        if (! Schema::hasTable('moderation_audit_logs')) {
            Schema::create('moderation_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 64); // auto_flagged, auto_approved, manual_approve, manual_reject, quarantine, escalate, suspend_user, update_policy
                $table->string('target_type')->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->string('reason')->nullable();
                $table->json('metadata')->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['target_type', 'target_id']);
                $table->index('action');
            });
        }

        // 6. Add status to comments if missing
        if (Schema::hasTable('comments') && ! Schema::hasColumn('comments', 'status')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->string('status', 32)->default('published')->after('body')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('comments') && Schema::hasColumn('comments', 'status')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
        Schema::dropIfExists('moderation_audit_logs');
        Schema::dropIfExists('moderation_policy_settings');
        Schema::dropIfExists('link_scan_results');
        Schema::dropIfExists('moderation_results');
        Schema::dropIfExists('moderation_requests');
    }
};

