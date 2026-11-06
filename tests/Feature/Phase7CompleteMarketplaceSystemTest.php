<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase7CompleteMarketplaceSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(string $username, string $name, string $role = 'USER'): User
    {
        $user = User::factory()->create([
            'username' => $username,
            'name' => $name,
            'email' => "{$username}@example.com",
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'avatar_url' => "https://ui-avatars.com/api/?name={$username}",
                'bio' => "Bio of {$name}",
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'dark_mode' => true,
            ]
        );

        $roleModel = Role::where('name', $role)->first();
        if ($roleModel) {
            $user->roles()->syncWithoutDetaching([$roleModel->id]);
        }

        return $user;
    }

    protected function createCategory(string $name, string $slug): MarketplaceCategory
    {
        return MarketplaceCategory::create([
            'name' => $name,
            'slug' => $slug,
            'icon' => 'tag',
            'display_order' => 1,
        ]);
    }

    protected function createProduct(User $seller, MarketplaceCategory $cat, array $attributes = []): MarketplaceProduct
    {
        return MarketplaceProduct::create(array_merge([
            'seller_id' => $seller->id,
            'category_id' => $cat->id,
            'title' => 'Default Test Product',
            'description' => 'Original authentic product in excellent working condition.',
            'price' => 5000,
            'currency' => 'BDT',
            'condition' => 'used',
            'status' => 'active',
            'location' => 'Dhaka',
        ], $attributes));
    }

    /**
     * 1. Test Product Listing Creation with Title, Price, Condition, and Images
     */
    public function test_product_listing_creation(): void
    {
        $seller = $this->createUser('market_seller', 'Market Seller');
        $category = $this->createCategory('Laptops & Computers', 'laptops-computers');

        $payload = [
            'category_id' => $category->id,
            'title' => 'MacBook Pro M3 Max 36GB RAM',
            'description' => 'Mint condition workstation with original box and invoice.',
            'price' => 285000,
            'currency' => 'BDT',
            'condition' => 'used',
            'brand' => 'Apple',
            'model' => 'MacBook Pro 16',
            'is_negotiable' => true,
            'delivery_type' => 'both',
            'delivery_charge' => 150,
            'location' => 'Banani, Dhaka',
            'images' => [
                'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80',
            ],
        ];

        $res = $this->actingAs($seller, 'sanctum')->postJson('/api/v1/marketplace/products', $payload);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'MacBook Pro M3 Max 36GB RAM')
            ->assertJsonPath('data.price', 285000);

        $this->assertDatabaseHas('marketplace_products', [
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'MacBook Pro M3 Max 36GB RAM',
            'condition' => 'used',
            'status' => 'active',
        ]);
    }

    /**
     * 2. Test Product Search and Multi-Parameter Filtering
     */
    public function test_product_search_and_filtering(): void
    {
        $seller = $this->createUser('search_seller', 'Search Seller');
        $viewer = $this->createUser('search_viewer', 'Search Viewer');
        $cat = $this->createCategory('Electronics', 'electronics');

        MarketplaceProduct::create([
            'seller_id' => $seller->id,
            'category_id' => $cat->id,
            'title' => 'Sony WH-1000XM5 Headphones',
            'description' => 'Noise cancelling wireless headphones',
            'price' => 32000,
            'currency' => 'BDT',
            'condition' => 'used',
            'status' => 'active',
            'location' => 'Dhanmondi, Dhaka',
        ]);

        MarketplaceProduct::create([
            'seller_id' => $seller->id,
            'category_id' => $cat->id,
            'title' => 'Wooden Dining Chair',
            'description' => 'Solid teak wood chair',
            'price' => 6500,
            'currency' => 'BDT',
            'condition' => 'new',
            'status' => 'active',
            'location' => 'Mirpur, Dhaka',
        ]);

        // Search for Sony headphones
        $res = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/marketplace/products?q=Sony&condition=used');
        $res->assertStatus(200)
            ->assertJsonPath('success', true);

        $items = $res->json('data');
        $this->assertCount(1, $items);
        $this->assertEquals('Sony WH-1000XM5 Headphones', $items[0]['title']);
    }

    /**
     * 3. Test Listing Management: Edit Listing and Mark as Sold
     */
    public function test_listing_edit_and_mark_as_sold(): void
    {
        $seller = $this->createUser('mgmt_seller', 'Mgmt Seller');
        $cat = $this->createCategory('Smartphones', 'smartphones');

        $product = $this->createProduct($seller, $cat, [
            'title' => 'Google Pixel 8 Pro',
            'price' => 75000,
            'currency' => 'BDT',
            'condition' => 'used',
            'status' => 'active',
        ]);

        // 1. Edit price and title
        $updateRes = $this->actingAs($seller, 'sanctum')->putJson("/api/v1/marketplace/products/{$product->id}", [
            'price' => 72000,
            'title' => 'Google Pixel 8 Pro 128GB Bay Blue',
        ]);
        $updateRes->assertStatus(200);

        $this->assertDatabaseHas('marketplace_products', [
            'id' => $product->id,
            'price' => 72000,
            'title' => 'Google Pixel 8 Pro 128GB Bay Blue',
        ]);

        // 2. Mark as sold
        $statusRes = $this->actingAs($seller, 'sanctum')->postJson("/api/v1/marketplace/products/{$product->id}/status", [
            'status' => 'sold',
        ]);
        $statusRes->assertStatus(200);

        $this->assertDatabaseHas('marketplace_products', [
            'id' => $product->id,
            'status' => 'sold',
        ]);
    }

    /**
     * 4. Test Save to Wishlist / Favorites
     */
    public function test_saved_wishlist_items(): void
    {
        $seller = $this->createUser('fav_seller', 'Fav Seller');
        $buyer = $this->createUser('fav_buyer', 'Fav Buyer');
        $cat = $this->createCategory('Gaming', 'gaming');

        $product = $this->createProduct($seller, $cat, [
            'title' => 'PlayStation 5 Slim Edition',
            'price' => 58000,
            'currency' => 'BDT',
            'condition' => 'new',
            'status' => 'active',
        ]);

        // 1. Save listing to favorites
        $favRes = $this->actingAs($buyer, 'sanctum')->postJson("/api/v1/marketplace/products/{$product->id}/favorite");
        $favRes->assertStatus(200)
            ->assertJsonPath('is_favorited', true);

        // 2. Retrieve favorites list
        $listRes = $this->actingAs($buyer, 'sanctum')->getJson('/api/v1/marketplace/favorites');
        $listRes->assertStatus(200);
        $savedItems = $listRes->json('data');
        $this->assertNotEmpty($savedItems);
    }

    /**
     * 5. Test Buyer-Seller Messaging Linked to Product
     */
    public function test_buyer_seller_contact_messaging(): void
    {
        $seller = $this->createUser('msg_seller', 'Msg Seller');
        $buyer = $this->createUser('msg_buyer', 'Msg Buyer');
        $cat = $this->createCategory('Watches', 'watches');

        $product = $this->createProduct($seller, $cat, [
            'title' => 'Seiko Automatic Diver Watch',
            'price' => 38000,
            'currency' => 'BDT',
            'condition' => 'used',
            'status' => 'active',
        ]);

        // Buyer contacts seller
        $contactRes = $this->actingAs($buyer, 'sanctum')->postJson("/api/v1/marketplace/products/{$product->id}/contact", [
            'message' => 'Is this watch still available? Can we meet in Gulshan?',
        ]);

        $contactRes->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertNotNull($contactRes->json('data.conversation_id'));
    }

    /**
     * 6. Test Order Placement / Checkout Flow
     */
    public function test_order_placement_checkout_flow(): void
    {
        $seller = $this->createUser('order_seller', 'Order Seller');
        $buyer = $this->createUser('order_buyer', 'Order Buyer');
        $cat = $this->createCategory('Cameras', 'cameras');

        $product = $this->createProduct($seller, $cat, [
            'title' => 'Fujifilm X-T30 II Body',
            'price' => 95000,
            'currency' => 'BDT',
            'condition' => 'new',
            'status' => 'active',
        ]);

        // Buyer places order
        $orderRes = $this->actingAs($buyer, 'sanctum')->postJson("/api/v1/marketplace/products/{$product->id}/order", [
            'shipping_name' => 'Buyer Chowdhury',
            'shipping_phone' => '+8801811223344',
            'shipping_address' => 'House 14, Road 5, Block C, Banani, Dhaka',
            'delivery_type' => 'standard',
            'quantity' => 1,
            'payment_method' => 'bkash',
        ]);

        $orderRes->assertStatus(201);

        $this->assertDatabaseHas('marketplace_orders', [
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        // Buyer verifies orders list
        $ordersListRes = $this->actingAs($buyer, 'sanctum')->getJson('/api/v1/marketplace/orders?role=buyer');
        $ordersListRes->assertStatus(200);
        $this->assertNotEmpty($ordersListRes->json('data'));
    }

    /**
     * 7. Test Listing Deletion and Authorization Security
     */
    public function test_listing_deletion_authorization_security(): void
    {
        $seller = $this->createUser('del_seller', 'Del Seller');
        $intruder = $this->createUser('del_intruder', 'Del Intruder');
        $cat = $this->createCategory('Books', 'books');

        $product = $this->createProduct($seller, $cat, [
            'title' => 'Structure and Interpretation of Computer Programs',
            'price' => 2200,
            'currency' => 'BDT',
            'condition' => 'new',
            'status' => 'active',
        ]);

        // 1. Intruder cannot delete listing -> 403 Forbidden
        $intruderRes = $this->actingAs($intruder, 'sanctum')->deleteJson("/api/v1/marketplace/products/{$product->id}");
        $intruderRes->assertStatus(403);
        $this->assertDatabaseHas('marketplace_products', ['id' => $product->id]);

        // 2. Seller deletes listing -> 200 OK
        $sellerRes = $this->actingAs($seller, 'sanctum')->deleteJson("/api/v1/marketplace/products/{$product->id}");
        $sellerRes->assertStatus(200);
        $this->assertSoftDeleted('marketplace_products', ['id' => $product->id]);
    }

    /**
     * 8. Test Web Route Resolution for Marketplace
     */
    public function test_marketplace_web_routes_and_navigation(): void
    {
        $user = $this->createUser('web_market_user', 'Web Market User');
        $cat = $this->createCategory('Accessories', 'accessories');

        $product = $this->createProduct($user, $cat, [
            'title' => 'Leather Wallet',
            'price' => 1500,
            'currency' => 'BDT',
            'condition' => 'new',
            'status' => 'active',
        ]);

        // 1. Marketplace index view
        $indexRes = $this->actingAs($user, 'web')->get('/marketplace');
        $indexRes->assertStatus(200);

        // 2. Create listing view
        $createRes = $this->actingAs($user, 'web')->get('/marketplace/create');
        $createRes->assertStatus(200);

        // 3. Single product view
        $showRes = $this->actingAs($user, 'web')->get("/marketplace/{$product->id}");
        $showRes->assertStatus(200);

        // 4. My listings view
        $myListingsRes = $this->actingAs($user, 'web')->get('/marketplace/my-listings');
        $myListingsRes->assertStatus(200);

        // 5. Checkout view (buyer accessing active listing)
        $buyer = $this->createUser('checkout_buyer', 'Checkout Buyer');
        $checkoutRes = $this->actingAs($buyer, 'web')->get("/marketplace/{$product->id}/checkout");
        $checkoutRes->assertStatus(200);

        // 6. Orders dashboard view
        $ordersRes = $this->actingAs($user, 'web')->get('/marketplace/orders');
        $ordersRes->assertStatus(200);
    }
}
