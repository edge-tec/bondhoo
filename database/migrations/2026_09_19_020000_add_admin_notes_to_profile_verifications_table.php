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
        Schema::table('profile_verifications', function (Blueprint $table) {
            if (! Schema::hasColumn('profile_verifications', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('rejection_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_verifications', function (Blueprint $table) {
            if (Schema::hasColumn('profile_verifications', 'admin_notes')) {
                $table->dropColumn('admin_notes');
            }
        });
    }
};
