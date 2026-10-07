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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('hometown')->nullable()->after('location');
            $table->string('relationship_status', 30)->nullable()->after('gender'); // single, in_a_relationship, engaged, married, complicated
        });

        Schema::table('user_settings', function (Blueprint $table) {
            $table->boolean('is_profile_locked')->default(false)->after('story_visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['hometown', 'relationship_status']);
        });

        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('is_profile_locked');
        });
    }
};
