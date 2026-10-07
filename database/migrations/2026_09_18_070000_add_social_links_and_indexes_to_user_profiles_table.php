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
            if (! Schema::hasColumn('user_profiles', 'social_links')) {
                $table->json('social_links')->nullable()->after('interests');
            }
            if (! Schema::hasColumn('user_profiles', 'cover_position_y')) {
                $table->smallInteger('cover_position_y')->default(50)->after('cover_url');
            }
            $table->index('display_name');
            $table->index('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex(['display_name']);
            $table->dropIndex(['location']);
            $table->dropColumn(['social_links', 'cover_position_y']);
        });
    }
};
