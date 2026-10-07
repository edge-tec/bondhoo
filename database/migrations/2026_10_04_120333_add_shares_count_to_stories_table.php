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
        Schema::table('stories', function (Blueprint $table) {
            if (! Schema::hasColumn('stories', 'shares_count')) {
                $table->unsignedInteger('shares_count')->default(0)->after('replies_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            if (Schema::hasColumn('stories', 'shares_count')) {
                $table->dropColumn('shares_count');
            }
        });
    }
};
