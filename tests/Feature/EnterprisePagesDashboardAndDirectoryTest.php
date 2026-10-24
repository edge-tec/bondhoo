<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageFollower;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EnterprisePagesDashboardAndDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $member;

    protected User $stranger;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::factory()->create([
            'name' => 'Founder Executive',
            'username' => 'founderexec',
            'email' => 'founder@socialhub.test',
        ]);

        $this->member = User::factory()->create([
            'name' => 'Content Manager',
            'username' => 'contentmgr',
            'email' => 'editor@socialhub.test',
        ]);

        $this->stranger = User::factory()->create([
            'name' => 'General Visitor',
            'username' => 'genvisitor',
            'email' => 'visitor@socialhub.test',
        ]);

        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'name' => 'SocialHub Official',
            'slug' => 'socialhub-official',
            'username' => 'socialhub_official',
            'category' => 'Technology',
            'bio' => 'Connecting people, ideas and opportunities.',
            'description' => 'Official enterprise presence on the network.',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 125,
            'posts_count' => 5,
            'is_verified' => true,
        ]);
    }

    public function test_authenticated_user_can_access_pages_dashboard_via_inertia(): void
    {
        $response = $this->actingAs($this->owner)->get('/app/pages');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Pages/Index')
            ->has('managedPages')
            ->has('selectedPage')
            ->has('summaryStats')
            ->has('pages.data')
            ->has('categories')
            ->has('filters')
            ->where('selectedPage.slug', 'socialhub-official')
            ->where('selectedPage.is_owner', true)
        );
    }

    public function test_pages_dashboard_includes_real_aggregated_metrics(): void
    {
        // Add follower records
        PageFollower::create([
            'page_id' => $this->page->id,
            'user_id' => $this->stranger->id,
        ]);

        // Add post records with likes
        Post::create([
            'page_id' => $this->page->id,
            'user_id' => $this->owner->id,
            'content' => 'Welcome to SocialHub!',
            'likes_count' => 24,
            'comments_count' => 6,
            'shares_count' => 2,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->owner)->get('/app/pages');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Pages/Index')
            ->where('selectedPage.name', 'SocialHub Official')
            ->has('selectedPage.stats')
            ->has('selectedPage.recent_posts', 1)
            ->has('selectedPage.trend_data')
        );
    }

    public function test_directory_supports_search_and_category_filtering(): void
    {
        Page::create([
            'owner_id' => $this->stranger->id,
            'name' => 'Culinary Delights',
            'slug' => 'culinary-delights',
            'category' => 'Food & Drink',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 50,
            'posts_count' => 1,
        ]);

        // Filter by search query
        $searchResponse = $this->actingAs($this->owner)->get('/app/pages?q=Culinary');
        $searchResponse->assertStatus(200);
        $searchResponse->assertInertia(fn (Assert $page) => $page
            ->component('Pages/Index')
            ->has('pages.data', 1)
            ->where('pages.data.0.name', 'Culinary Delights')
        );

        // Filter by category
        $catResponse = $this->actingAs($this->owner)->get('/app/pages?category=Technology');
        $catResponse->assertStatus(200);
        $catResponse->assertInertia(fn (Assert $page) => $page
            ->component('Pages/Index')
            ->where('pages.data.0.category', 'Technology')
        );
    }

    public function test_directory_tabs_filter_by_ownership_and_following(): void
    {
        PageFollower::create([
            'page_id' => $this->page->id,
            'user_id' => $this->stranger->id,
        ]);

        // Stranger views 'followed' tab
        $response = $this->actingAs($this->stranger)->get('/app/pages?tab=followed');
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Pages/Index')
            ->has('pages.data', 1)
            ->where('pages.data.0.name', 'SocialHub Official')
        );

        // Stranger views 'my_pages' tab (has none)
        $emptyResponse = $this->actingAs($this->stranger)->get('/app/pages?tab=my_pages');
        $emptyResponse->assertStatus(200);
        $emptyResponse->assertInertia(fn (Assert $page) => $page
            ->component('Pages/Index')
            ->has('pages.data', 0)
        );
    }

    public function test_page_followers_endpoint_returns_paginated_followers(): void
    {
        PageFollower::create([
            'page_id' => $this->page->id,
            'user_id' => $this->stranger->id,
        ]);

        $response = $this->actingAs($this->owner)->getJson("/pages/{$this->page->slug}/followers");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.user.id', $this->stranger->id);
        $response->assertJsonPath('data.0.user.name', 'General Visitor');
    }

    public function test_authorized_user_can_create_post_on_page(): void
    {
        $response = $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/posts", [
            'content' => 'Exciting updates on our roadmap!',
            'audience' => 'public',
            'status' => 'published',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('posts', [
            'page_id' => $this->page->id,
            'content' => 'Exciting updates on our roadmap!',
        ]);
    }

    public function test_unauthorized_user_cannot_create_post_on_page(): void
    {
        $response = $this->actingAs($this->stranger)->postJson("/pages/{$this->page->slug}/posts", [
            'content' => 'Malicious unauthorized post attempt',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('posts', [
            'page_id' => $this->page->id,
            'content' => 'Malicious unauthorized post attempt',
        ]);
    }

    public function test_owner_can_invite_team_member_to_page(): void
    {
        $response = $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/team/invite", [
            'identifier' => $this->member->username,
            'role' => 'editor',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('page_members', [
            'page_id' => $this->page->id,
            'user_id' => $this->member->id,
            'role' => 'editor',
        ]);
    }

    public function test_owner_can_update_page_details_and_settings(): void
    {
        // Update details
        $detailsResponse = $this->actingAs($this->owner)->putJson("/pages/{$this->page->slug}/details", [
            'name' => 'SocialHub Official Global',
            'category' => 'Enterprise Technology',
            'bio' => 'Next-gen social networking infra.',
            'city' => 'Singapore',
        ]);

        $detailsResponse->assertStatus(200);
        $detailsResponse->assertJsonPath('success', true);
        $this->assertDatabaseHas('pages', [
            'id' => $this->page->id,
            'name' => 'SocialHub Official Global',
            'category' => 'Enterprise Technology',
        ]);

        // Update settings
        $settingsResponse = $this->actingAs($this->owner)->putJson("/pages/{$this->page->slug}/settings", [
            'visibility' => 'public',
            'cta_type' => 'Contact Us',
            'cta_url' => 'https://example.com/contact',
        ]);

        $settingsResponse->assertStatus(200);
        $settingsResponse->assertJsonPath('success', true);
        $this->assertDatabaseHas('pages', [
            'id' => $this->page->id,
            'cta_type' => 'contact_us',
        ]);
    }

    public function test_only_owner_can_soft_delete_page(): void
    {
        // Stranger attempt fails
        $forbiddenResponse = $this->actingAs($this->stranger)->deleteJson("/api/v2/pages/{$this->page->id}");
        $forbiddenResponse->assertStatus(403);

        // Owner succeeds
        $deleteResponse = $this->actingAs($this->owner)->deleteJson("/api/v2/pages/{$this->page->id}");
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJsonPath('success', true);

        $this->assertSoftDeleted('pages', [
            'id' => $this->page->id,
            'status' => Page::STATUS_TRASH,
        ]);
    }

    public function test_enterprise_page_wall_renders_via_inertia_with_complete_props(): void
    {
        // Add a follower and a post
        PageFollower::create([
            'page_id' => $this->page->id,
            'user_id' => $this->stranger->id,
        ]);

        Post::create([
            'page_id' => $this->page->id,
            'user_id' => $this->owner->id,
            'content' => 'First post on SocialHub page wall!',
            'status' => 'published',
            'audience' => 'public',
        ]);

        $response = $this->actingAs($this->owner)->get("/app/pages/{$this->page->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Pages/Show')
            ->has('page')
            ->has('posts.data', 1)
            ->has('followers')
            ->has('photos')
            ->has('videos')
            ->has('events')
            ->has('products')
            ->has('relatedPages')
            ->where('page.name', 'SocialHub Official')
            ->where('isOwner', true)
            ->where('canManage', true)
            ->where('canPost', true)
        );
    }

    public function test_private_page_blocks_unauthorized_visitor(): void
    {
        $this->page->update(['visibility' => 'private']);

        // Stranger without permission gets 403
        $strangerResponse = $this->actingAs($this->stranger)->get("/app/pages/{$this->page->slug}");
        $strangerResponse->assertStatus(403);

        // Owner can access
        $ownerResponse = $this->actingAs($this->owner)->get("/app/pages/{$this->page->slug}");
        $ownerResponse->assertStatus(200);

        // Active follower can access private page
        PageFollower::create([
            'page_id' => $this->page->id,
            'user_id' => $this->stranger->id,
        ]);
        $followerResponse = $this->actingAs($this->stranger)->get("/app/pages/{$this->page->slug}");
        $followerResponse->assertStatus(200);
    }

    public function test_page_post_media_upload_persists_media_records(): void
    {
        Storage::fake('public');

        $file1 = UploadedFile::fake()->image('banner1.jpg', 800, 600);
        $file2 = UploadedFile::fake()->image('banner2.png', 800, 600);

        $response = $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/posts", [
            'content' => 'Post with multiple attachments',
            'audience' => 'public',
            'status' => 'published',
            'media_files' => [$file1, $file2],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $createdPost = Post::where('page_id', $this->page->id)->where('content', 'Post with multiple attachments')->first();
        $this->assertNotNull($createdPost);
        $this->assertEquals('media', $createdPost->type);

        $mediaCount = Media::where('mediable_type', Post::class)->where('mediable_id', $createdPost->id)->count();
        $this->assertEquals(2, $mediaCount);
    }

    public function test_post_editing_and_deletion_enforces_manager_authorization(): void
    {
        $post = Post::create([
            'page_id' => $this->page->id,
            'user_id' => $this->owner->id,
            'content' => 'Original Announcement',
            'status' => 'published',
            'audience' => 'public',
        ]);

        // Unauthorized visitor attempt to edit
        $forbiddenEdit = $this->actingAs($this->stranger)->putJson("/pages/{$this->page->slug}/posts/{$post->id}", [
            'content' => 'Hacked announcement',
        ]);
        $forbiddenEdit->assertStatus(403);

        // Authorized manager edit
        $validEdit = $this->actingAs($this->owner)->putJson("/pages/{$this->page->slug}/posts/{$post->id}", [
            'content' => 'Updated Official Announcement',
            'audience' => 'followers',
        ]);
        $validEdit->assertStatus(200);
        $validEdit->assertJsonPath('success', true);
        $this->assertEquals('Updated Official Announcement', $post->fresh()->content);

        // Unauthorized visitor attempt to delete
        $forbiddenDelete = $this->actingAs($this->stranger)->deleteJson("/pages/{$this->page->slug}/posts/{$post->id}");
        $forbiddenDelete->assertStatus(403);

        // Authorized manager delete
        $validDelete = $this->actingAs($this->owner)->deleteJson("/pages/{$this->page->slug}/posts/{$post->id}");
        $validDelete->assertStatus(200);
        $validDelete->assertJsonPath('success', true);
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_post_pinning_and_visibility_toggle(): void
    {
        $post = Post::create([
            'page_id' => $this->page->id,
            'user_id' => $this->owner->id,
            'content' => 'Important Community Guidelines',
            'status' => 'published',
            'audience' => 'public',
            'is_pinned' => false,
        ]);

        // Toggle Pin ON
        $pinResponse = $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/posts/{$post->id}/pin");
        $pinResponse->assertStatus(200);
        $pinResponse->assertJsonPath('is_pinned', true);
        $this->assertTrue($post->fresh()->is_pinned);

        // Toggle Pin OFF
        $unpinResponse = $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/posts/{$post->id}/pin");
        $unpinResponse->assertStatus(200);
        $unpinResponse->assertJsonPath('is_pinned', false);
        $this->assertFalse($post->fresh()->is_pinned);

        // Update Visibility
        $visResponse = $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/posts/{$post->id}/visibility", [
            'audience' => 'only_me',
        ]);
        $visResponse->assertStatus(200);
        $this->assertEquals('only_me', $post->fresh()->audience);
    }

    public function test_reactions_persistence_and_counters_on_page_posts(): void
    {
        $post = Post::create([
            'page_id' => $this->page->id,
            'user_id' => $this->owner->id,
            'content' => 'Engage with us!',
            'status' => 'published',
            'audience' => 'public',
        ]);

        // React with 'love'
        $reactResponse = $this->actingAs($this->stranger)->postJson("/posts/{$post->id}/react", [
            'type' => 'love',
        ]);
        $reactResponse->assertStatus(200);
        $reactResponse->assertJsonPath('data.reacted', true);
        $reactResponse->assertJsonPath('data.type', 'love');
        $this->assertEquals(1, $post->fresh()->likes_count);

        // Switch reaction to 'haha'
        $switchResponse = $this->actingAs($this->stranger)->postJson("/posts/{$post->id}/react", [
            'type' => 'haha',
        ]);
        $switchResponse->assertStatus(200);
        $switchResponse->assertJsonPath('data.type', 'haha');
        $this->assertEquals(1, $post->fresh()->likes_count);

        // Toggle OFF (remove reaction)
        $toggleOffResponse = $this->actingAs($this->stranger)->postJson("/posts/{$post->id}/react", [
            'type' => 'haha',
        ]);
        $toggleOffResponse->assertStatus(200);
        $toggleOffResponse->assertJsonPath('data.reacted', false);
        $this->assertEquals(0, $post->fresh()->likes_count);
    }

    public function test_comments_creation_pagination_and_page_manager_moderation(): void
    {
        $post = Post::create([
            'page_id' => $this->page->id,
            'user_id' => $this->owner->id,
            'content' => 'Questions and Feedback Thread',
            'status' => 'published',
            'audience' => 'public',
        ]);

        // Stranger posts a comment
        $commentResponse = $this->actingAs($this->stranger)->postJson("/api/v1/posts/{$post->id}/comments", [
            'body' => 'Great initiative from the team!',
        ]);
        $commentResponse->assertStatus(201);
        $commentId = $commentResponse->json('data.id');
        $this->assertNotNull($commentId);
        $this->assertEquals(1, $post->fresh()->comments_count);

        // Page owner/moderator deletes the comment (moderation check)
        $deleteCommentResponse = $this->actingAs($this->owner)->deleteJson("/api/v1/comments/{$commentId}");
        $deleteCommentResponse->assertStatus(200);
        $this->assertSoftDeleted('comments', ['id' => $commentId]);
        $this->assertEquals(0, $post->fresh()->comments_count);
    }

    public function test_post_sharing_persists_shares_record_and_increments_counter(): void
    {
        $post = Post::create([
            'page_id' => $this->page->id,
            'user_id' => $this->owner->id,
            'content' => 'Shareable Company Milestone',
            'status' => 'published',
            'audience' => 'public',
            'shares_count' => 0,
        ]);

        $shareResponse = $this->actingAs($this->stranger)->postJson("/api/v1/posts/{$post->id}/share", [
            'caption' => 'Proud to support SocialHub!',
        ]);

        $shareResponse->assertStatus(200);
        $shareResponse->assertJsonPath('success', true);
        $this->assertEquals(1, $post->fresh()->shares_count);
        $this->assertDatabaseHas('post_shares', [
            'post_id' => $post->id,
            'user_id' => $this->stranger->id,
            'caption' => 'Proud to support SocialHub!',
        ]);
    }

    public function test_page_follow_unfollow_behavior_and_counter_integrity(): void
    {
        $initialCount = $this->page->followers_count;

        // Follow
        $followResponse = $this->actingAs($this->stranger)->postJson("/pages/{$this->page->id}/follow");
        $followResponse->assertStatus(200);
        $followResponse->assertJsonPath('is_following', true);
        $this->assertEquals($initialCount + 1, $this->page->fresh()->followers_count);
        $this->assertDatabaseHas('page_followers', [
            'page_id' => $this->page->id,
            'user_id' => $this->stranger->id,
        ]);

        // Unfollow
        $unfollowResponse = $this->actingAs($this->stranger)->postJson("/pages/{$this->page->id}/follow");
        $unfollowResponse->assertStatus(200);
        $unfollowResponse->assertJsonPath('is_following', false);
        $this->assertEquals($initialCount, $this->page->fresh()->followers_count);
        $this->assertDatabaseMissing('page_followers', [
            'page_id' => $this->page->id,
            'user_id' => $this->stranger->id,
        ]);
    }

    public function test_page_branding_upload_and_removal(): void
    {
        Storage::fake('public');

        $avatarFile = UploadedFile::fake()->image('page-logo.png', 400, 400);

        // Upload Avatar
        $uploadResponse = $this->actingAs($this->owner)->postJson("/pages/{$this->page->slug}/branding", [
            'avatar' => $avatarFile,
        ]);
        $uploadResponse->assertStatus(200);
        $uploadResponse->assertJsonPath('success', true);
        $this->assertNotNull($this->page->fresh()->avatar_url);

        // Remove Avatar
        $removeResponse = $this->actingAs($this->owner)->deleteJson("/pages/{$this->page->slug}/branding/avatar");
        $removeResponse->assertStatus(200);
        $this->assertNull($this->page->fresh()->avatar_url);
    }

    public function test_follower_removal_by_authorized_manager(): void
    {
        PageFollower::create([
            'page_id' => $this->page->id,
            'user_id' => $this->stranger->id,
        ]);

        // Stranger cannot remove other followers
        $forbidden = $this->actingAs($this->stranger)->deleteJson("/pages/{$this->page->slug}/followers/{$this->stranger->id}");
        $forbidden->assertStatus(403);

        // Owner can remove follower
        $success = $this->actingAs($this->owner)->deleteJson("/pages/{$this->page->slug}/followers/{$this->stranger->id}");
        $success->assertStatus(200);
        $this->assertDatabaseMissing('page_followers', [
            'page_id' => $this->page->id,
            'user_id' => $this->stranger->id,
        ]);
    }

    public function test_legacy_blade_fallback_route_remains_operational(): void
    {
        $response = $this->actingAs($this->owner)->get("/pages/{$this->page->slug}");

        $response->assertStatus(200);
        $response->assertSee($this->page->name);
        $response->assertSee('ফলো করুন');
        $response->assertSee('বার্তা পাঠান');
    }
}
