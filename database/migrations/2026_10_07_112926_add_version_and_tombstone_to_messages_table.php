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
        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('metadata');
            }
            if (! Schema::hasColumn('messages', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('is_deleted_for_everyone');
            }
            if (! Schema::hasColumn('messages', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'deleted_by')) {
                $table->dropForeign(['deleted_by']);
                $table->dropColumn('deleted_by');
            }
            if (Schema::hasColumn('messages', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
            if (Schema::hasColumn('messages', 'version')) {
                $table->dropColumn('version');
            }
        });
    }
};
