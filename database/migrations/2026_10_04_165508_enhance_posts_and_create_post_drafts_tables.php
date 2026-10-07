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
        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'collaborator_id')) {
                $table->foreignId('collaborator_id')->nullable()->after('page_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('posts', 'collaborator_status')) {
                $table->string('collaborator_status', 20)->default('pending')->after('collaborator_id');
            }
            if (! Schema::hasColumn('posts', 'tagged_user_ids')) {
                $table->json('tagged_user_ids')->nullable()->after('collaborator_status');
            }
            if (! Schema::hasColumn('posts', 'background_style')) {
                $table->string('background_style', 60)->nullable()->after('type');
            }
            if (! Schema::hasColumn('posts', 'is_ai_generated')) {
                $table->boolean('is_ai_generated')->default(false)->after('background_style');
            }
            if (! Schema::hasColumn('posts', 'content_warning')) {
                $table->string('content_warning', 150)->nullable()->after('is_ai_generated');
            }
            if (! Schema::hasColumn('posts', 'media_meta')) {
                $table->json('media_meta')->nullable()->after('content_warning');
            }
            if (! Schema::hasColumn('posts', 'shared_to_story')) {
                $table->boolean('shared_to_story')->default(false)->after('media_meta');
            }
        });

        if (! Schema::hasTable('post_drafts')) {
            Schema::create('post_drafts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
                $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
                $table->text('content')->nullable();
                $table->string('audience', 20)->default('public');
                $table->string('type', 20)->default('text');
                $table->string('location')->nullable();
                $table->string('feeling_activity')->nullable();
                $table->json('poll_data')->nullable();
                $table->json('link_preview')->nullable();
                $table->string('background_style', 60)->nullable();
                $table->boolean('is_ai_generated')->default(false);
                $table->string('content_warning', 150)->nullable();
                $table->boolean('comments_disabled')->default(false);
                $table->json('tagged_user_ids')->nullable();
                $table->foreignId('collaborator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('media_ids')->nullable();
                $table->json('media_meta')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'updated_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('post_drafts');

        Schema::table('posts', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['shared_to_story', 'media_meta', 'content_warning', 'is_ai_generated', 'background_style', 'tagged_user_ids', 'collaborator_status'] as $col) {
                if (Schema::hasColumn('posts', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
            if (Schema::hasColumn('posts', 'collaborator_id')) {
                $table->dropConstrainedForeignId('collaborator_id');
            }
        });
    }
};
