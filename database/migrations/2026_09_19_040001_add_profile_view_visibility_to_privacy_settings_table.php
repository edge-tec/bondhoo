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
        Schema::table('privacy_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('privacy_settings', 'profile_view_visibility')) {
                $table->string('profile_view_visibility', 20)->default('public')->after('social_links_privacy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('privacy_settings', function (Blueprint $table) {
            if (Schema::hasColumn('privacy_settings', 'profile_view_visibility')) {
                $table->dropColumn('profile_view_visibility');
            }
        });
    }
};
