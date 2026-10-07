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
            if (! Schema::hasColumn('privacy_settings', 'avatar_privacy')) {
                $table->string('avatar_privacy', 20)->default('public')->after('profile_visibility');
            }
            if (! Schema::hasColumn('privacy_settings', 'cover_privacy')) {
                $table->string('cover_privacy', 20)->default('public')->after('avatar_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'bio_privacy')) {
                $table->string('bio_privacy', 20)->default('public')->after('cover_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'about_privacy')) {
                $table->string('about_privacy', 20)->default('public')->after('bio_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'location_privacy')) {
                $table->string('location_privacy', 20)->default('public')->after('about_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'education_privacy')) {
                $table->string('education_privacy', 20)->default('public')->after('location_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'work_privacy')) {
                $table->string('work_privacy', 20)->default('public')->after('education_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'skills_privacy')) {
                $table->string('skills_privacy', 20)->default('public')->after('work_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'interests_privacy')) {
                $table->string('interests_privacy', 20)->default('public')->after('skills_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'languages_privacy')) {
                $table->string('languages_privacy', 20)->default('public')->after('interests_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'social_links_privacy')) {
                $table->string('social_links_privacy', 20)->default('public')->after('languages_privacy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('privacy_settings', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_privacy',
                'cover_privacy',
                'bio_privacy',
                'about_privacy',
                'location_privacy',
                'education_privacy',
                'work_privacy',
                'skills_privacy',
                'interests_privacy',
                'languages_privacy',
                'social_links_privacy',
            ]);
        });
    }
};
