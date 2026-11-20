<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Ultra Enterprise Messenger.
     */
    public function up(): void
    {
        // 1. Add expires_at to messages if not present
        if (Schema::hasTable('messages') && ! Schema::hasColumn('messages', 'expires_at')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->index();
            });
        }

        // 2. Chat Polls Table
        if (! Schema::hasTable('chat_polls')) {
            Schema::create('chat_polls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
                $table->foreignId('message_id')->nullable()->constrained('messages')->cascadeOnDelete();
                $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
                $table->text('question');
                $table->boolean('is_multiple_choice')->default(false);
                $table->boolean('is_anonymous')->default(false);
                $table->boolean('can_change_vote')->default(true);
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_closed')->default(false);
                $table->unsignedInteger('total_votes_count')->default(0);
                $table->timestamps();

                $table->index(['conversation_id', 'created_at']);
            });
        }

        // 3. Chat Poll Options Table
        if (! Schema::hasTable('chat_poll_options')) {
            Schema::create('chat_poll_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('poll_id')->constrained('chat_polls')->cascadeOnDelete();
                $table->string('option_text');
                $table->unsignedInteger('votes_count')->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['poll_id', 'sort_order']);
            });
        }

        // 4. Chat Poll Votes Table
        if (! Schema::hasTable('chat_poll_votes')) {
            Schema::create('chat_poll_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('poll_id')->constrained('chat_polls')->cascadeOnDelete();
                $table->foreignId('poll_option_id')->constrained('chat_poll_options')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['poll_id', 'user_id', 'poll_option_id'], 'chat_poll_user_opt_unique');
                $table->index(['poll_id', 'user_id']);
            });
        }

        // 5. Scheduled Messages Table
        if (! Schema::hasTable('scheduled_messages')) {
            Schema::create('scheduled_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->string('type', 30)->default('text');
                $table->text('body')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('scheduled_at')->index();
                $table->string('status', 20)->default('pending')->index(); // pending, sent, cancelled, failed
                $table->string('timezone', 50)->default('UTC');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'scheduled_at']);
                $table->index(['conversation_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_messages');
        Schema::dropIfExists('chat_poll_votes');
        Schema::dropIfExists('chat_poll_options');
        Schema::dropIfExists('chat_polls');

        if (Schema::hasTable('messages') && Schema::hasColumn('messages', 'expires_at')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropColumn('expires_at');
            });
        }
    }
};
