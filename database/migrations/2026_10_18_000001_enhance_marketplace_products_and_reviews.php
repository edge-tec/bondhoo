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
            if (! Schema::hasColumn('marketplace_products', 'brand')) {
                $table->string('brand', 100)->nullable()->after('condition');
            }
            if (! Schema::hasColumn('marketplace_products', 'model')) {
                $table->string('model', 100)->nullable()->after('brand');
            }
            if (! Schema::hasColumn('marketplace_products', 'is_negotiable')) {
                $table->boolean('is_negotiable')->default(false)->after('model');
            }
            if (! Schema::hasColumn('marketplace_products', 'delivery_type')) {
                $table->string('delivery_type', 30)->default('pickup')->after('is_negotiable'); // pickup, delivery, both
            }
            if (! Schema::hasColumn('marketplace_products', 'delivery_charge')) {
                $table->decimal('delivery_charge', 10, 2)->default(0.00)->after('delivery_type');
            }
        });

        if (! Schema::hasTable('marketplace_reviews')) {
            Schema::create('marketplace_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->nullable()->constrained('marketplace_orders')->nullOnDelete();
                $table->foreignId('product_id')->constrained('marketplace_products')->cascadeOnDelete();
                $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating')->default(5); // 1 to 5
                $table->text('comment')->nullable();
                $table->timestamps();

                $table->index(['seller_id', 'rating']);
                $table->index(['product_id', 'rating']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_reviews');

        Schema::table('marketplace_products', function (Blueprint $table) {
            $columns = ['brand', 'model', 'is_negotiable', 'delivery_type', 'delivery_charge'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('marketplace_products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
