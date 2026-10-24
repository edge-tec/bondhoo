<?php

namespace Tests\Feature;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5MarketplaceAndEscrowCommerceTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected User $buyer;

    protected MarketplaceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create(['name' => 'Seller User']);
        $this->buyer = User::factory()->create(['name' => 'Buyer User']);

        $this->category = MarketplaceCategory::create([
            'name' => 'স্মার্টফোন ও গ্যাজেট',
            'slug' => 'gadgets',
            'display_order' => 1,
        ]);
    }

    public function test_categories_and_search_filter_web_and_api(): void
    {
        $service = app(MarketplaceService::class);
        $prod1 = $service->createProduct($this->seller, [
            'category_id' => $this->category->id,
            'title' => 'iPhone 14 Pro Max 256GB',
            'price' => 95000.00,
            'location' => 'ধানমন্ডি, ঢাকা',
        ]);

        $prod2 = $service->createProduct($this->seller, [
            'category_id' => $this->category->id,
            'title' => 'Samsung Galaxy S23 Ultra',
            'price' => 85000.00,
            'location' => 'গুলশান, ঢাকা',
        ]);

        // 1. API v2 Categories
        $catRes = $this->getJson('/api/v2/marketplace/categories');
        $catRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonFragment(['slug' => 'gadgets']);

        // 2. API v1 Categories (dual-version parity)
        $v1CatRes = $this->getJson('/api/v1/marketplace/categories');
        $v1CatRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        // 3. Search by query
        $searchRes = $this->getJson('/api/v2/marketplace/products?q=iPhone');
        $searchRes->assertStatus(200)
            ->assertJsonFragment(['title' => 'iPhone 14 Pro Max 256GB']);

        // 4. Web marketplace index
        $webRes = $this->actingAs($this->buyer)->get('/marketplace?q=Samsung');
        $webRes->assertStatus(200)
            ->assertSee('Samsung Galaxy S23 Ultra');
    }

    public function test_seller_can_create_listing_with_ai_fraud_screening(): void
    {
        $token = $this->seller->createToken('seller-token')->plainTextToken;

        // 1. Valid API Creation
        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v2/marketplace/products', [
                'category_id' => $this->category->id,
                'title' => 'MacBook Pro M2 16GB',
                'description' => 'Original charger included in pristine condition',
                'price' => 140000.00,
                'currency' => 'BDT',
                'condition' => 'used',
                'location' => 'বনানী, ঢাকা',
                'images' => ['https://images.unsplash.com/photo-macbook.jpg'],
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'MacBook Pro M2 16GB');

        $productId = $res->json('data.id');
        $product = MarketplaceProduct::findOrFail($productId);
        $this->assertSame($this->seller->id, $product->seller_id);
        $this->assertGreaterThanOrEqual(0.0, $product->ai_fraud_score);

        // 2. Web creation fallback
        $webRes = $this->actingAs($this->seller)->post('/marketplace/products', [
            'category_id' => $this->category->id,
            'title' => 'Sony WH-1000XM5 Headphones',
            'description' => 'Noise cancellation top notch',
            'price' => 32000.00,
            'condition' => 'new',
            'location' => 'উত্তরা, ঢাকা',
            'image_url' => 'https://images.unsplash.com/photo-headphones.jpg',
        ]);

        $webRes->assertRedirect();
        $this->assertDatabaseHas('marketplace_products', [
            'title' => 'Sony WH-1000XM5 Headphones',
            'seller_id' => $this->seller->id,
        ]);
    }

    public function test_buyer_can_place_order_with_shipping_and_escrow_held(): void
    {
        $service = app(MarketplaceService::class);
        $product = $service->createProduct($this->seller, [
            'category_id' => $this->category->id,
            'title' => 'iPad Air 5th Gen M1',
            'price' => 65000.00,
        ]);

        $token = $this->buyer->createToken('buyer-token')->plainTextToken;

        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v2/marketplace/products/{$product->id}/order", [
                'shipping_address' => 'বাড়ি #১২, রোড #৪, ধানমন্ডি, ঢাকা — ০১৭১১২২৩৩৪৪',
                'payment_method' => 'bkash',
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.amount', '65000.00')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.escrow_status', 'held')
            ->assertJsonPath('data.payment_method', 'bkash');

        $orderId = $res->json('data.id');
        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $orderId,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'escrow_status' => 'held',
        ]);
    }

    public function test_checkout_session_and_payment_callback_verification(): void
    {
        $service = app(MarketplaceService::class);
        $product = $service->createProduct($this->seller, [
            'category_id' => $this->category->id,
            'title' => 'Apple Watch Ultra',
            'price' => 78000.00,
        ]);

        $order = $service->placeOrder($this->buyer, $product, 'মীরপুর-১০, ঢাকা', 'nagad');

        $token = $this->buyer->createToken('buyer-token')->plainTextToken;

        // 1. Checkout session
        $checkoutRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/checkout", [
                'gateway' => 'nagad',
            ]);

        $checkoutRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.gateway', 'nagad')
            ->assertJsonPath('data.order_id', $order->id);

        $this->assertNotEmpty($checkoutRes->json('data.checkout_url'));
        $trxId = $checkoutRes->json('data.transaction_id');

        // 2. Payment callback
        $verifyRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/verify-payment", [
                'transaction_id' => $trxId,
                'payment_status' => 'COMPLETED',
            ]);

        $verifyRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.escrow_status', 'held');

        $this->assertSame('processing', $order->fresh()->status);
        $this->assertSame('held', $order->fresh()->escrow_status);
    }

    public function test_buyer_can_release_escrow_and_unauthorized_user_is_forbidden(): void
    {
        $service = app(MarketplaceService::class);
        $product = $service->createProduct($this->seller, [
            'category_id' => $this->category->id,
            'title' => 'DJI Mini 3 Pro Drone',
            'price' => 88000.00,
        ]);

        $order = $service->placeOrder($this->buyer, $product, 'উত্তরা সেক্টর ৭', 'bkash');
        $order->update(['status' => 'delivered']);

        $attacker = User::factory()->create(['name' => 'Attacker']);
        $attackerToken = $attacker->createToken('attacker')->plainTextToken;

        // 1. Unauthorized attacker cannot release escrow
        $failRes = $this->withHeader('Authorization', 'Bearer '.$attackerToken)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/release-escrow");
        $failRes->assertStatus(404); // Scoped to buyer_id

        // 2. Legitimate buyer releases escrow via API
        $this->flushHeaders();
        auth()->forgetGuards();
        $buyerToken = $this->buyer->createToken('buyer')->plainTextToken;
        $successRes = $this->withHeader('Authorization', 'Bearer '.$buyerToken)
            ->postJson("/api/v2/marketplace/orders/{$order->id}/release-escrow");

        $successRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.escrow_status', 'released')
            ->assertJsonPath('data.status', 'delivered');

        // 3. Web release escrow endpoint
        $order2 = $service->placeOrder($this->buyer, $product, 'গুলশান', 'bkash');
        $webReleaseRes = $this->actingAs($this->buyer)
            ->post("/marketplace/orders/{$order2->id}/release-escrow");

        $webReleaseRes->assertRedirect();
        $this->assertSame('released', $order2->fresh()->escrow_status);
    }

    public function test_seller_can_manage_fulfillment_and_my_listings(): void
    {
        $service = app(MarketplaceService::class);
        $product = $service->createProduct($this->seller, [
            'category_id' => $this->category->id,
            'title' => 'Canon EOS R6 Mark II',
            'price' => 220000.00,
        ]);

        $order = $service->placeOrder($this->buyer, $product, 'বনানী', 'bkash');

        $sellerToken = $this->seller->createToken('seller')->plainTextToken;

        // 1. Seller lists orders
        $ordersRes = $this->withHeader('Authorization', 'Bearer '.$sellerToken)
            ->getJson('/api/v2/marketplace/orders?role=seller');
        $ordersRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        // 2. Seller updates status to shipped
        $statusRes = $this->withHeader('Authorization', 'Bearer '.$sellerToken)
            ->patchJson("/api/v2/marketplace/orders/{$order->id}/status", [
                'status' => 'shipped',
            ]);

        $statusRes->assertStatus(200)
            ->assertJsonPath('data.status', 'shipped');

        // 3. Seller toggles listing status to sold
        $listingStatusRes = $this->withHeader('Authorization', 'Bearer '.$sellerToken)
            ->patchJson("/api/v2/marketplace/products/{$product->id}/status", [
                'status' => 'sold',
            ]);

        $listingStatusRes->assertStatus(200)
            ->assertJsonPath('data.status', 'sold');

        // 4. Seller deletes listing
        $deleteRes = $this->withHeader('Authorization', 'Bearer '.$sellerToken)
            ->deleteJson("/api/v2/marketplace/products/{$product->id}");

        $deleteRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('marketplace_products', [
            'id' => $product->id,
        ]);
    }

    public function test_marketplace_web_views_render_successfully(): void
    {
        $service = app(MarketplaceService::class);
        $product = $service->createProduct($this->seller, [
            'category_id' => $this->category->id,
            'title' => 'Kindle Paperwhite 11th Gen',
            'price' => 15500.00,
            'location' => 'ঢাকা',
        ]);

        $order = $service->placeOrder($this->buyer, $product, 'মোহাম্মদপুর, ঢাকা', 'bkash');

        // 1. Marketplace index view
        $resIndex = $this->actingAs($this->buyer)->get('/marketplace');
        $resIndex->assertStatus(200)
            ->assertSee('মার্কেটপ্লেস ও কেনাবেচা')
            ->assertSee('Kindle Paperwhite');

        // 2. Product show view
        $resShow = $this->actingAs($this->buyer)->get("/marketplace/{$product->id}");
        $resShow->assertStatus(200)
            ->assertSee('Kindle Paperwhite')
            ->assertSee('এসক্রো সুরক্ষিত');

        // 3. Orders list view (Purchases & Sales)
        $resOrders = $this->actingAs($this->buyer)->get('/marketplace/orders?tab=purchases');
        $resOrders->assertStatus(200)
            ->assertSee('অর্ডার ও এসক্রো ট্র্যাকার')
            ->assertSee('Kindle Paperwhite');

        $resSales = $this->actingAs($this->seller)->get('/marketplace/orders?tab=sales');
        $resSales->assertStatus(200)
            ->assertSee('অর্ডার ও এসক্রো ট্র্যাকার');

        // 4. Order invoice details view
        $resInvoice = $this->actingAs($this->buyer)->get("/marketplace/orders/{$order->id}");
        $resInvoice->assertStatus(200)
            ->assertSee("অর্ডার ইনভয়েস #{$order->id}")
            ->assertSee('পেমেন্ট ও এসক্রো সিকিউরড');

        // 5. Seller my listings view
        $resMyListings = $this->actingAs($this->seller)->get('/marketplace/my-listings');
        $resMyListings->assertStatus(200)
            ->assertSee('আমার মার্কেটপ্লেস বিজ্ঞাপন');
    }
}
