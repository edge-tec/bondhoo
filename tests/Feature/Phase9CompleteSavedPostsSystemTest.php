<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceProduct;
use App\Models\Media;
use App\Models\Post;
use App\Models\Reel;
use App\Models\Role;
use App\Models\SavedItem;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase9CompleteSavedPostsSystemTest extends TestCase
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

    protected function createPost(User $author, string $content): Post
    {
        return Post::create([
            'user_id' => $author->id,
            'content' => $content,
            'status' => 'published',
            'visibility' => 'public',
        ]);
    }

    /**
     * 1. Test Saving Post to Default Collection and Database Persistence
     */
    public function test_save_post_to_default_collection(): void
    {
        $author = $this->createUser('post_author', 'Post Author');
        $user = $this->createUser('bookmark_user', 'Bookmark User');

        $post = $this->createPost($author, 'Architectural patterns for scalable distributed feeds.');

        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v1/profile/saved-items/toggle', [
            'item_type' => 'post',
            'item_id' => $post->id,
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.saved', true);

        $this->assertDatabaseHas('saved_items', [
            'user_id' => $user->id,
            'item_type' => 'post',
            'item_id' => $post->id,
            'collection_name' => 'All Items',
        ]);
    }

    /**
     * 2. Test Unsave Post (Toggle Off)
     */
    public function test_unsave_post_toggle_off(): void
    {
        $author = $this->createUser('post_author_2', 'Post Author 2');
        $user = $this->createUser('bookmark_user_2', 'Bookmark User 2');

        $post = $this->createPost($author, 'Microservices communication with gRPC and WebSockets.');

        SavedItem::create([
            'user_id' => $user->id,
            'item_type' => 'post',
            'item_id' => $post->id,
            'collection_name' => 'All Items',
        ]);

        // Toggle unsave
        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v1/profile/saved-items/toggle', [
            'item_type' => 'post',
            'item_id' => $post->id,
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.saved', false);

        $this->assertDatabaseMissing('saved_items', [
            'user_id' => $user->id,
            'item_type' => 'post',
            'item_id' => $post->id,
        ]);
    }

    /**
     * 3. Test Saving Post into Custom Named Collection Folder
     */
    public function test_save_post_into_custom_collection_folder(): void
    {
        $author = $this->createUser('post_author_3', 'Post Author 3');
        $user = $this->createUser('collector_user', 'Collector User');

        $post = $this->createPost($author, 'Deep dive into Jetpack Compose Canvas and Custom Layouts.');

        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v1/profile/saved-items/toggle', [
            'item_type' => 'post',
            'item_id' => $post->id,
            'collection_name' => 'Mobile Engineering',
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('data.saved', true);

        $this->assertDatabaseHas('saved_items', [
            'user_id' => $user->id,
            'item_type' => 'post',
            'item_id' => $post->id,
            'collection_name' => 'Mobile Engineering',
        ]);
    }

    /**
     * 4. Test Retrieving Saved Items and Filtering by Collection
     */
    public function test_retrieve_and_filter_saved_items_by_collection(): void
    {
        $author = $this->createUser('author_multi', 'Author Multi');
        $user = $this->createUser('reader_user', 'Reader User');

        $post1 = $this->createPost($author, 'Laravel 13 Architecture Guide');
        $post2 = $this->createPost($author, 'Postgres Performance Tuning');

        // Save post 1 in 'Backend'
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/profile/saved-items/toggle', [
            'item_type' => 'post',
            'item_id' => $post1->id,
            'collection_name' => 'Backend',
        ]);

        // Save post 2 in 'Databases'
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/profile/saved-items/toggle', [
            'item_type' => 'post',
            'item_id' => $post2->id,
            'collection_name' => 'Databases',
        ]);

        // 1. Get all saved items
        $allRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/profile/saved-items');
        $allRes->assertStatus(200);
        $this->assertCount(2, $allRes->json('data'));

        // 2. Get saved items filtered by 'Backend' collection
        $filteredRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/profile/saved-items?collection=Backend');
        $filteredRes->assertStatus(200);
        $filteredItems = $filteredRes->json('data');
        $this->assertCount(1, $filteredItems);
        $this->assertEquals($post1->id, $filteredItems[0]['item_id']);
    }

    /**
     * 5. Test Cross-Module Bookmarking for Reels and Marketplace Products
     */
    public function test_cross_module_bookmarking_reels_and_marketplace(): void
    {
        $creator = $this->createUser('media_creator', 'Media Creator');
        $user = $this->createUser('avid_saver', 'Avid Saver');

        // 1. Bookmark a Reel
        $media = Media::create([
            'user_id' => $creator->id,
            'original_path' => 'reels/sample_reel.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024 * 1024 * 5,
            'disk' => 'public',
        ]);
        $reel = Reel::create([
            'user_id' => $creator->id,
            'caption' => 'Scenic drone footage',
            'is_published' => true,
            'privacy' => 'public',
        ]);

        $reelSaveRes = $this->actingAs($user, 'sanctum')->postJson("/api/v2/reels/{$reel->id}/save");
        $reelSaveRes->assertStatus(200);

        $savedReelsRes = $this->actingAs($user, 'sanctum')->getJson('/api/v2/reels/saved');
        $savedReelsRes->assertStatus(200);
        $this->assertNotEmpty($savedReelsRes->json('data'));

        // 2. Bookmark a Marketplace Product
        $cat = MarketplaceCategory::create([
            'name' => 'Gadgets',
            'slug' => 'gadgets',
            'display_order' => 1,
        ]);
        $product = MarketplaceProduct::create([
            'seller_id' => $creator->id,
            'category_id' => $cat->id,
            'title' => 'Wireless Keyboard',
            'description' => 'Mechanical wireless keyboard with hot-swappable switches.',
            'price' => 4500,
            'currency' => 'BDT',
            'status' => 'active',
        ]);

        $prodFavRes = $this->actingAs($user, 'sanctum')->postJson("/api/v1/marketplace/products/{$product->id}/favorite");
        $prodFavRes->assertStatus(200);

        $favProductsRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/marketplace/favorites');
        $favProductsRes->assertStatus(200);
        $this->assertNotEmpty($favProductsRes->json('data'));
    }

    /**
     * 6. Test Web Route Resolution and Saved Feed Presentation
     */
    public function test_saved_web_routes_and_navigation(): void
    {
        $user = $this->createUser('web_saved_user', 'Web Saved User');
        $author = $this->createUser('web_author', 'Web Author');

        $savedPost = $this->createPost($author, 'Crucial insights on micro-frontend state management');
        $unsavedPost = $this->createPost($author, 'A casual thought not bookmarked');

        SavedItem::create([
            'user_id' => $user->id,
            'item_type' => 'post',
            'item_id' => $savedPost->id,
            'collection_name' => 'All Items',
        ]);

        // 1. /saved route redirects to dashboard?tab=saved
        $res = $this->actingAs($user, 'web')->get('/saved');
        $res->assertStatus(302)
            ->assertRedirect('/dashboard?tab=saved');

        // 2. /dashboard?tab=saved renders successfully with the saved posts
        $dashboardRes = $this->actingAs($user, 'web')->get('/dashboard?tab=saved');
        $dashboardRes->assertStatus(200);
    }
}
