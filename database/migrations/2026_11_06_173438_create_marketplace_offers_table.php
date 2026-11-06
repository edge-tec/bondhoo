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
        if (! Schema::hasTable('marketplace_offers')) {
            Schema::create('marketplace_offers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('marketplace_products')->cascadeOnDelete();
                $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('offer_amount', 12, 2);
                $table->decimal('original_price', 12, 2);
                $table->decimal('counter_amount', 12, 2)->nullable();
                $table->string('currency', 10)->default('BDT');
                $table->string('status', 30)->default('pending')->index(); // pending, accepted, rejected, countered, cancelled, expired
                $table->text('message')->nullable();
                $table->text('counter_message')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamp('countered_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->index(['product_id', 'buyer_id']);
                $table->index(['seller_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_offers');
    }
};
