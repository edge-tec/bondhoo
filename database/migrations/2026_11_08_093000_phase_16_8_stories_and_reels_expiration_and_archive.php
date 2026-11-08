<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ephemeral 24-hour lifecycle & archive fields for Stories and Reels.
     */
    public function up(): void
    {
        // 1. Stories Table Enhancements
        if (Schema::hasTable('stories')) {
            Schema::table('stories', function (Blueprint $table) {
                if (! Schema::hasColumn('stories', 'expired_at')) {
                    $table->timestamp('expired_at')->nullable()->after('expires_at')->index();
                }
                if (! Schema::hasColumn('stories', 'is_archived')) {
                    $table->boolean('is_archived')->default(false)->after('is_expired')->index();
                }
            });
        }

        // 2. Reels Table Enhancements
        if (Schema::hasTable('reels')) {
            Schema::table('reels', function (Blueprint $table) {
                if (! Schema::hasColumn('reels', 'expired_at')) {
                    $table->timestamp('expired_at')->nullable()->after('expires_at')->index();
                }
                if (! Schema::hasColumn('reels', 'is_archived')) {
                    $table->boolean('is_archived')->default(false)->after('is_expired')->index();
                }
            });

            // Compound index for archive and feed querying
            Schema::table('reels', function (Blueprint $table) {
                $table->index(['user_id', 'is_archived', 'is_expired'], 'reels_user_archive_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('reels')) {
            Schema::table('reels', function (Blueprint $table) {
                $table->dropIndex('reels_user_archive_idx');
                if (Schema::hasColumn('reels', 'is_archived')) {
                    $table->dropColumn('is_archived');
                }
                if (Schema::hasColumn('reels', 'expired_at')) {
                    $table->dropColumn('expired_at');
                }
            });
        }

        if (Schema::hasTable('stories')) {
            Schema::table('stories', function (Blueprint $table) {
                if (Schema::hasColumn('stories', 'expired_at')) {
                    $table->dropColumn('expired_at');
                }
            });
        }
    }
};
