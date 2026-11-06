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
        Schema::table('marketplace_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplace_categories', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('id')->constrained('marketplace_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('marketplace_categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('display_order');
            }
            if (! Schema::hasColumn('marketplace_categories', 'description')) {
                $table->string('description')->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_categories', function (Blueprint $table) {
            if (Schema::hasColumn('marketplace_categories', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
            if (Schema::hasColumn('marketplace_categories', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('marketplace_categories', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
