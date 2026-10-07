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
        if (Schema::hasTable('profile_social_links')) {
            Schema::table('profile_social_links', function (Blueprint $table) {
                if (! Schema::hasColumn('profile_social_links', 'privacy')) {
                    $table->string('privacy', 20)->default('public')->after('is_visible');
                    $table->index(['user_id', 'privacy']);
                }
                $table->index(['user_id', 'display_order']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('profile_social_links')) {
            Schema::table('profile_social_links', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'display_order']);
                if (Schema::hasColumn('profile_social_links', 'privacy')) {
                    $table->dropIndex(['user_id', 'privacy']);
                    $table->dropColumn('privacy');
                }
            });
        }
    }
};
