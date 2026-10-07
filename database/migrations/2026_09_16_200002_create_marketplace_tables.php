<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('marketplace_orders');
        Schema::dropIfExists('marketplace_products');
        Schema::dropIfExists('marketplace_categories');

        Schema::create('marketplace_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('marketplace_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('marketplace_categories')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('BDT');
            $table->string('condition')->default('used');
            $table->string('location')->default('Dhaka, Bangladesh');
            $table->string('status')->default('active');
            $table->json('images')->nullable();
            $table->json('variants')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->float('ai_fraud_score')->default(0.0);
            $table->boolean('is_boosted')->default(false);
            $table->timestamps();

            $table->index(['seller_id', 'status']);
            $table->index(['category_id', 'price']);
            $table->index(['status', 'is_boosted']);

            if (DB::getDriverName() !== 'sqlite') {
                $table->fullText(['title', 'description']);
            } else {
                $table->index('title');
            }
        });

        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('marketplace_products')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->string('escrow_status')->default('held');
            $table->string('payment_method')->default('bkash');
            $table->text('shipping_address')->nullable();
            $table->timestamps();

            $table->index(['buyer_id', 'status']);
            $table->index(['seller_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_orders');
        Schema::dropIfExists('marketplace_products');
        Schema::dropIfExists('marketplace_categories');
    }
};
