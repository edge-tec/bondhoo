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
        // 1. Upgrade conversations table
        Schema::table('conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('conversations', 'avatar_url')) {
                $table->string('avatar_url')->nullable()->after('title');
            }
            if (! Schema::hasColumn('conversations', 'description')) {
                $table->text('description')->nullable()->after('avatar_url');
            }
            if (! Schema::hasColumn('conversations', 'settings')) {
                $table->json('settings')->nullable()->after('description');
            }
        });

        // 2. Upgrade conversation_participants table
        Schema::table('conversation_participants', function (Blueprint $table) {
            if (! Schema::hasColumn('conversation_participants', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('is_muted');
            }
            if (! Schema::hasColumn('conversation_participants', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_pinned');
            }
            if (! Schema::hasColumn('conversation_participants', 'muted_until')) {
                $table->timestamp('muted_until')->nullable()->after('is_archived');
            }
            if (! Schema::hasColumn('conversation_participants', 'is_request')) {
                $table->boolean('is_request')->default(false)->after('muted_until');
            }
            if (! Schema::hasColumn('conversation_participants', 'request_status')) {
                $table->string('request_status', 20)->default('accepted')->after('is_request'); // accepted, pending, declined
            }
            if (! Schema::hasColumn('conversation_participants', 'cleared_at')) {
                $table->timestamp('cleared_at')->nullable()->after('request_status');
            }
            if (! Schema::hasColumn('conversation_participants', 'draft_message')) {
                $table->text('draft_message')->nullable()->after('cleared_at');
            }
        });

        // 3. Upgrade messages table
        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'reply_to_message_id')) {
                $table->unsignedBigInteger('reply_to_message_id')->nullable()->after('conversation_id');
            }
            if (! Schema::hasColumn('messages', 'is_forwarded')) {
                $table->boolean('is_forwarded')->default(false)->after('is_deleted_for_everyone');
            }
            if (! Schema::hasColumn('messages', 'is_edited')) {
                $table->boolean('is_edited')->default(false)->after('is_forwarded');
            }
            if (! Schema::hasColumn('messages', 'edited_at')) {
                $table->timestamp('edited_at')->nullable()->after('is_edited');
            }
            if (! Schema::hasColumn('messages', 'edit_history')) {
                $table->json('edit_history')->nullable()->after('edited_at');
            }
        });

        // 4. Create message_reactions table
        if (! Schema::hasTable('message_reactions')) {
            Schema::create('message_reactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('reaction', 32); // 👍, ❤️, 😂, 😮, 😢, 😡, etc.
                $table->timestamps();

                $table->unique(['message_id', 'user_id']);
                $table->index(['message_id', 'reaction']);
            });
        }

        // 5. Create message_user_deletions table (Delete for Me)
        if (! Schema::hasTable('message_user_deletions')) {
            Schema::create('message_user_deletions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['message_id', 'user_id']);
            });
        }

        // 6. Privacy settings messaging columns
        Schema::table('privacy_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('privacy_settings', 'who_can_message_me')) {
                $table->string('who_can_message_me', 20)->default('everyone')->after('friends_list_visibility');
            }
            if (! Schema::hasColumn('privacy_settings', 'who_can_add_to_groups')) {
                $table->string('who_can_add_to_groups', 20)->default('everyone')->after('who_can_message_me');
            }
            if (! Schema::hasColumn('privacy_settings', 'show_online_status')) {
                $table->boolean('show_online_status')->default(true)->after('who_can_add_to_groups');
            }
            if (! Schema::hasColumn('privacy_settings', 'show_last_seen')) {
                $table->string('show_last_seen', 20)->default('everyone')->after('show_online_status');
            }
            if (! Schema::hasColumn('privacy_settings', 'read_receipts_enabled')) {
                $table->boolean('read_receipts_enabled')->default(true)->after('show_last_seen');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_user_deletions');
        Schema::dropIfExists('message_reactions');

        Schema::table('privacy_settings', function (Blueprint $table) {
            $cols = ['who_can_message_me', 'who_can_add_to_groups', 'show_online_status', 'show_last_seen', 'read_receipts_enabled'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('privacy_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            $cols = ['reply_to_message_id', 'is_forwarded', 'is_edited', 'edited_at', 'edit_history'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('messages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $cols = ['is_pinned', 'is_archived', 'muted_until', 'is_request', 'request_status', 'cleared_at', 'draft_message'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('conversation_participants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('conversations', function (Blueprint $table) {
            $cols = ['avatar_url', 'description', 'settings'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('conversations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
