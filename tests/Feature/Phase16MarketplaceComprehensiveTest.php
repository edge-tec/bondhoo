<?php

namespace Tests\Feature;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceOffer;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceReview;
use App\Models\Report;
use App\Models\SavedItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase16MarketplaceComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected User $buyer;
    protected User $intruder;
    protected MarketplaceCategory $topCategory;
    protected MarketplaceCategory $subcategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create(['name' => 'Verified Seller', 'username' => 'seller_user']);
        $this->buyer = User::factory()->create(['name' => 'Verified Buyer', 'username' => 'buyer_user']);
        $this->intruder = User::factory()->create(['name' => 'Unauthorized Intruder', 'username' => 'intruder_user']);

        $this->topCategory = MarketplaceCategory::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'icon' => 'smartphone',
            'display_order' => 1,
            'is_active' => true,
        ]);

        $this->subcategory = MarketplaceCategory::create([
            'parent_id' => $this->topCategory->id,
            'name' => 'Smartphones',
            'slug' => 'electronics-smartphones',
            'icon' => 'smartphone',
            'display_order' => 1,
            'is_active' => true,
        ]);
    }

    public function test_categories_api_returns_top_level_categories_with_subcategories(): void
    {
        $response = $this->getJson('/api/v1/marketplace/categories');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $electronics = collect($data)->firstWhere('slug', 'electronics');
        $this->assertNotNull($electronics);
        $this->assertNotEmpty($electronics['subcategories']);
        $this->assertEquals('Smartphones', $electronics['subcategories'][0]['name']);
    }

    public function test_create_listing_with_subcategory_original_price_and_discount(): void
    {
        $payload = [
            'category_id' => $this->topCategory->id,
            'subcategory_id' => $this->subcategory->id,
            'title' => 'iPhone 15 Pro Max 256GB',
            'description' => 'Brand new in box, official Apple warranty included.',
            'price' => 135000.00,
            'original_price' => 150000.00,
            'condition' => 'new',
            'brand' => 'Apple',
            'model' => 'iPhone 15 Pro Max',
            'is_negotiable' => true,
            'delivery_type' => 'delivery',
            'delivery_charge' => 150.00,
            'location' => 'Gulshan, Dhaka',
            'images' => ['https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=800'],
        ];

        $response = $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/v1/marketplace/products', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'iPhone 15 Pro Max 256GB')
            ->assertJsonPath('data.price', 135000)
            ->assertJsonPath('data.original_price', 150000)
            ->assertJsonPath('data.discount_percent', 10)
            ->assertJsonPath('data.subcategory.name', 'Smartphones');

        $this->assertDatabaseHas('marketplace_products', [
            'seller_id' => $this->seller->id,
            'title' => 'iPhone 15 Pro Max 256GB',
            'subcategory_id' => $this->subcategory->id,
        ]);
    }

    public function test_advanced_search_and_filtering_across_multiple_criteria(): void
    {
        $prod1 = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->topCategory->id,
            'subcategory_id' => $this->subcategory->id,
            'title' => 'Samsung Galaxy S24 Ultra',
            'description' => '512GB Titanium Grey with S-Pen',
            'price' => 120000.00,
            'currency' => 'BDT',
            'condition' => 'used',
            'location' => 'Dhanmondi, Dhaka',
            'brand' => 'Samsung',
            'status' => 'active',
            'views_count' => 500,
        ]);

        $prod2 = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->topCategory->id,
            'subcategory_id' => $this->subcategory->id,
            'title' => 'Google Pixel 8 Pro',
            'description' => 'Obsidian black, mint condition',
            'price' => 75000.00,
            'currency' => 'BDT',
            'condition' => 'used',
            'location' => 'Uttara, Dhaka',
            'brand' => 'Google',
            'status' => 'active',
            'views_count' => 120,
        ]);

        // Search by query keyword
        $searchRes = $this->getJson('/api/v1/marketplace/products?q=Samsung');
        $searchRes->assertStatus(200);
        $this->assertCount(1, $searchRes->json('data'));
        $this->assertEquals('Samsung Galaxy S24 Ultra', $searchRes->json('data.0.title'));

        // Filter by price range
        $priceRes = $this->getJson('/api/v1/marketplace/products?min_price=70000&max_price=80000');
        $priceRes->assertStatus(200);
        $this->assertCount(1, $priceRes->json('data'));
        $this->assertEquals('Google Pixel 8 Pro', $priceRes->json('data.0.title'));

        // Sort by price ascending
        $sortLow = $this->getJson('/api/v1/marketplace/products?sort=price_low');
        $sortLow->assertStatus(200);
        $this->assertEquals('Google Pixel 8 Pro', $sortLow->json('data.0.title'));

        // Sort by popularity (views_count)
        $sortPop = $this->getJson('/api/v1/marketplace/products?sort=popular');
        $sortPop->assertStatus(200);
        $this->assertEquals('Samsung Galaxy S24 Ultra', $sortPop->json('data.0.title'));
    }

    public function test_offer_lifecycle_submit_counter_and_accept(): void
    {
        $product = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->topCategory->id,
            'title' => 'Sony PlayStation 5 Disc Edition',
            'description' => 'Original box and accessories included.',
            'price' => 58000.00,
            'currency' => 'BDT',
            'condition' => 'used',
            'status' => 'active',
        ]);

        // 1. Seller cannot make offer on own listing
        $selfOfferRes = $this->actingAs($this->seller, 'sanctum')
            ->postJson("/api/v1/marketplace/products/{$product->id}/offers", [
                'offer_amount' => 50000.00,
            ]);
        $selfOfferRes->assertStatus(422);

        // 2. Buyer submits valid offer
        $buyerOfferRes = $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/marketplace/products/{$product->id}/offers", [
                'offer_amount' => 52000.00,
                'message' => 'Can pick up tomorrow cash in hand.',
            ]);
        $buyerOfferRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.offer_amount', 52000)
            ->assertJsonPath('data.status', 'pending');

        $offerId = $buyerOfferRes->json('data.id');

        // 3. Buyer sees their submitted offer
        $myOffersRes = $this->actingAs($this->buyer, 'sanctum')
            ->getJson('/api/v1/marketplace/offers/my-offers');
        $myOffersRes->assertStatus(200)
            ->assertJsonPath('data.0.id', $offerId);

        // 4. Seller sees received offer
        $receivedRes = $this->actingAs($this->seller, 'sanctum')
            ->getJson('/api/v1/marketplace/offers/received');
        $receivedRes->assertStatus(200)
            ->assertJsonPath('data.0.id', $offerId);

        // 5. Intruder cannot counter or accept offer
        $intruderRes = $this->actingAs($this->intruder, 'sanctum')
            ->postJson("/api/v1/marketplace/offers/{$offerId}/accept");
        $intruderRes->assertStatus(403);

        // 6. Seller counters offer
        $counterRes = $this->actingAs($this->seller, 'sanctum')
            ->postJson("/api/v1/marketplace/offers/{$offerId}/counter", [
                'counter_amount' => 55000.00,
                'counter_message' => 'I can do 55,000 final price.',
            ]);
        $counterRes->assertStatus(200)
            ->assertJsonPath('data.status', 'countered')
            ->assertJsonPath('data.counter_amount', 55000);

        // 7. Seller accepts offer
        $acceptRes = $this->actingAs($this->seller, 'sanctum')
            ->postJson("/api/v1/marketplace/offers/{$offerId}/accept");
        $acceptRes->assertStatus(200)
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('marketplace_offers', [
            'id' => $offerId,
            'status' => 'accepted',
        ]);
    }

    public function test_buyer_can_cancel_their_own_offer(): void
    {
        $product = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->topCategory->id,
            'title' => 'MacBook Pro M3 Max',
            'description' => 'Space Black 36GB RAM 1TB SSD.',
            'price' => 320000.00,
            'currency' => 'BDT',
            'condition' => 'used',
            'status' => 'active',
        ]);

        $offer = MarketplaceOffer::create([
            'product_id' => $product->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'offer_amount' => 300000.00,
            'original_price' => 320000.00,
            'status' => 'pending',
        ]);

        // Intruder cannot cancel buyer's offer
        $intruderRes = $this->actingAs($this->intruder, 'sanctum')
            ->postJson("/api/v1/marketplace/offers/{$offer->id}/cancel");
        $intruderRes->assertStatus(403);

        // Buyer cancels their offer
        $cancelRes = $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/marketplace/offers/{$offer->id}/cancel");
        $cancelRes->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('marketplace_offers', [
            'id' => $offer->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_seller_and_buyer_dashboard_metrics(): void
    {
        $product = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->topCategory->id,
            'title' => 'Mechanical Keyboard RGB',
            'description' => 'Custom mechanical keyboard with lubed red switches.',
            'price' => 4500.00,
            'currency' => 'BDT',
            'condition' => 'new',
            'status' => 'active',
        ]);

        MarketplaceOrder::create([
            'order_number' => 'ORD-TEST-001',
            'product_id' => $product->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'amount' => 4500.00,
            'status' => 'completed',
            'payment_status' => 'paid',
            'escrow_status' => 'released',
            'shipping_name' => 'Verified Buyer',
            'shipping_address' => 'Mirpur, Dhaka',
            'shipping_phone' => '01711223344',
        ]);

        // Test seller dashboard
        $sellerDash = $this->actingAs($this->seller, 'sanctum')
            ->getJson('/api/v1/marketplace/seller/dashboard');
        $sellerDash->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_listings', 1)
            ->assertJsonPath('data.active_listings', 1)
            ->assertJsonPath('data.total_orders', 1)
            ->assertJsonPath('data.completed_orders', 1);

        // Test buyer dashboard
        $buyerDash = $this->actingAs($this->buyer, 'sanctum')
            ->getJson('/api/v1/marketplace/buyer/dashboard');
        $buyerDash->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_orders', 1)
            ->assertJsonPath('data.completed_orders', 1);
    }

    public function test_report_fraudulent_listing(): void
    {
        $product = MarketplaceProduct::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->topCategory->id,
            'title' => 'Suspicious Listing Lot',
            'description' => 'Suspicious item description.',
            'price' => 100.00,
            'currency' => 'BDT',
            'condition' => 'used',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->buyer, 'sanctum')
            ->postJson("/api/v1/marketplace/products/{$product->id}/report", [
                'reason' => 'scam',
                'details' => 'Asks for advance payment outside BONDHOO.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $this->buyer->id,
            'reportable_id' => $product->id,
            'reason' => 'scam',
        ]);
    }
}
