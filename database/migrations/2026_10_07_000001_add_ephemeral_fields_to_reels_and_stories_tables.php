<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ephemeral 24-hour lifecycle fields and indexes for Reels and Stories.
     */
    public function up(): void
    {
        // 1. Update Reels table
        if (Schema::hasTable('reels')) {
            Schema::table('reels', function (Blueprint $table) {
                if (! Schema::hasColumn('reels', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('status')->index();
                }
                if (! Schema::hasColumn('reels', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable()->after('published_at')->index();
                }
                if (! Schema::hasColumn('reels', 'is_expired')) {
                    $table->boolean('is_expired')->default(false)->after('expires_at')->index();
                }
            });

            // Add compound indexes
            Schema::table('reels', function (Blueprint $table) {
                $table->index(['status', 'is_expired', 'expires_at'], 'reels_status_active_idx');
                $table->index(['user_id', 'status', 'is_expired'], 'reels_user_status_active_idx');
            });

            // Backfill existing reels if any
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                DB::table('reels')->whereNull('published_at')->update([
                    'published_at' => DB::raw('created_at'),
                    'expires_at' => DB::raw("datetime(created_at, '+24 hours')"),
                ]);
            } else {
                DB::table('reels')->whereNull('published_at')->update([
                    'published_at' => DB::raw('created_at'),
                    'expires_at' => DB::raw('DATE_ADD(created_at, INTERVAL 24 HOUR)'),
                ]);
            }

            DB::table('reels')->whereNotNull('expires_at')->where('expires_at', '<=', now())->update([
                'is_expired' => true,
            ]);
        }

        // 2. Update Stories table
        if (Schema::hasTable('stories')) {
            Schema::table('stories', function (Blueprint $table) {
                if (! Schema::hasColumn('stories', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('status')->index();
                }
            });

            // Backfill published_at for existing stories
            DB::table('stories')->whereNull('published_at')->update([
                'published_at' => DB::raw('created_at'),
            ]);

            Schema::table('stories', function (Blueprint $table) {
                $table->index(['status', 'is_expired', 'expires_at'], 'stories_status_active_idx');
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
                $table->dropIndex('reels_status_active_idx');
                $table->dropIndex('reels_user_status_active_idx');
                if (Schema::hasColumn('reels', 'is_expired')) {
                    $table->dropColumn('is_expired');
                }
                if (Schema::hasColumn('reels', 'expires_at')) {
                    $table->dropColumn('expires_at');
                }
                if (Schema::hasColumn('reels', 'published_at')) {
                    $table->dropColumn('published_at');
                }
            });
        }

        if (Schema::hasTable('stories')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->dropIndex('stories_status_active_idx');
                if (Schema::hasColumn('stories', 'published_at')) {
                    $table->dropColumn('published_at');
                }
            });
        }
    }
};
