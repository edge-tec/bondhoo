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
        Schema::table('profile_educations', function (Blueprint $table) {
            if (! Schema::hasColumn('profile_educations', 'location')) {
                $table->string('location', 191)->nullable()->after('description');
            }
            if (! Schema::hasColumn('profile_educations', 'display_order')) {
                $table->unsignedInteger('display_order')->default(0)->after('privacy');
                $table->index(['user_id', 'display_order']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_educations', function (Blueprint $table) {
            if (Schema::hasColumn('profile_educations', 'display_order')) {
                $table->dropIndex(['user_id', 'display_order']);
                $table->dropColumn('display_order');
            }
            if (Schema::hasColumn('profile_educations', 'location')) {
                $table->dropColumn('location');
            }
        });
    }
};
