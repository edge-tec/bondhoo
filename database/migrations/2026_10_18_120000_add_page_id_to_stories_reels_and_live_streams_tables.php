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
        if (Schema::hasTable('stories') && ! Schema::hasColumn('stories', 'page_id')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->foreignId('page_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('pages')
                    ->nullOnDelete();
                $table->index(['page_id', 'created_at']);
                $table->index(['page_id', 'is_expired']);
            });
        }

        if (Schema::hasTable('reels') && ! Schema::hasColumn('reels', 'page_id')) {
            Schema::table('reels', function (Blueprint $table) {
                $table->foreignId('page_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('pages')
                    ->nullOnDelete();
                $table->index(['page_id', 'created_at']);
                $table->index(['page_id', 'status']);
            });
        }

        if (Schema::hasTable('live_streams') && ! Schema::hasColumn('live_streams', 'page_id')) {
            Schema::table('live_streams', function (Blueprint $table) {
                $table->foreignId('page_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('pages')
                    ->nullOnDelete();
                $table->index(['page_id', 'created_at']);
                $table->index(['page_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stories') && Schema::hasColumn('stories', 'page_id')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->dropForeign(['page_id']);
                $table->dropIndex(['page_id', 'created_at']);
                $table->dropIndex(['page_id', 'is_expired']);
                $table->dropColumn('page_id');
            });
        }

        if (Schema::hasTable('reels') && Schema::hasColumn('reels', 'page_id')) {
            Schema::table('reels', function (Blueprint $table) {
                $table->dropForeign(['page_id']);
                $table->dropIndex(['page_id', 'created_at']);
                $table->dropIndex(['page_id', 'status']);
                $table->dropColumn('page_id');
            });
        }

        if (Schema::hasTable('live_streams') && Schema::hasColumn('live_streams', 'page_id')) {
            Schema::table('live_streams', function (Blueprint $table) {
                $table->dropForeign(['page_id']);
                $table->dropIndex(['page_id', 'created_at']);
                $table->dropIndex(['page_id', 'status']);
                $table->dropColumn('page_id');
            });
        }
    }
};
