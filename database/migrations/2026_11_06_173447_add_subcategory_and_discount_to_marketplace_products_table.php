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
        Schema::table('marketplace_products', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplace_products', 'subcategory_id')) {
                $table->foreignId('subcategory_id')->nullable()->after('category_id')->constrained('marketplace_categories')->nullOnDelete();
            }
            if (! Schema::hasColumn('marketplace_products', 'original_price')) {
                $table->decimal('original_price', 12, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('marketplace_products', 'discount_percent')) {
                $table->integer('discount_percent')->nullable()->after('original_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            if (Schema::hasColumn('marketplace_products', 'subcategory_id')) {
                $table->dropForeign(['subcategory_id']);
                $table->dropColumn('subcategory_id');
            }
            if (Schema::hasColumn('marketplace_products', 'original_price')) {
                $table->dropColumn('original_price');
            }
            if (Schema::hasColumn('marketplace_products', 'discount_percent')) {
                $table->dropColumn('discount_percent');
            }
        });
    }
};
