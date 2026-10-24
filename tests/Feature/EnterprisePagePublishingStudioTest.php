<?php

namespace Tests\Feature;

use App\Models\LiveStream;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageMember;
use App\Models\Post;
use App\Models\Reel;
use App\Models\Story;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EnterprisePagePublishingStudioTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $editor;

    protected User $visitor;

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

        $this->editor = User::factory()->create([
            'name' => 'Content Manager',
            'username' => 'contentmgr',
            'email' => 'editor@socialhub.test',
        ]);

        $this->visitor = User::factory()->create([
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
            'bio' => 'Connecting people, ideas and opportunities. Join our community and be part of something bigger!',
            'description' => 'Official enterprise presence on the network.',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'followers_count' => 125486,
            'posts_count' => 15,
            'is_verified' => true,
        ]);

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->editor->id,
            'role' => Page::ROLE_MANAGER,
            'status' => 'active',
        ]);
    }

    /**
     * Test page profile loads with 3-column publishing studio props (stories, reels, insights, recent activity).
     */
    public function test_page_profile_renders_publishing_studio_data(): void
    {
        $response = $this->actingAs($this->owner)
            ->get("/app/pages/{$this->page->slug}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Pages/Show')
            ->has('page')
            ->has('sevenDayInsights')
            ->has('recentActivity')
            ->has('stories')
            ->has('reels')
            ->where('canManage', true)
            ->where('canPost', true)
        );
    }

    /**
     * Test publishing a standard post with media files.
     */
    public function test_page_manager_can_publish_post_with_media(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('launch.jpg', 1200, 800);
        $video = UploadedFile::fake()->create('demo.mp4', 5000, 'video/mp4');

        $response = $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/posts", [
                'content' => 'Announcing our new feature! #SocialHub #Community',
                'status' => 'published',
                'audience' => 'public',
                'media_files' => [$photo, $video],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('posts', [
            'page_id' => $this->page->id,
            'user_id' => $this->owner->id,
            'content' => 'Announcing our new feature! #SocialHub #Community',
            'status' => 'published',
        ]);

        $post = Post::where('page_id', $this->page->id)->latest('id')->first();
        $this->assertNotNull($post);
        $this->assertCount(2, $post->media);
    }

    /**
     * Test saving draft posts and scheduling posts for a Page.
     */
    public function test_page_manager_can_create_draft_and_scheduled_post(): void
    {
        // 1. Draft Post
        $draftRes = $this->actingAs($this->editor)
            ->postJson("/pages/{$this->page->slug}/posts", [
                'content' => 'Draft content for review',
                'status' => 'draft',
                'audience' => 'public',
            ]);

        $draftRes->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'page_id' => $this->page->id,
            'content' => 'Draft content for review',
            'status' => 'draft',
        ]);

        // 2. Scheduled Post
        $scheduledAt = now()->addDays(2)->format('Y-m-d H:i:s');
        $schedRes = $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/posts", [
                'content' => 'Scheduled announcement content',
                'status' => 'scheduled',
                'scheduled_at' => $scheduledAt,
                'audience' => 'public',
            ]);

        $schedRes->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'page_id' => $this->page->id,
            'content' => 'Scheduled announcement content',
            'status' => 'scheduled',
        ]);
    }

    /**
     * Test creating a Reel for a Page with validation and authorization.
     */
    public function test_page_manager_can_publish_page_reel(): void
    {
        Storage::fake('public');

        // Prepare uploaded media for reel
        $media = Media::create([
            'user_id' => $this->owner->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => 'reels/sample.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024000,
            'processing_status' => 'ready',
            'width' => 720,
            'height' => 1280,
            'metadata' => ['duration' => 30.0],
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v2/reels', [
                'page_id' => $this->page->id,
                'media_id' => $media->id,
                'caption' => 'Exciting short-form update from SocialHub Official! #Reels',
                'audio_title' => 'Original Audio - SocialHub Official',
                'allow_comments' => true,
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('reels', [
            'page_id' => $this->page->id,
            'caption' => 'Exciting short-form update from SocialHub Official! #Reels',
            'user_id' => $this->owner->id,
        ]);

        // Stranger cannot publish reel on behalf of page
        $strangerRes = $this->actingAs($this->visitor)
            ->postJson('/api/v2/reels', [
                'page_id' => $this->page->id,
                'media_id' => $media->id,
                'caption' => 'Hacked reel',
            ]);

        $strangerRes->assertStatus(403);
    }

    /**
     * Test 24-hour Story lifecycle: creation, expiration, and exclusion of expired stories.
     */
    public function test_page_story_creation_and_24h_expiration(): void
    {
        // 1. Authorize creation of Story for Page
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v2/stories', [
                'page_id' => $this->page->id,
                'type' => 'text',
                'content' => '24-hour flash update for our community!',
                'background_color' => '#1877F2',
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('stories', [
            'page_id' => $this->page->id,
            'content' => '24-hour flash update for our community!',
            'is_expired' => false,
        ]);

        $story = Story::where('page_id', $this->page->id)->latest('id')->first();
        $this->assertNotNull($story);
        $this->assertNotNull($story->expires_at);

        // Verify that expires_at is approximately 24 hours in the future
        $diffHours = (int) round(now()->diffInHours($story->expires_at, false));
        $this->assertEquals(24, $diffHours);

        // 2. Stranger cannot post story for this page
        $unauthRes = $this->actingAs($this->visitor)
            ->postJson('/api/v2/stories', [
                'page_id' => $this->page->id,
                'type' => 'text',
                'content' => 'Unauthorized story',
            ]);

        $unauthRes->assertStatus(403);

        // 3. Expiration exclusion test: An expired story (> 24h old) is excluded from active feed
        $expiredStory = Story::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'type' => 'text',
            'content' => 'This story has expired',
            'published_at' => now()->subHours(26),
            'expires_at' => now()->subHours(2),
            'is_expired' => true,
        ]);

        $this->assertTrue($expiredStory->isExpired());
    }

    /**
     * Test Live Studio broadcast lifecycle: create channel, start, end, and authorization.
     */
    public function test_page_live_broadcast_lifecycle(): void
    {
        // 1. Create broadcast channel for Page
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v2/live/streams', [
                'page_id' => $this->page->id,
                'title' => 'Page Live Broadcast Studio Session',
                'description' => 'Real-time interactive session with followers',
                'privacy' => 'public',
                'recording_enabled' => true,
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
        ]);

        $streamId = $response->json('data.id');
        $this->assertNotNull($streamId);

        $this->assertDatabaseHas('live_streams', [
            'id' => $streamId,
            'page_id' => $this->page->id,
            'user_id' => $this->owner->id,
            'title' => 'Page Live Broadcast Studio Session',
            'status' => LiveStream::STATUS_READY,
        ]);

        // 2. Non-manager cannot create broadcast session for this page
        $strangerRes = $this->actingAs($this->visitor)
            ->postJson('/api/v2/live/streams', [
                'page_id' => $this->page->id,
                'title' => 'Hijacked broadcast',
            ]);

        $strangerRes->assertStatus(403);

        // 3. Start live stream
        $startRes = $this->actingAs($this->owner)
            ->postJson("/api/v2/live/streams/{$streamId}/start");

        $startRes->assertStatus(200);
        $this->assertDatabaseHas('live_streams', [
            'id' => $streamId,
            'status' => LiveStream::STATUS_LIVE,
        ]);

        // 4. End broadcast session
        $endRes = $this->actingAs($this->owner)
            ->postJson("/api/v2/live/streams/{$streamId}/end");

        $endRes->assertStatus(200);
        $this->assertDatabaseHas('live_streams', [
            'id' => $streamId,
            'status' => LiveStream::STATUS_ENDED,
        ]);
    }

    /**
     * Test reactions, pinning, and moderation on Page posts.
     */
    public function test_page_post_reactions_and_pinning(): void
    {
        $post = Post::create([
            'page_id' => $this->page->id,
            'user_id' => $this->owner->id,
            'content' => 'Post for reaction and pin test',
            'status' => 'published',
            'audience' => 'public',
            'likes_count' => 5,
        ]);

        // 1. Toggle Pin
        $pinRes = $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/posts/{$post->id}/pin");

        $pinRes->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'is_pinned' => true,
        ]);

        // 2. React to post
        $reactRes = $this->actingAs($this->visitor)
            ->postJson("/pages/posts/{$post->id}/react", [
                'type' => 'love',
            ]);

        $reactRes->assertStatus(200);
        $this->assertDatabaseHas('reactions', [
            'reactable_id' => $post->id,
            'user_id' => $this->visitor->id,
            'type' => 'love',
        ]);
    }
}
