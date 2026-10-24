<?php

namespace Tests\Feature;

use App\Events\PagePostPublishedEvent;
use App\Models\Comment;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageFollower;
use App\Models\PageMember;
use App\Models\PageType;
use App\Models\Post;
use App\Models\Reel;
use App\Models\Report;
use App\Models\Story;
use App\Models\User;
use App\Services\PostService;
use Database\Seeders\PageTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PART 3 — Production-Ready Page Publishing System Test Suite
 */
class PagePublishingProductionSystemPart3Test extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $editor;

    protected User $follower;

    protected User $stranger;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Event::fake([PagePostPublishedEvent::class]);

        $this->seed(PageTaxonomySeeder::class);

        $type = PageType::first() ?? PageType::create(['name' => 'Brand', 'slug' => 'brand', 'is_active' => true]);
        $category = PageCategory::first() ?? PageCategory::create(['page_type_id' => $type->id, 'name' => 'Technology', 'slug' => 'tech', 'is_active' => true]);

        $this->owner = User::factory()->create(['name' => 'Page Owner', 'username' => 'pageowner']);
        $this->editor = User::factory()->create(['name' => 'Page Editor', 'username' => 'pageeditor']);
        $this->follower = User::factory()->create(['name' => 'Loyal Follower', 'username' => 'loyal_follower']);
        $this->stranger = User::factory()->create(['name' => 'Random Stranger', 'username' => 'random_stranger']);

        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'page_type_id' => $type->id,
            'page_category_id' => $category->id,
            'name' => 'TechVerse Official',
            'slug' => 'techverse-official',
            'username' => 'techverse',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'posts_count' => 0,
            'followers_count' => 0,
        ]);

        // Editor membership
        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->editor->id,
            'role' => Page::ROLE_EDITOR,
            'status' => 'active',
            'permissions' => ['posts.create', 'posts.edit', 'posts.delete'],
        ]);

        // Follower
        PageFollower::create([
            'page_id' => $this->page->id,
            'user_id' => $this->follower->id,
        ]);
        $this->page->increment('followers_count');
    }

    public function test_page_owner_can_create_text_only_post(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/posts", [
                'content' => 'Welcome to the official TechVerse Page!',
                'audience' => 'public',
                'status' => 'published',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Post published successfully.',
            ]);

        $this->assertDatabaseHas('posts', [
            'page_id' => $this->page->id,
            'user_id' => $this->owner->id,
            'content' => 'Welcome to the official TechVerse Page!',
            'status' => 'published',
            'type' => 'text',
        ]);

        $this->assertEquals(1, $this->page->fresh()->posts_count);

        $this->assertDatabaseHas('page_audit_logs', [
            'page_id' => $this->page->id,
            'actor_id' => $this->owner->id,
            'action' => 'post.published',
            'target_type' => Post::class,
        ]);

        Event::assertDispatched(PagePostPublishedEvent::class, function ($event) {
            return $event->pageId === (int) $this->page->id;
        });
    }

    public function test_page_manager_can_upload_single_and_multi_photo_posts(): void
    {
        $photo1 = UploadedFile::fake()->image('product1.jpg', 800, 600);
        $photo2 = UploadedFile::fake()->image('product2.png', 800, 600);

        $response = $this->actingAs($this->editor)
            ->post("/pages/{$this->page->slug}/posts", [
                'content' => 'Check out our new products gallery!',
                'audience' => 'public',
                'status' => 'published',
                'media_files' => [$photo1, $photo2],
            ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $post = Post::where('page_id', $this->page->id)->latest('id')->first();
        $this->assertNotNull($post);
        $this->assertEquals('media', $post->type);
        $this->assertCount(2, $post->media);

        foreach ($post->media as $media) {
            Storage::disk('public')->assertExists($media->original_path);
        }
    }

    public function test_rejects_large_files_and_disallowed_file_types(): void
    {
        // 1. Disallowed file type (.txt / .sh)
        $invalidFile = UploadedFile::fake()->create('script.sh', 100, 'application/x-sh');

        $responseInvalid = $this->actingAs($this->owner)
            ->post("/pages/{$this->page->slug}/posts", [
                'content' => 'Test invalid file post',
                'media' => $invalidFile,
            ], ['Accept' => 'application/json']);

        $responseInvalid->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        // 2. File exceeding 50MB (52MB)
        $largeFile = UploadedFile::fake()->create('huge_video.mp4', 53000, 'video/mp4');

        $responseLarge = $this->actingAs($this->owner)
            ->post("/pages/{$this->page->slug}/posts", [
                'content' => 'Test oversized file post',
                'media' => $largeFile,
            ], ['Accept' => 'application/json']);

        $responseLarge->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_video_upload_sets_video_post_type(): void
    {
        $video = UploadedFile::fake()->create('demo.mp4', 5000, 'video/mp4');

        $response = $this->actingAs($this->owner)
            ->post("/pages/{$this->page->slug}/posts", [
                'content' => 'Watch our official keynote video!',
                'status' => 'published',
                'media' => $video,
            ], ['Accept' => 'application/json']);

        $response->assertOk();

        $post = Post::where('page_id', $this->page->id)->latest('id')->first();
        $this->assertEquals('video', $post->type);
        $this->assertDatabaseHas('media', [
            'mediable_id' => $post->id,
            'mediable_type' => Post::class,
            'mime_type' => 'video/mp4',
        ]);
    }

    public function test_dedicated_reel_publishing_workflow_for_pages(): void
    {
        $video = UploadedFile::fake()->create('reel_9_16.mp4', 4000, 'video/mp4');
        $cover = UploadedFile::fake()->image('cover.jpg', 720, 1280);

        $response = $this->actingAs($this->owner)
            ->post("/pages/{$this->page->slug}/reels", [
                'caption' => 'Sneak peek at upcoming launch! #TechVerse',
                'video' => $video,
                'cover_image' => $cover,
                'audio_title' => 'Futuristic Synth',
            ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('reels', [
            'page_id' => $this->page->id,
            'user_id' => $this->owner->id,
            'caption' => 'Sneak peek at upcoming launch! #TechVerse',
            'audio_title' => 'Futuristic Synth',
            'status' => Reel::STATUS_READY,
        ]);

        $this->assertDatabaseHas('page_audit_logs', [
            'page_id' => $this->page->id,
            'actor_id' => $this->owner->id,
            'action' => 'reel.created',
            'target_type' => Reel::class,
        ]);
    }

    public function test_dedicated_story_publishing_workflow_for_pages(): void
    {
        // 1. Text Story
        $textStoryResponse = $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/stories", [
                'type' => 'text',
                'content' => 'Flash Announcement: 24h Sale is LIVE!',
                'background_color' => '#10B981',
            ]);

        $textStoryResponse->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stories', [
            'page_id' => $this->page->id,
            'type' => 'text',
            'content' => 'Flash Announcement: 24h Sale is LIVE!',
            'background_color' => '#10B981',
        ]);

        // 2. Media Story
        $image = UploadedFile::fake()->image('story_pic.jpg', 1080, 1920);

        $mediaStoryResponse = $this->actingAs($this->owner)
            ->post("/pages/{$this->page->slug}/stories", [
                'type' => 'media',
                'content' => 'Behind the scenes at HQ',
                'media' => $image,
            ], ['Accept' => 'application/json']);

        $mediaStoryResponse->assertStatus(201);

        $this->assertDatabaseHas('stories', [
            'page_id' => $this->page->id,
            'type' => 'media',
            'content' => 'Behind the scenes at HQ',
        ]);

        $this->assertDatabaseHas('page_audit_logs', [
            'page_id' => $this->page->id,
            'action' => 'story.created',
            'target_type' => Story::class,
        ]);
    }

    public function test_feeling_activity_and_location_persisted(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/posts", [
                'content' => 'Celebrating our 100k milestone!',
                'feeling_activity' => 'Celebrating 🎉',
                'location' => 'Dhaka, Bangladesh',
                'status' => 'published',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('posts', [
            'page_id' => $this->page->id,
            'feeling_activity' => 'Celebrating 🎉',
            'location' => 'Dhaka, Bangladesh',
        ]);
    }

    public function test_audience_selection_and_visibility_authorization(): void
    {
        $postService = app(PostService::class);

        // 1. Followers-only post
        $followersPost = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Special promo code for our page followers only!',
            'audience' => 'followers',
            'status' => 'published',
        ]);

        $this->assertTrue($postService->canViewPost($followersPost, $this->owner));
        $this->assertTrue($postService->canViewPost($followersPost, $this->follower));
        $this->assertFalse($postService->canViewPost($followersPost, $this->stranger));

        // 2. Only-me / Private post
        $privatePost = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Internal draft ideas for next month',
            'audience' => 'only_me',
            'status' => 'published',
        ]);

        $this->assertTrue($postService->canViewPost($privatePost, $this->owner));
        $this->assertTrue($postService->canViewPost($privatePost, $this->editor));
        $this->assertFalse($postService->canViewPost($privatePost, $this->follower));
        $this->assertFalse($postService->canViewPost($privatePost, $this->stranger));
    }

    public function test_real_scheduling_lifecycle_and_artisan_publish_command(): void
    {
        $postService = app(PostService::class);

        // Schedule post for 2 days in the future
        $futureDate = now()->addDays(2)->toIso8601String();

        $response = $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/posts", [
                'content' => 'Scheduled announcement for next Monday!',
                'status' => 'scheduled',
                'scheduled_at' => $futureDate,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $post = Post::where('page_id', $this->page->id)->latest('id')->first();
        $this->assertEquals('scheduled', $post->status);
        $this->assertNotNull($post->scheduled_at);

        // Posts count must NOT increment yet!
        $this->assertEquals(0, $this->page->fresh()->posts_count);

        // Non-managers cannot view scheduled post
        $this->assertFalse($postService->canViewPost($post, $this->stranger));
        $this->assertTrue($postService->canViewPost($post, $this->owner));

        // Running publish command now should NOT publish future posts
        $this->artisan('pages:publish-scheduled')
            ->expectsOutput('0 scheduled post(s) published successfully.')
            ->assertExitCode(0);

        $this->assertEquals('scheduled', $post->fresh()->status);
        $this->assertEquals(0, $this->page->fresh()->posts_count);

        // Move scheduled_at to the past
        $post->update(['scheduled_at' => now()->subMinute()]);

        // Run scheduler command
        $this->artisan('pages:publish-scheduled')
            ->expectsOutput('1 scheduled post(s) published successfully.')
            ->assertExitCode(0);

        $this->assertEquals('published', $post->fresh()->status);
        $this->assertEquals(1, $this->page->fresh()->posts_count);

        Event::assertDispatched(PagePostPublishedEvent::class);
    }

    public function test_draft_saving_editing_and_publishing(): void
    {
        // 1. Save draft
        $response = $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/posts", [
                'content' => 'Draft content to be reviewed later',
                'status' => 'draft',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Draft saved successfully.',
            ]);

        $post = Post::where('page_id', $this->page->id)->latest('id')->first();
        $this->assertEquals('draft', $post->status);
        $this->assertEquals(0, $this->page->fresh()->posts_count);

        $this->assertDatabaseHas('page_audit_logs', [
            'page_id' => $this->page->id,
            'action' => 'post.draft_saved',
        ]);

        // 2. Edit draft & publish
        $updateResponse = $this->actingAs($this->owner)
            ->putJson("/pages/{$this->page->slug}/posts/{$post->id}", [
                'content' => 'Final polished content ready for world!',
                'status' => 'published',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('published', $post->fresh()->status);
        $this->assertEquals('Final polished content ready for world!', $post->fresh()->content);
        $this->assertEquals(1, $this->page->fresh()->posts_count);

        $this->assertDatabaseHas('page_audit_logs', [
            'page_id' => $this->page->id,
            'action' => 'post.updated',
        ]);
    }

    public function test_post_pinning_and_feed_ordering(): void
    {
        $post1 = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'First post',
            'status' => 'published',
            'is_pinned' => false,
        ]);

        $post2 = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Second post',
            'status' => 'published',
            'is_pinned' => false,
        ]);

        // Pin post 1
        $pin1Response = $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/posts/{$post1->id}/pin");

        $pin1Response->assertStatus(200)
            ->assertJson(['success' => true, 'is_pinned' => true]);
        $this->assertTrue($post1->fresh()->is_pinned);

        // Pin post 2 -> post 1 must unpin automatically
        $pin2Response = $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/posts/{$post2->id}/pin");

        $pin2Response->assertStatus(200)
            ->assertJson(['success' => true, 'is_pinned' => true]);
        $this->assertTrue($post2->fresh()->is_pinned);
        $this->assertFalse($post1->fresh()->is_pinned);

        // Assert feed orders pinned post first
        $feedResponse = $this->getJson("/pages/{$this->page->slug}/feed");
        $feedResponse->assertStatus(200);
        $items = $feedResponse->json('data');
        $this->assertCount(2, $items);
        $this->assertEquals($post2->id, $items[0]['id']);
    }

    public function test_post_soft_deletion_and_counter_decrement(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Post to be removed',
            'status' => 'published',
        ]);
        $this->page->update(['posts_count' => 1]);

        $deleteResponse = $this->actingAs($this->owner)
            ->deleteJson("/pages/{$this->page->slug}/posts/{$post->id}");

        $deleteResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
        $this->assertEquals(0, $this->page->fresh()->posts_count);

        $this->assertDatabaseHas('page_audit_logs', [
            'page_id' => $this->page->id,
            'action' => 'post.deleted',
            'target_id' => $post->id,
        ]);
    }

    public function test_reactions_switching_and_duplicate_prevention(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'React to this great update!',
            'status' => 'published',
            'likes_count' => 0,
        ]);

        // 1. Initial like
        $res1 = $this->actingAs($this->follower)
            ->postJson("/pages/posts/{$post->id}/react", ['type' => 'like']);

        $res1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['reacted' => true, 'type' => 'like'],
            ]);
        $this->assertEquals(1, $post->fresh()->likes_count);

        // 2. Change reaction from like to love (no duplicate reaction row, count stays 1)
        $res2 = $this->actingAs($this->follower)
            ->postJson("/pages/posts/{$post->id}/react", ['type' => 'love']);

        $res2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['reacted' => true, 'type' => 'love'],
            ]);
        $this->assertEquals(1, $post->fresh()->likes_count);

        // 3. Toggle off reaction by clicking love again
        $res3 = $this->actingAs($this->follower)
            ->postJson("/pages/posts/{$post->id}/react", ['type' => 'love']);

        $res3->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['reacted' => false],
            ]);
        $this->assertEquals(0, $post->fresh()->likes_count);
    }

    public function test_comments_nested_replies_and_authorization(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Drop your comments below!',
            'status' => 'published',
            'comments_count' => 0,
        ]);

        // 1. Comment creation
        $c1Response = $this->actingAs($this->follower)
            ->postJson("/pages/posts/{$post->id}/comments", [
                'body' => 'Amazing update, keep it up!',
            ]);

        $c1Response->assertStatus(201)
            ->assertJson(['success' => true]);
        $comment1Id = $c1Response->json('comment.id');
        $this->assertEquals(1, $post->fresh()->comments_count);

        // 2. Nested reply
        $replyResponse = $this->actingAs($this->owner)
            ->postJson("/pages/posts/{$post->id}/comments", [
                'body' => 'Thank you for supporting us!',
                'parent_id' => $comment1Id,
            ]);

        $replyResponse->assertStatus(201);
        $this->assertEquals(2, $post->fresh()->comments_count);
        $this->assertEquals(1, Comment::find($comment1Id)->replies_count);

        // 3. Edit comment (Author only)
        $editResponse = $this->actingAs($this->follower)
            ->putJson("/pages/comments/{$comment1Id}", [
                'body' => 'Amazing update, keep it up! (Edited)',
            ]);

        $editResponse->assertStatus(200);
        $this->assertEquals('Amazing update, keep it up! (Edited)', Comment::find($comment1Id)->body);

        // 4. Unauthorized edit by stranger fails
        $unauthorizedEdit = $this->actingAs($this->stranger)
            ->putJson("/pages/comments/{$comment1Id}", [
                'body' => 'Hacked body',
            ]);
        $unauthorizedEdit->assertStatus(403);

        // 5. Delete comment by page manager (Owner / Moderator can moderate comments)
        $deleteCommentResponse = $this->actingAs($this->owner)
            ->deleteJson("/pages/comments/{$comment1Id}");

        $deleteCommentResponse->assertStatus(200);
        $this->assertSoftDeleted('comments', ['id' => $comment1Id]);
        $this->assertEquals(1, $post->fresh()->comments_count);

        // 6. Comments disabled check
        $post->update(['comments_disabled' => true]);
        $disabledResponse = $this->actingAs($this->follower)
            ->postJson("/pages/posts/{$post->id}/comments", [
                'body' => 'Trying to comment when disabled',
            ]);

        $disabledResponse->assertStatus(403);
    }

    public function test_post_sharing_increments_share_count(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Share this with your friends!',
            'status' => 'published',
            'shares_count' => 0,
        ]);

        $response = $this->actingAs($this->follower)
            ->postJson("/pages/posts/{$post->id}/share", [
                'caption' => 'Sharing this awesome news!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'shares_count' => 1,
            ]);

        $this->assertDatabaseHas('post_shares', [
            'post_id' => $post->id,
            'user_id' => $this->follower->id,
            'caption' => 'Sharing this awesome news!',
        ]);

        $this->assertEquals(1, $post->fresh()->shares_count);
    }

    public function test_post_reporting_to_moderation(): void
    {
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Questionable content',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->stranger)
            ->postJson("/pages/posts/{$post->id}/report", [
                'reason' => 'spam',
                'details' => 'This post looks like promotional spam',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $this->stranger->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'spam',
            'status' => Report::STATUS_PENDING,
        ]);
    }

    public function test_feed_pagination_and_authorization_filtering(): void
    {
        // 1 public post
        $pubPost = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Public feed post',
            'audience' => 'public',
            'status' => 'published',
        ]);

        // 1 draft post
        $draftPost = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Draft secret post',
            'audience' => 'public',
            'status' => 'draft',
        ]);

        // 1 scheduled post
        $schedPost = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Scheduled post for future',
            'audience' => 'public',
            'status' => 'scheduled',
            'scheduled_at' => now()->addWeek(),
        ]);

        // Stranger should only see 1 published post
        $strangerFeed = $this->actingAs($this->stranger)
            ->getJson("/pages/{$this->page->slug}/feed");

        $strangerFeed->assertStatus(200);
        $this->assertCount(1, $strangerFeed->json('data'));
        $this->assertEquals($pubPost->id, $strangerFeed->json('data.0.id'));

        // Page owner can filter by status=draft
        $draftFeed = $this->actingAs($this->owner)
            ->getJson("/pages/{$this->page->slug}/feed?status=draft");

        $draftFeed->assertStatus(200);
        $this->assertCount(1, $draftFeed->json('data'));
        $this->assertEquals($draftPost->id, $draftFeed->json('data.0.id'));
    }

    public function test_regression_existing_user_feed_posts_still_functional(): void
    {
        $userPost = Post::create([
            'user_id' => $this->stranger->id,
            'page_id' => null,
            'content' => 'Standard user feed personal update',
            'audience' => 'public',
            'status' => 'published',
        ]);

        $postService = app(PostService::class);
        $this->assertTrue($postService->canViewPost($userPost, $this->follower));
        $this->assertTrue($postService->canViewPost($userPost, $this->stranger));
        $this->assertNull($userPost->page_id);
    }
}
