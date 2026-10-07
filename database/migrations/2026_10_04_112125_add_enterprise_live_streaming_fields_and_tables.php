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
        Schema::table('live_streams', function (Blueprint $table) {
            $table->string('privacy')->default('public')->after('description'); // public, friends, only_me
            $table->boolean('comments_enabled')->default(true)->after('total_reactions');
            $table->boolean('reactions_enabled')->default(true)->after('comments_enabled');
            $table->boolean('sharing_enabled')->default(true)->after('reactions_enabled');
            $table->boolean('recording_enabled')->default(true)->after('sharing_enabled');
            $table->string('recording_status')->default('none')->after('recording_enabled'); // none, recording, processing, ready, failed
            $table->text('recording_url')->nullable()->after('recording_status');
            $table->unsignedInteger('total_unique_viewers')->default(0)->after('peak_viewers');
            $table->unsignedInteger('total_shares')->default(0)->after('total_reactions');
            $table->string('thumbnail')->nullable()->after('recording_url');

            $table->index(['privacy', 'status']);
        });

        Schema::create('live_stream_viewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained('live_streams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 64)->index();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('last_ping_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['live_stream_id', 'session_id']);
            $table->index(['live_stream_id', 'is_active']);
            $table->index(['live_stream_id', 'user_id']);
        });

        Schema::create('live_stream_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained('live_streams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reaction_type', 32); // like, love, care, haha, wow, sad, angry
            $table->timestamps();

            $table->index(['live_stream_id', 'reaction_type']);
        });

        Schema::create('live_stream_moderators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained('live_streams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('appointed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['live_stream_id', 'user_id']);
        });

        Schema::create('live_stream_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained('live_streams')->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 64); // harassment, violence, sexual_content, hate, spam, copyright, other
            $table->text('details')->nullable();
            $table->string('status', 32)->default('pending'); // pending, reviewed, dismissed, action_taken
            $table->foreignId('action_taken_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['live_stream_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_stream_reports');
        Schema::dropIfExists('live_stream_moderators');
        Schema::dropIfExists('live_stream_reactions');
        Schema::dropIfExists('live_stream_viewers');

        Schema::table('live_streams', function (Blueprint $table) {
            $table->dropIndex(['privacy', 'status']);
            $table->dropColumn([
                'privacy',
                'comments_enabled',
                'reactions_enabled',
                'sharing_enabled',
                'recording_enabled',
                'recording_status',
                'recording_url',
                'total_unique_viewers',
                'total_shares',
                'thumbnail',
            ]);
        });
    }
};
