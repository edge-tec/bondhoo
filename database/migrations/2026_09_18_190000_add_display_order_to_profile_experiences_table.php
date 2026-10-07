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
        if (Schema::hasTable('profile_experiences')) {
            Schema::table('profile_experiences', function (Blueprint $table) {
                if (! Schema::hasColumn('profile_experiences', 'display_order')) {
                    $table->unsignedSmallInteger('display_order')->default(0)->after('description');
                    $table->index(['user_id', 'display_order']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('profile_experiences')) {
            Schema::table('profile_experiences', function (Blueprint $table) {
                if (Schema::hasColumn('profile_experiences', 'display_order')) {
                    $table->dropIndex(['user_id', 'display_order']);
                    $table->dropColumn('display_order');
                }
            });
        }
    }
};
