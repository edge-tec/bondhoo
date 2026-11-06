<?php

namespace Tests\Feature;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceOffer;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase16MarketplaceDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;

    protected User $buyer;

    protected User $intruder;

    protected User $admin;

    protected MarketplaceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create(['name' => 'Seller User', 'username' => 'seller_user']);
        $this->buyer = User::factory()->create(['name' => 'Buyer User', 'username' => 'buyer_user']);
        $this->intruder = User::factory()->create(['name' => 'Intruder User', 'username' => 'intruder_user']);
        $this->admin = User::factory()->create(['name' => 'Admin User', 'username' => 'admin_user']);
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Administrator']);
        $this->admin->roles()->attach($adminRole->id);

        $this->category = MarketplaceCategory::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'icon' => 'laptop',
            'order' => 1,
            'is_active' => true,
        ]);
    }

    /**
     * Helper to create a listing.
     */
    protected function createListing(User $owner, array $attributes = []): MarketplaceProduct
    {
        return MarketplaceProduct::create(array_merge([
            'seller_id' => $owner->id,
            'category_id' => $this->category->id,
            'title' => 'iPhone 15 Pro Max',
            'slug' => 'iphone-15-pro-max-'.uniqid(),
            'price' => 120000.00,
            'currency' => 'BDT',
            'condition' => 'used',
            'status' => 'active',
            'description' => 'Mint condition phone with box and cable.',
            'location' => 'Dhaka, Bangladesh',
        ], $attributes));
    }

    /**
     * 1. Test Seller can delete their own listing (HTTP 200, soft deleted, cancelled offers).
     */
    public function test_owner_can_successfully_delete_own_listing(): void
    {
        $listing = $this->createListing($this->seller);

        // Create an active offer on this product
        $offer = MarketplaceOffer::create([
            'product_id' => $listing->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'offer_amount' => 110000.00,
            'original_price' => 120000.00,
            'currency' => 'BDT',
            'status' => MarketplaceOffer::STATUS_PENDING,
            'message' => 'Can you do 110k?',
        ]);

        $response = $this->actingAs($this->seller, 'sanctum')
            ->deleteJson("/api/v1/marketplace/products/{$listing->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'success' => true,
                'data' => null,
            ]);

        // Product is soft deleted
        $this->assertSoftDeleted('marketplace_products', ['id' => $listing->id]);

        // Offer is automatically cancelled
        $this->assertDatabaseHas('marketplace_offers', [
            'id' => $offer->id,
            'status' => MarketplaceOffer::STATUS_CANCELLED,
            'counter_message' => 'Listing was removed by seller.',
        ]);
    }

    /**
     * 2. Test Idempotency: Repeated deletion of already deleted listing returns 200 OK.
     */
    public function test_repeated_deletion_is_idempotent_and_returns_200(): void
    {
        $listing = $this->createListing($this->seller);

        // First deletion
        $this->actingAs($this->seller, 'sanctum')
            ->deleteJson("/api/v1/marketplace/products/{$listing->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('marketplace_products', ['id' => $listing->id]);

        // Second deletion (idempotent repeated call)
        $secondResponse = $this->actingAs($this->seller, 'sanctum')
            ->deleteJson("/api/v1/marketplace/products/{$listing->id}");

        $secondResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'success' => true,
                'message' => 'Listing already removed.',
                'data' => null,
            ]);
    }

    /**
     * 3. Test Nonexistent listing deletion returns 200 idempotent response with data: null.
     */
    public function test_nonexistent_listing_deletion_returns_idempotent_success(): void
    {
        $response = $this->actingAs($this->seller, 'sanctum')
            ->deleteJson('/api/v1/marketplace/products/9999999');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'success' => true,
                'message' => 'Listing already removed.',
                'data' => null,
            ]);
    }

    /**
     * 4. Test Unauthorized intruder cannot delete another user's listing (403 Forbidden).
     */
    public function test_intruder_cannot_delete_listing_forbidden(): void
    {
        $listing = $this->createListing($this->seller);

        $response = $this->actingAs($this->intruder, 'sanctum')
            ->deleteJson("/api/v1/marketplace/products/{$listing->id}");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'success' => false,
            ]);

        // Listing remains active
        $this->assertDatabaseHas('marketplace_products', [
            'id' => $listing->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * 5. Test Unauthenticated request fails with 401 Unauthorized.
     */
    public function test_unauthenticated_request_fails_with_401(): void
    {
        $listing = $this->createListing($this->seller);

        $response = $this->deleteJson("/api/v1/marketplace/products/{$listing->id}");
        $response->assertStatus(401);

        $this->assertDatabaseHas('marketplace_products', [
            'id' => $listing->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * 6. Test Admin can delete any listing.
     */
    public function test_admin_can_delete_any_listing(): void
    {
        $listing = $this->createListing($this->seller);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/marketplace/products/{$listing->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'success' => true,
            ]);

        $this->assertSoftDeleted('marketplace_products', ['id' => $listing->id]);
    }

    /**
     * 7. Test Historical orders and financial records are preserved upon listing deletion.
     */
    public function test_historical_orders_are_preserved_when_listing_is_deleted(): void
    {
        $listing = $this->createListing($this->seller);

        // Create an existing completed order referencing this product
        $order = MarketplaceOrder::create([
            'order_number' => 'ORD-'.strtoupper(uniqid()),
            'product_id' => $listing->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'unit_price' => 120000.00,
            'total_amount' => 120000.00,
            'subtotal' => 120000.00,
            'escrow_amount' => 120000.00,
            'currency' => 'BDT',
            'status' => MarketplaceOrder::STATUS_COMPLETED,
            'payment_status' => MarketplaceOrder::PAYMENT_PAID,
            'escrow_status' => MarketplaceOrder::ESCROW_RELEASED,
            'delivery_address' => 'Mirpur, Dhaka',
        ]);

        // Seller deletes listing
        $response = $this->actingAs($this->seller, 'sanctum')
            ->deleteJson("/api/v1/marketplace/products/{$listing->id}");

        $response->assertStatus(200);

        // Product is soft deleted
        $this->assertSoftDeleted('marketplace_products', ['id' => $listing->id]);

        // Historical order still exists with all financial audit fields intact
        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $order->id,
            'product_id' => $listing->id,
            'status' => MarketplaceOrder::STATUS_COMPLETED,
            'escrow_status' => MarketplaceOrder::ESCROW_RELEASED,
        ]);
    }

    /**
     * 8. Test Product Image Upload endpoint stores file on public storage.
     */
    public function test_product_image_upload_endpoint_accepts_file_and_stores_in_storage(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('laptop.jpg', 800, 600)->size(1500);

        $response = $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/v1/marketplace/media/upload', [
                'image' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'url',
                'path',
                'data' => ['url', 'path'],
            ]);

        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);
    }

    /**
     * 9. Test Product creation with uploaded image URLs sets primary image and images array.
     */
    public function test_seller_can_create_listing_with_uploaded_images(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'title' => 'MacBook Pro M3 Max',
            'price' => 280000.00,
            'condition' => 'used',
            'location' => 'Gulshan, Dhaka',
            'description' => 'Super fast laptop with 36GB unified memory.',
            'images' => [
                'https://cdn.example.com/macbook-1.jpg',
                'https://cdn.example.com/macbook-2.jpg',
            ],
        ];

        $response = $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/v1/marketplace/products', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $created = MarketplaceProduct::where('title', 'MacBook Pro M3 Max')->first();
        $this->assertNotNull($created);
        $this->assertCount(2, $created->images);
        $this->assertEquals('https://cdn.example.com/macbook-1.jpg', $created->primary_image_url);
    }
}
