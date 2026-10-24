<?php

namespace Tests\Feature;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\User;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterpriseMarketplacePlatformTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected User $buyer;

    protected MarketplaceCategory $electronicsCat;

    protected MarketplaceCategory $fashionCat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create([
            'name' => 'Rahim Ahmed',
            'username' => 'rahim_seller',
            'email' => 'rahim@test.com',
        ]);

        $this->buyer = User::factory()->create([
            'name' => 'Sadia Buyer',
            'username' => 'sadia_buyer',
            'email' => 'sadia@test.com',
        ]);

        $this->electronicsCat = MarketplaceCategory::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'icon' => 'smartphone',
            'display_order' => 1,
        ]);

        $this->fashionCat = MarketplaceCategory::create([
            'name' => 'Fashion',
            'slug' => 'fashion',
            'icon' => 'shirt',
            'display_order' => 2,
        ]);
    }

    public function test_marketplace_index_renders_with_categories_and_featured_listings(): void
    {
        $service = app(MarketplaceService::class);

        $macbook = $service->createProduct($this->seller, [
            'category_id' => $this->electronicsCat->id,
            'title' => 'MacBook Air M2 (2022)',
            'description' => 'Original box and MagSafe cable included.',
            'price' => 850.00,
            'currency' => 'USD',
            'condition' => 'used',
            'brand' => 'Apple',
            'model' => 'MacBook Air M2 256GB',
            'is_negotiable' => true,
            'location' => 'Dhaka, Bangladesh',
            'images' => ['https://images.unsplash.com/photo-macbook.jpg'],
            'is_boosted' => true,
        ]);

        $iphone = $service->createProduct($this->seller, [
            'category_id' => $this->electronicsCat->id,
            'title' => 'iPhone 14 Pro 128GB',
            'price' => 700.00,
            'currency' => 'USD',
            'condition' => 'used',
            'location' => 'Dhaka, Bangladesh',
            'images' => ['https://images.unsplash.com/photo-iphone.jpg'],
            'is_boosted' => true,
        ]);

        // 1. Web Index
        $webRes = $this->actingAs($this->buyer)->get('/marketplace');
        $webRes->assertStatus(200)
            ->assertSee('মার্কেটপ্লেস ও কেনাবেচা')
            ->assertSee('MacBook Air M2')
            ->assertSee('iPhone 14 Pro');

        // 2. API Featured Listings
        $featuredRes = $this->getJson('/api/v1/marketplace/featured');
        $featuredRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonFragment(['title' => 'MacBook Air M2 (2022)']);

        // 3. API Categories with Counts
        $catRes = $this->getJson('/api/v1/marketplace/categories');
        $catRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonFragment(['name' => 'Electronics']);
    }

    public function test_marketplace_filtering_by_category_price_and_condition(): void
    {
        $service = app(MarketplaceService::class);

        $headphone = $service->createProduct($this->seller, [
            'category_id' => $this->electronicsCat->id,
            'title' => 'Sony WH-1000XM5 Headphones',
            'price' => 280.00,
            'condition' => 'used',
            'location' => 'Dhaka, Bangladesh',
        ]);

        $nikeShoes = $service->createProduct($this->seller, [
            'category_id' => $this->fashionCat->id,
            'title' => 'Nike Air Force 1 (Original)',
            'price' => 100.00,
            'condition' => 'new',
            'location' => 'Dhaka, Bangladesh',
        ]);

        // Filter by Category = fashion
        $fashionRes = $this->getJson('/api/v1/marketplace/products?category=fashion');
        $fashionRes->assertStatus(200)
            ->assertJsonFragment(['title' => 'Nike Air Force 1 (Original)'])
            ->assertJsonMissing(['title' => 'Sony WH-1000XM5 Headphones']);

        // Filter by Price Range: min=200, max=300
        $priceRes = $this->getJson('/api/v1/marketplace/products?min_price=200&max_price=300');
        $priceRes->assertStatus(200)
            ->assertJsonFragment(['title' => 'Sony WH-1000XM5 Headphones'])
            ->assertJsonMissing(['title' => 'Nike Air Force 1 (Original)']);

        // Filter by Condition = new
        $condRes = $this->getJson('/api/v1/marketplace/products?condition=new');
        $condRes->assertStatus(200)
            ->assertJsonFragment(['title' => 'Nike Air Force 1 (Original)'])
            ->assertJsonMissing(['title' => 'Sony WH-1000XM5 Headphones']);
    }

    public function test_buyer_can_toggle_favorite_listing(): void
    {
        $service = app(MarketplaceService::class);
        $product = $service->createProduct($this->seller, [
            'category_id' => $this->electronicsCat->id,
            'title' => 'Wooden Study Chair',
            'price' => 45.00,
        ]);

        $token = $this->buyer->createToken('buyer-token')->plainTextToken;

        // Toggle Favorite ON
        $favRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/marketplace/products/{$product->id}/favorite");

        $favRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_favorited', true);

        $this->assertDatabaseHas('saved_items', [
            'user_id' => $this->buyer->id,
            'item_id' => $product->id,
            'item_type' => 'marketplace_product',
        ]);

        // Toggle Favorite OFF
        $unfavRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/marketplace/products/{$product->id}/favorite");

        $unfavRes->assertStatus(200)
            ->assertJsonPath('is_favorited', false);

        $this->assertDatabaseMissing('saved_items', [
            'user_id' => $this->buyer->id,
            'item_id' => $product->id,
            'item_type' => 'marketplace_product',
        ]);
    }

    public function test_buyer_can_contact_seller_directly(): void
    {
        $service = app(MarketplaceService::class);
        $product = $service->createProduct($this->seller, [
            'category_id' => $this->electronicsCat->id,
            'title' => 'Mountain Bike (21 Speed)',
            'price' => 150.00,
        ]);

        $token = $this->buyer->createToken('buyer-token')->plainTextToken;

        $contactRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/marketplace/products/{$product->id}/contact", [
                'message' => 'Hello Rahim, is the mountain bike available for inspection today?',
            ]);

        $contactRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'conversation_id',
                    'seller_id',
                    'message_sent',
                ],
            ]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->buyer->id,
            'body' => 'Hello Rahim, is the mountain bike available for inspection today?',
        ]);
    }

    public function test_seller_can_create_update_and_delete_listing(): void
    {
        $token = $this->seller->createToken('seller-token')->plainTextToken;

        // 1. Create Listing
        $createRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/marketplace/products', [
                'category_id' => $this->electronicsCat->id,
                'title' => 'Canon EOS 200D DSLR Camera',
                'description' => 'Includes 18-55mm lens and 64GB card',
                'price' => 450.00,
                'currency' => 'USD',
                'condition' => 'used',
                'brand' => 'Canon',
                'model' => 'EOS 200D',
                'is_negotiable' => true,
                'delivery_type' => 'both',
                'delivery_charge' => 5.00,
                'location' => 'Dhaka, Bangladesh',
                'images' => ['https://images.unsplash.com/photo-canon.jpg'],
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Canon EOS 200D DSLR Camera');

        $productId = $createRes->json('data.id');

        // 2. Update Status to sold
        $statusRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/marketplace/products/{$productId}/status", [
                'status' => 'sold',
            ]);

        $statusRes->assertStatus(200)
            ->assertJsonPath('data.status', 'sold');

        // 3. Delete Listing
        $deleteRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/marketplace/products/{$productId}");

        $deleteRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('marketplace_products', [
            'id' => $productId,
        ]);
    }

    public function test_product_detail_view_and_reviews(): void
    {
        $service = app(MarketplaceService::class);
        $product = $service->createProduct($this->seller, [
            'category_id' => $this->electronicsCat->id,
            'title' => 'Sofa Set (5 Seater)',
            'price' => 350.00,
            'description' => 'Comfortable L-shaped sofa in grey linen.',
        ]);

        // Detail Web View
        $webRes = $this->actingAs($this->buyer)->get("/marketplace/{$product->id}");
        $webRes->assertStatus(200)
            ->assertSee('Sofa Set')
            ->assertSee('এসক্রো সুরক্ষিত');

        // Leave Review
        $review = $service->leaveReview($this->buyer, $product, 5, 'Great seller and prompt delivery!');
        $this->assertDatabaseHas('marketplace_reviews', [
            'id' => $review->id,
            'product_id' => $product->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'rating' => 5,
        ]);

        // API Show with reviews & seller rating
        $apiRes = $this->getJson("/api/v1/marketplace/products/{$product->id}");
        $apiRes->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.seller.reviews_count', 1);
    }

    public function test_media_upload_endpoint_stores_images(): void
    {
        Storage::fake('public');
        $token = $this->seller->createToken('seller-token')->plainTextToken;

        $file = UploadedFile::fake()->image('item.jpg', 600, 600);

        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/marketplace/upload-media', [
                'image' => $file,
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['url', 'path']);

        $path = $res->json('path');
        Storage::disk('public')->assertExists($path);
    }

    public function test_user_can_report_listing(): void
    {
        $service = app(MarketplaceService::class);
        $product = $service->createProduct($this->seller, [
            'category_id' => $this->electronicsCat->id,
            'title' => 'Suspicious Drone',
            'price' => 10.00,
        ]);

        $token = $this->buyer->createToken('buyer-token')->plainTextToken;

        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/marketplace/products/{$product->id}/report", [
                'reason' => 'scam',
                'details' => 'Price is abnormally low and seller asks for untraceable payment.',
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('reports', [
            'reportable_id' => $product->id,
            'reportable_type' => MarketplaceProduct::class,
            'reporter_id' => $this->buyer->id,
            'reason' => 'scam',
        ]);
    }
}
