<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Phase 16.4 Enterprise Messenger & Calling scalability.
     */
    public function up(): void
    {
        // 1. conversation_participants composite indexes for Inbox filtering
        if (Schema::hasTable('conversation_participants')) {
            Schema::table('conversation_participants', function (Blueprint $table) {
                // Check if index already exists before creating
                $driver = DB::getDriverName();
                if ($driver !== 'sqlite') {
                    $indexes = DB::select("SHOW INDEX FROM conversation_participants WHERE Key_name = 'cp_user_archive_pin_idx'");
                    if (empty($indexes)) {
                        $table->index(['user_id', 'is_archived', 'is_pinned'], 'cp_user_archive_pin_idx');
                    }
                    $requestIndexes = DB::select("SHOW INDEX FROM conversation_participants WHERE Key_name = 'cp_user_req_status_idx'");
                    if (empty($requestIndexes)) {
                        $table->index(['user_id', 'is_request', 'request_status'], 'cp_user_req_status_idx');
                    }
                } else {
                    try {
                        $table->index(['user_id', 'is_archived', 'is_pinned'], 'cp_user_archive_pin_idx');
                    } catch (Throwable $e) {
                    }
                    try {
                        $table->index(['user_id', 'is_request', 'request_status'], 'cp_user_req_status_idx');
                    } catch (Throwable $e) {
                    }
                }
            });
        }

        // 2. messages composite index for timestamp pagination
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                $driver = DB::getDriverName();
                if ($driver !== 'sqlite') {
                    $indexes = DB::select("SHOW INDEX FROM messages WHERE Key_name = 'messages_conv_created_idx'");
                    if (empty($indexes)) {
                        $table->index(['conversation_id', 'created_at'], 'messages_conv_created_idx');
                    }
                } else {
                    try {
                        $table->index(['conversation_id', 'created_at'], 'messages_conv_created_idx');
                    } catch (Throwable $e) {
                    }
                }
            });
        }

        // 3. calls table room_id and status composite index
        if (Schema::hasTable('calls')) {
            Schema::table('calls', function (Blueprint $table) {
                $driver = DB::getDriverName();
                if ($driver !== 'sqlite') {
                    $indexes = DB::select("SHOW INDEX FROM calls WHERE Key_name = 'calls_room_status_idx'");
                    if (empty($indexes)) {
                        $table->index(['room_id', 'status'], 'calls_room_status_idx');
                    }
                } else {
                    try {
                        $table->index(['room_id', 'status'], 'calls_room_status_idx');
                    } catch (Throwable $e) {
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('conversation_participants')) {
            Schema::table('conversation_participants', function (Blueprint $table) {
                try {
                    $table->dropIndex('cp_user_archive_pin_idx');
                } catch (Throwable $e) {
                }
                try {
                    $table->dropIndex('cp_user_req_status_idx');
                } catch (Throwable $e) {
                }
            });
        }

        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                try {
                    $table->dropIndex('messages_conv_created_idx');
                } catch (Throwable $e) {
                }
            });
        }

        if (Schema::hasTable('calls')) {
            Schema::table('calls', function (Blueprint $table) {
                try {
                    $table->dropIndex('calls_room_status_idx');
                } catch (Throwable $e) {
                }
            });
        }
    }
};
