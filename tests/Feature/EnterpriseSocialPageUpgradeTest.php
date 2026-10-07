<?php

namespace Tests\Feature;

use App\Events\PagePostPublishedEvent;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EnterpriseSocialPageUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $follower;

    protected User $stranger;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::factory()->create([
            'name' => 'Tech Corp Owner',
            'username' => 'techowner',
            'email' => 'owner@techcorp.com',
        ]);

        $this->follower = User::factory()->create([
            'name' => 'Fan User',
            'username' => 'fanuser',
            'email' => 'fan@example.com',
        ]);

        $this->stranger = User::factory()->create([
            'name' => 'Stranger User',
            'username' => 'stranger',
            'email' => 'stranger@example.com',
        ]);

        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'name' => 'Tech Enterprise Hub',
            'slug' => 'tech-enterprise-hub',
            'username' => 'techhub',
            'category' => 'Technology',
            'bio' => 'Next-generation tech solutions and news.',
            'website' => 'https://techhub.example.com',
            'email' => 'contact@techhub.example.com',
            'phone' => '+8801700000000',
            'address' => 'Gulshan-2, Dhaka',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 0,
            'posts_count' => 0,
            'is_verified' => true,
        ]);
    }

    public function test_authenticated_user_can_create_enterprise_page_with_full_metadata(): void
    {
        $creator = User::factory()->create();

        $response = $this->actingAs($creator)->postJson('/api/v2/pages', [
            'name' => 'Dhaka Digital Media',
            'username' => 'dhakadigital',
            'category' => 'Media & Entertainment',
            'bio' => 'Connecting communities with digital stories.',
            'website' => 'https://dhakadigital.net',
            'email' => 'info@dhakadigital.net',
            'phone' => '+8801811223344',
            'address' => 'Banani, Dhaka',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('pages', [
            'name' => 'Dhaka Digital Media',
            'username' => 'dhakadigital',
            'owner_id' => $creator->id,
        ]);
    }

    public function test_page_creation_rejects_duplicate_username(): void
    {
        $creator = User::factory()->create();

        $response = $this->actingAs($creator)->postJson('/api/v2/pages', [
            'name' => 'Another Tech Hub',
            'username' => 'techhub', // already used by $this->page
            'category' => 'Technology',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_follow_and_unfollow_page_with_real_counter_and_notification(): void
    {
        // 1. Follow the page
        $followResponse = $this->actingAs($this->follower)->postJson("/api/v1/pages/{$this->page->id}/follow");

        $followResponse->assertStatus(200);
        $this->page->refresh();
        $this->assertEquals(1, $this->page->followers_count);
        $this->assertTrue($this->page->isFollowedBy($this->follower->id));

        // Verify real database notification was dispatched for the page owner
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $this->owner->id,
            'type' => 'page.followed',
        ]);

        // 2. Unfollow the page (toggle)
        $unfollowResponse = $this->actingAs($this->follower)->postJson("/api/v1/pages/{$this->page->id}/follow");

        $unfollowResponse->assertStatus(200);
        $this->page->refresh();
        $this->assertEquals(0, $this->page->followers_count);
        $this->assertFalse($this->page->isFollowedBy($this->follower->id));
    }

    public function test_authorized_page_manager_can_create_post_with_media_attachments(): void
    {
        Event::fake([PagePostPublishedEvent::class]);

        // Create a media item belonging to the owner
        $media = Media::create([
            'user_id' => $this->owner->id,
            'disk' => 'public',
            'original_path' => 'media/tech_product.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 102400,
        ]);

        $response = $this->actingAs($this->owner)->postJson("/api/v1/pages/{$this->page->id}/posts", [
            'content' => 'Exciting news: Our enterprise tech hub is now live in Dhaka!',
            'media_ids' => [$media->id],
        ]);

        $response->assertStatus(201);
        $postId = $response->json('data.id') ?? $response->json('data.post.id');

        $this->assertNotNull($postId);
        $this->assertDatabaseHas('posts', [
            'id' => $postId,
            'page_id' => $this->page->id,
            'content' => 'Exciting news: Our enterprise tech hub is now live in Dhaka!',
        ]);

        // Media attachment check
        $media->refresh();
        $this->assertEquals(Post::class, $media->mediable_type);
        $this->assertEquals($postId, $media->mediable_id);

        // Realtime event broadcast check
        Event::assertDispatched(PagePostPublishedEvent::class, function ($event) use ($postId) {
            return $event->pageId === $this->page->id && $event->postData['id'] === $postId;
        });
    }

    public function test_unauthorized_stranger_cannot_post_to_page(): void
    {
        $response = $this->actingAs($this->stranger)->postJson("/api/v1/pages/{$this->page->id}/posts", [
            'content' => 'I am an unauthorized stranger trying to post as this page.',
        ]);

        $response->assertStatus(403);
    }

    public function test_page_post_supports_real_reactions_and_comments(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Check out our new update!',
            'type' => 'text',
            'privacy' => 'public',
            'status' => 'published',
        ]);

        // 1. Follower reacts to the page post
        $reactResponse = $this->actingAs($this->follower)->postJson("/api/v1/posts/{$post->id}/react", [
            'type' => 'love',
        ]);
        $reactResponse->assertStatus(200);

        $this->assertDatabaseHas('reactions', [
            'user_id' => $this->follower->id,
            'reactable_type' => Post::class,
            'reactable_id' => $post->id,
            'type' => 'love',
        ]);

        // 2. Follower comments on the page post
        $commentResponse = $this->actingAs($this->follower)->postJson("/api/v1/posts/{$post->id}/comments", [
            'body' => 'Congratulations on this milestone!',
        ]);
        $commentResponse->assertStatus(201);

        $this->assertDatabaseHas('comments', [
            'user_id' => $this->follower->id,
            'post_id' => $post->id,
            'body' => 'Congratulations on this milestone!',
        ]);
    }

    public function test_page_public_view_renders_successfully_with_all_tabs(): void
    {
        // 1. Default Posts tab
        $response = $this->actingAs($this->follower)->get("/pages/{$this->page->slug}");
        $response->assertStatus(200);
        $response->assertSee('Tech Enterprise Hub');
        $response->assertSee('Technology');

        // 2. About tab
        $aboutResponse = $this->actingAs($this->follower)->get("/pages/{$this->page->slug}?tab=about");
        $aboutResponse->assertStatus(200);
        $aboutResponse->assertSee('Next-generation tech solutions and news.');
        $aboutResponse->assertSee('Gulshan-2, Dhaka');

        // 3. Photos tab
        $photosResponse = $this->actingAs($this->follower)->get("/pages/{$this->page->slug}?tab=photos");
        $photosResponse->assertStatus(200);
        $photosResponse->assertSee('আপলোডকৃত ছবি ও ভিডিও গ্যালারি');

        // 4. Followers tab
        $followersResponse = $this->actingAs($this->follower)->get("/pages/{$this->page->slug}?tab=followers");
        $followersResponse->assertStatus(200);
        $followersResponse->assertSee('পেইজ ফলোয়ারবৃন্দ');

        // 5. Events tab
        $eventsResponse = $this->actingAs($this->follower)->get("/pages/{$this->page->slug}?tab=events");
        $eventsResponse->assertStatus(200);
        $eventsResponse->assertSee('পেইজের আনুষ্ঠানিক ইভেন্টস');

        // 6. Shop tab
        $shopResponse = $this->actingAs($this->follower)->get("/pages/{$this->page->slug}?tab=shop");
        $shopResponse->assertStatus(200);
        $shopResponse->assertSee('পেইজের পণ্য ও সেবা সম্ভার');
    }

    public function test_pages_directory_supports_category_filtering_and_sorting(): void
    {
        // Another page with different category
        Page::create([
            'owner_id' => $this->owner->id,
            'name' => 'Fashion BD Style',
            'slug' => 'fashion-bd-style',
            'username' => 'fashionbd',
            'category' => 'Fashion & Apparel',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 150,
            'posts_count' => 5,
        ]);

        // Category filter
        $techFilter = $this->get('/pages?category=Technology');
        $techFilter->assertStatus(200);
        $techFilter->assertSee('Tech Enterprise Hub');
        $techFilter->assertDontSee('Fashion BD Style');

        // Popular sort
        $sortPopular = $this->get('/pages?sort=popular');
        $sortPopular->assertStatus(200);
        $sortPopular->assertSee('Fashion BD Style');
        $sortPopular->assertSee('Tech Enterprise Hub');
    }

    public function test_page_team_management_rbac_enforcement(): void
    {
        $newMember = User::factory()->create([
            'name' => 'Assigned Moderator',
            'email' => 'mod@example.com',
            'username' => 'moduser',
        ]);

        // Owner can invite a member as Moderator
        $addResponse = $this->actingAs($this->owner)->postJson("/api/v2/pages/{$this->page->id}/team/invite", [
            'identifier' => $newMember->email,
            'role' => Page::ROLE_MODERATOR,
        ]);
        $addResponse->assertStatus(201);

        $this->assertDatabaseHas('page_members', [
            'page_id' => $this->page->id,
            'user_id' => $newMember->id,
            'role' => Page::ROLE_MODERATOR,
        ]);

        // Stranger cannot manage team
        $unauthResponse = $this->actingAs($this->stranger)->postJson("/api/v2/pages/{$this->page->id}/team/invite", [
            'identifier' => $this->stranger->email,
            'role' => Page::ROLE_ADMIN,
        ]);
        $unauthResponse->assertStatus(403);
    }
}
