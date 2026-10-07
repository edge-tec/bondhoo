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
        if (Schema::hasTable('profile_skills')) {
            Schema::table('profile_skills', function (Blueprint $table) {
                $table->index('name');
                $table->index(['user_id', 'display_order']);
            });
        }

        if (Schema::hasTable('profile_interests')) {
            Schema::table('profile_interests', function (Blueprint $table) {
                $table->index('name');
            });
        }

        if (Schema::hasTable('profile_languages')) {
            Schema::table('profile_languages', function (Blueprint $table) {
                $table->index('language');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('profile_skills')) {
            Schema::table('profile_skills', function (Blueprint $table) {
                $table->dropIndex(['name']);
                $table->dropIndex(['user_id', 'display_order']);
            });
        }

        if (Schema::hasTable('profile_interests')) {
            Schema::table('profile_interests', function (Blueprint $table) {
                $table->dropIndex(['name']);
            });
        }

        if (Schema::hasTable('profile_languages')) {
            Schema::table('profile_languages', function (Blueprint $table) {
                $table->dropIndex(['language']);
            });
        }
    }
};
