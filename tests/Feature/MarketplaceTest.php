<?php

namespace Tests\Feature;

use App\Models\MarketplaceCategory;
use App\Models\User;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_product_and_system_calculates_fraud_score(): void
    {
        $seller = User::factory()->create();
        $cat = MarketplaceCategory::create(['name' => 'Electronics', 'slug' => 'electronics']);

        $service = app(MarketplaceService::class);
        $product = $service->createProduct($seller, [
            'category_id' => $cat->id,
            'title' => 'iPhone 15 Pro Max',
            'description' => 'Original box and accessories included',
            'price' => 125000.00,
            'condition' => 'used_like_new',
            'location' => 'Gulshan, Dhaka',
        ]);

        $this->assertDatabaseHas('marketplace_products', [
            'id' => $product->id,
            'seller_id' => $seller->id,
            'title' => 'iPhone 15 Pro Max',
            'status' => 'active',
        ]);
        $this->assertGreaterThanOrEqual(0.0, $product->ai_fraud_score);
    }

    public function test_buyer_can_place_order_with_escrow_held(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $cat = MarketplaceCategory::create(['name' => 'Books', 'slug' => 'books']);

        $service = app(MarketplaceService::class);
        $product = $service->createProduct($seller, [
            'category_id' => $cat->id,
            'title' => 'Clean Architecture in PHP',
            'price' => 1200.00,
        ]);

        $order = $service->placeOrder($buyer, $product, 'Dhanmondi, Dhaka', 'bkash');

        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $order->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'status' => 'pending',
            'escrow_status' => 'held',
        ]);

        // Release escrow
        $service->releaseEscrow($order);
        $this->assertSame('released', $order->fresh()->escrow_status);
        $this->assertSame('delivered', $order->fresh()->status);
    }

    public function test_buyer_can_initiate_checkout_and_verify_payment(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $cat = MarketplaceCategory::create(['name' => 'Fashion', 'slug' => 'fashion']);

        $service = app(MarketplaceService::class);
        $product = $service->createProduct($seller, [
            'category_id' => $cat->id,
            'title' => 'Panjabi Cotton',
            'price' => 2500.00,
        ]);

        $order = $service->placeOrder($buyer, $product, 'Banani, Dhaka', 'bkash');

        $token = $buyer->createToken('test')->plainTextToken;

        // 1. Initiate Checkout
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/checkout", [
                'gateway' => 'bkash',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.gateway', 'bkash');

        $this->assertNotEmpty($response->json('data.checkout_url'));
        $trxId = $response->json('data.transaction_id');

        // 2. Verify Payment Callback
        $verifyRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/verify-payment", [
                'transaction_id' => $trxId,
                'payment_status' => 'COMPLETED',
            ]);

        $verifyRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.escrow_status', 'held');
    }
}
