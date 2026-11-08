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
        Schema::table('post_shares', function (Blueprint $table) {
            if (! Schema::hasColumn('post_shares', 'target_type')) {
                $table->string('target_type', 30)->default('timeline')->after('caption')->index();
            }
            if (! Schema::hasColumn('post_shares', 'target_id')) {
                $table->unsignedBigInteger('target_id')->nullable()->after('target_type')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_shares', function (Blueprint $table) {
            if (Schema::hasColumn('post_shares', 'target_id')) {
                $table->dropColumn('target_id');
            }
            if (Schema::hasColumn('post_shares', 'target_type')) {
                $table->dropColumn('target_type');
            }
        });
    }
};
