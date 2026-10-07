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
        // 1. Extend user_profiles with country, city, address
        Schema::table('user_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('user_profiles', 'country')) {
                $table->string('country', 100)->nullable()->after('location');
            }
            if (! Schema::hasColumn('user_profiles', 'city')) {
                $table->string('city', 100)->nullable()->after('country');
            }
            if (! Schema::hasColumn('user_profiles', 'address')) {
                $table->string('address', 255)->nullable()->after('city');
            }
        });

        // 2. Extend privacy_settings with granular personal field privacy columns
        Schema::table('privacy_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('privacy_settings', 'first_name_privacy')) {
                $table->string('first_name_privacy', 20)->default('public')->after('profile_visibility'); // public, followers, friends, only_me
            }
            if (! Schema::hasColumn('privacy_settings', 'last_name_privacy')) {
                $table->string('last_name_privacy', 20)->default('public')->after('first_name_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'gender_privacy')) {
                $table->string('gender_privacy', 20)->default('public')->after('last_name_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'dob_privacy')) {
                $table->string('dob_privacy', 20)->default('friends')->after('gender_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'dob_display_format')) {
                $table->string('dob_display_format', 20)->default('month_day')->after('dob_privacy'); // full, month_day, age, hidden
            }
            if (! Schema::hasColumn('privacy_settings', 'country_privacy')) {
                $table->string('country_privacy', 20)->default('public')->after('dob_display_format');
            }
            if (! Schema::hasColumn('privacy_settings', 'city_privacy')) {
                $table->string('city_privacy', 20)->default('public')->after('country_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'address_privacy')) {
                $table->string('address_privacy', 20)->default('only_me')->after('city_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'phone_privacy')) {
                $table->string('phone_privacy', 20)->default('only_me')->after('address_privacy');
            }
            if (! Schema::hasColumn('privacy_settings', 'email_privacy')) {
                $table->string('email_privacy', 20)->default('only_me')->after('phone_privacy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['country', 'city', 'address']);
        });

        Schema::table('privacy_settings', function (Blueprint $table) {
            $table->dropColumn([
                'first_name_privacy',
                'last_name_privacy',
                'gender_privacy',
                'dob_privacy',
                'dob_display_format',
                'country_privacy',
                'city_privacy',
                'address_privacy',
                'phone_privacy',
                'email_privacy',
            ]);
        });
    }
};
