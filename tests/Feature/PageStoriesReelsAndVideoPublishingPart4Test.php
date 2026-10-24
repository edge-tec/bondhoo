<?php

namespace Tests\Feature;

use App\Jobs\ProcessMediaJob;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageFollower;
use App\Models\PageMember;
use App\Models\PageType;
use App\Models\Post;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\Story;
use App\Models\StoryMedia;
use App\Models\StoryView;
use App\Models\User;
use Database\Seeders\PageTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PART 4 — Complete Page Stories, Reels & Video Publishing Test Suite
 */
class PageStoriesReelsAndVideoPublishingPart4Test extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $manager;

    protected User $follower;

    protected User $stranger;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Queue::fake([ProcessMediaJob::class]);

        $this->seed(PageTaxonomySeeder::class);

        $type = PageType::first() ?? PageType::create(['name' => 'Brand', 'slug' => 'brand', 'is_active' => true]);
        $category = PageCategory::first() ?? PageCategory::create(['page_type_id' => $type->id, 'name' => 'Technology', 'slug' => 'tech', 'is_active' => true]);

        $this->owner = User::factory()->create(['name' => 'Tech Owner', 'username' => 'techowner']);
        $this->manager = User::factory()->create(['name' => 'Tech Manager', 'username' => 'techmanager']);
        $this->follower = User::factory()->create(['name' => 'Tech Fan', 'username' => 'techfan']);
        $this->stranger = User::factory()->create(['name' => 'Stranger', 'username' => 'stranger']);

        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'page_type_id' => $type->id,
            'page_category_id' => $category->id,
            'name' => 'TechCorp Media',
            'slug' => 'techcorp-media',
            'username' => 'techcorp',
            'status' => Page::STATUS_ACTIVE,
            'visibility' => 'public',
            'posts_count' => 0,
            'followers_count' => 0,
        ]);

        // Manager membership
        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->manager->id,
            'role' => Page::ROLE_ADMIN,
            'status' => 'active',
            'custom_permissions' => ['posts.create', 'posts.edit', 'posts.delete'],
        ]);

        // Follower
        PageFollower::create([
            'page_id' => $this->page->id,
            'user_id' => $this->follower->id,
        ]);
        $this->page->increment('followers_count');
    }

    /*
     * -------------------------------------------------------------
     * STORIES TESTS
     * -------------------------------------------------------------
     */

    public function test_page_manager_can_create_photo_story_with_24_hour_expiration(): void
    {
        $file = UploadedFile::fake()->image('story_photo.jpg', 1080, 1920);

        $response = $this->actingAs($this->manager)
            ->postJson("/pages/{$this->page->slug}/stories", [
                'type' => 'media',
                'media_file' => $file,
                'caption' => 'Special announcement photo story!',
                'privacy' => 'public',
                'background_color' => '#0f172a',
            ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Page story published successfully.',
            ]);

        $storyId = $response->json('story.id');
        $this->assertNotNull($storyId);

        $story = Story::find($storyId);
        $this->assertEquals($this->page->id, $story->page_id);
        $this->assertEquals($this->manager->id, $story->user_id);
        $this->assertEquals('media', $story->type);
        $this->assertEquals('public', $story->privacy);
        $this->assertFalse($story->is_expired);
        $this->assertEquals(0, $story->views_count);

        // Verify 24-hour expiration server-side rule
        $this->assertNotNull($story->expires_at);
        $diffHours = $story->published_at->diffInHours($story->expires_at);
        $this->assertEquals(24, $diffHours);

        // Verify media attachment
        $this->assertCount(1, $story->storyMedia);
        $media = $story->storyMedia->first()->media;
        $this->assertNotNull($media);
        Storage::disk('public')->assertExists($media->original_path);

        // Verify audit log
        $this->assertDatabaseHas('page_audit_logs', [
            'page_id' => $this->page->id,
            'actor_id' => $this->manager->id,
            'action' => 'story.created',
            'target_id' => $story->id,
        ]);
    }

    public function test_page_manager_can_create_video_story_and_triggers_media_processing(): void
    {
        $video = UploadedFile::fake()->create('story_video.mp4', 10240, 'video/mp4');

        $response = $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/stories", [
                'type' => 'media',
                'media_file' => $video,
                'caption' => 'Exciting product preview video',
                'privacy' => 'public',
            ]);

        $response->assertCreated();

        $storyId = $response->json('story.id');
        $story = Story::find($storyId);
        $this->assertNotNull($story);

        $media = $story->storyMedia->first()->media;
        $this->assertEquals('processing', $media->processing_status);

        // Verify ProcessMediaJob was queued
        Queue::assertPushed(ProcessMediaJob::class, function ($job) use ($media) {
            return $job->mediaId === $media->id;
        });
    }

    public function test_story_creation_validates_file_types_and_size_limits(): void
    {
        // Invalid file format (PHP script)
        $invalidFile = UploadedFile::fake()->create('malicious.php', 100, 'text/x-php');

        $response = $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/stories", [
                'type' => 'media',
                'media_file' => $invalidFile,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['media_file']);

        // Missing media for media type
        $response2 = $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/stories", [
                'type' => 'media',
            ]);

        $response2->assertStatus(422);
    }

    public function test_unauthorized_user_cannot_create_page_story(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($this->stranger)
            ->postJson("/pages/{$this->page->slug}/stories", [
                'type' => 'media',
                'media_file' => $file,
            ]);

        $response->assertForbidden();
    }

    public function test_expired_story_is_excluded_from_active_stories_and_returns_410_on_view(): void
    {
        // Active story
        $activeStory = Story::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'type' => 'text',
            'content' => 'Active Story Content',
            'status' => Story::STATUS_READY,
            'privacy' => 'public',
            'published_at' => now()->subHours(2),
            'expires_at' => now()->addHours(22),
            'is_expired' => false,
            'views_count' => 0,
        ]);

        // Expired story (published 25 hours ago)
        $expiredStory = Story::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'type' => 'text',
            'content' => 'Expired Story Content',
            'status' => Story::STATUS_READY,
            'privacy' => 'public',
            'published_at' => now()->subHours(25),
            'expires_at' => now()->subHour(),
            'is_expired' => false, // even if delayed cleanup job has not run
            'views_count' => 0,
        ]);

        // 1. Check API list of active stories
        $response = $this->actingAs($this->stranger)
            ->getJson("/pages/{$this->page->slug}/stories");

        $response->assertOk();
        $storyIds = collect($response->json('data'))->pluck('id')->toArray();

        $this->assertContains($activeStory->id, $storyIds);
        $this->assertNotContains($expiredStory->id, $storyIds);

        // 2. Attempting to view the expired story returns 410 Gone
        $viewResponse = $this->actingAs($this->stranger)
            ->postJson("/pages/{$this->page->slug}/stories/{$expiredStory->id}/view");

        $viewResponse->assertStatus(410)
            ->assertJson([
                'success' => false,
                'is_expired' => true,
            ]);

        // Views count must not have incremented
        $this->assertEquals(0, $expiredStory->fresh()->views_count);
    }

    public function test_story_view_records_real_viewer_and_prevents_duplicate_view_count(): void
    {
        $story = Story::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'type' => 'text',
            'content' => 'Engaging story!',
            'status' => Story::STATUS_READY,
            'privacy' => 'public',
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'is_expired' => false,
            'views_count' => 0,
        ]);

        // First view by follower
        $res1 = $this->actingAs($this->follower)
            ->postJson("/pages/{$this->page->slug}/stories/{$story->id}/view");

        $res1->assertOk()
            ->assertJson([
                'success' => true,
                'views_count' => 1,
            ]);

        $this->assertDatabaseHas('story_views', [
            'story_id' => $story->id,
            'user_id' => $this->follower->id,
        ]);

        // Second view by same follower - must not duplicate view count
        $res2 = $this->actingAs($this->follower)
            ->postJson("/pages/{$this->page->slug}/stories/{$story->id}/view");

        $res2->assertOk()
            ->assertJson([
                'success' => true,
                'views_count' => 1,
            ]);

        $this->assertEquals(1, StoryView::where('story_id', $story->id)->count());
        $this->assertEquals(1, $story->fresh()->views_count);

        // Third view by stranger
        $res3 = $this->actingAs($this->stranger)
            ->postJson("/pages/{$this->page->slug}/stories/{$story->id}/view");

        $res3->assertOk()
            ->assertJson([
                'success' => true,
                'views_count' => 2,
            ]);

        $this->assertEquals(2, $story->fresh()->views_count);
    }

    public function test_story_audience_controls_enforce_followers_and_only_me(): void
    {
        $followersStory = Story::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'type' => 'text',
            'content' => 'Followers exclusive',
            'status' => Story::STATUS_READY,
            'privacy' => 'followers',
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'is_expired' => false,
            'views_count' => 0,
        ]);

        $privateStory = Story::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'type' => 'text',
            'content' => 'Admin drafts only',
            'status' => Story::STATUS_READY,
            'privacy' => 'only_me',
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'is_expired' => false,
            'views_count' => 0,
        ]);

        // Follower can view followersStory
        $this->actingAs($this->follower)
            ->postJson("/pages/{$this->page->slug}/stories/{$followersStory->id}/view")
            ->assertOk();

        // Stranger cannot view followersStory
        $this->actingAs($this->stranger)
            ->postJson("/pages/{$this->page->slug}/stories/{$followersStory->id}/view")
            ->assertStatus(403);

        // Stranger cannot view privateStory
        $this->actingAs($this->stranger)
            ->postJson("/pages/{$this->page->slug}/stories/{$privateStory->id}/view")
            ->assertStatus(403);

        // Page Manager can view privateStory
        $this->actingAs($this->manager)
            ->postJson("/pages/{$this->page->slug}/stories/{$privateStory->id}/view")
            ->assertOk();
    }

    public function test_only_page_managers_can_view_story_viewers_list(): void
    {
        $story = Story::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'type' => 'text',
            'content' => 'Viewer test',
            'status' => Story::STATUS_READY,
            'privacy' => 'public',
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'is_expired' => false,
            'views_count' => 1,
        ]);

        StoryView::create([
            'story_id' => $story->id,
            'user_id' => $this->follower->id,
            'viewed_at' => now(),
        ]);

        // Stranger forbidden
        $this->actingAs($this->stranger)
            ->getJson("/pages/{$this->page->slug}/stories/{$story->id}/viewers")
            ->assertForbidden();

        // Follower forbidden
        $this->actingAs($this->follower)
            ->getJson("/pages/{$this->page->slug}/stories/{$story->id}/viewers")
            ->assertForbidden();

        // Manager authorized
        $response = $this->actingAs($this->manager)
            ->getJson("/pages/{$this->page->slug}/stories/{$story->id}/viewers");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'views_count' => 1,
            ])
            ->assertJsonFragment(['username' => $this->follower->username]);
    }

    public function test_page_manager_can_delete_story_and_unlinks_media_files(): void
    {
        $file = UploadedFile::fake()->image('delete_me.jpg');
        $path = $file->store('stories/images', 'public');

        $story = Story::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'type' => 'media',
            'status' => Story::STATUS_READY,
            'privacy' => 'public',
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'is_expired' => false,
        ]);

        $media = Media::create([
            'user_id' => $this->owner->id,
            'mediable_type' => Story::class,
            'mediable_id' => $story->id,
            'collection' => 'stories',
            'disk' => 'public',
            'original_path' => $path,
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'processing_status' => 'ready',
        ]);

        StoryMedia::create([
            'story_id' => $story->id,
            'media_id' => $media->id,
            'order' => 0,
            'duration' => 5,
        ]);

        Storage::disk('public')->assertExists($path);

        $response = $this->actingAs($this->manager)
            ->deleteJson("/pages/{$this->page->slug}/stories/{$story->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('stories', ['id' => $story->id]);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);

        $this->assertDatabaseHas('page_audit_logs', [
            'page_id' => $this->page->id,
            'actor_id' => $this->manager->id,
            'action' => 'story.deleted',
            'target_id' => $story->id,
        ]);
    }

    public function test_expire_stories_artisan_command_cleans_up_media_per_retention_policy(): void
    {
        $file = UploadedFile::fake()->image('old_story.jpg');
        $path = $file->store('stories/images', 'public');

        $story = Story::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'type' => 'media',
            'status' => Story::STATUS_READY,
            'privacy' => 'public',
            'published_at' => now()->subHours(60),
            'expires_at' => now()->subHours(36), // expired 36h ago (> 24h retention)
            'is_expired' => false,
            'is_archived' => false,
        ]);

        $media = Media::create([
            'user_id' => $this->owner->id,
            'mediable_type' => Story::class,
            'mediable_id' => $story->id,
            'collection' => 'stories',
            'disk' => 'public',
            'original_path' => $path,
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'processing_status' => 'ready',
        ]);

        StoryMedia::create([
            'story_id' => $story->id,
            'media_id' => $media->id,
            'order' => 0,
            'duration' => 5,
        ]);

        Storage::disk('public')->assertExists($path);

        // Run artisan command with cleanup
        $this->artisan('stories:expire', ['--cleanup' => true, '--retention' => 24])
            ->assertExitCode(0);

        // Verify media file unlinked
        Storage::disk('public')->assertMissing($path);

        // Story record retained for analytics/audit but archived and expired
        $storyFresh = $story->fresh();
        $this->assertTrue((bool) $storyFresh->is_expired);
        $this->assertTrue((bool) $storyFresh->is_archived);
    }

    /*
     * -------------------------------------------------------------
     * REELS TESTS
     * -------------------------------------------------------------
     */

    public function test_page_manager_can_upload_vertical_reel_with_custom_thumbnail(): void
    {
        $video = UploadedFile::fake()->create('reel_9_16.mp4', 20480, 'video/mp4');
        $cover = UploadedFile::fake()->image('thumbnail.jpg', 720, 1280);

        $response = $this->actingAs($this->manager)
            ->postJson("/pages/{$this->page->slug}/reels", [
                'video' => $video,
                'cover_image' => $cover,
                'caption' => 'Exciting #tech #reel for followers! Check it out.',
                'privacy' => 'public',
                'aspect_ratio' => '9:16',
                'allow_comments' => true,
                'is_draft' => false,
            ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Page reel published successfully.',
            ]);

        $reelId = $response->json('reel.id');
        $reel = Reel::find($reelId);
        $this->assertNotNull($reel);
        $this->assertEquals($this->page->id, $reel->page_id);
        $this->assertEquals('9:16', $reel->aspect_ratio);
        $this->assertFalse($reel->is_draft);
        $this->assertEquals('public', $reel->privacy);
        $this->assertTrue((bool) $reel->allow_comments);
        $this->assertNotNull($reel->published_at);
        $this->assertNotNull($reel->cover_image_path);

        // Check storage
        Storage::disk('public')->assertExists($reel->cover_image_path);

        // Check ReelMedia
        $this->assertCount(1, $reel->media);
        $media = $reel->media->first();
        Storage::disk('public')->assertExists($media->video_path);

        // Background job queued
        Queue::assertPushed(ProcessMediaJob::class);

        // Audit log
        $this->assertDatabaseHas('page_audit_logs', [
            'page_id' => $this->page->id,
            'actor_id' => $this->manager->id,
            'action' => 'reel.created',
            'target_id' => $reel->id,
        ]);
    }

    public function test_reel_draft_workflow_save_and_subsequent_publish(): void
    {
        $video = UploadedFile::fake()->create('draft_reel.mp4', 15000, 'video/mp4');

        // 1. Save draft
        $response = $this->actingAs($this->owner)
            ->postJson("/pages/{$this->page->slug}/reels", [
                'video' => $video,
                'caption' => 'Unfinished reel draft',
                'is_draft' => true,
            ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Reel saved as draft.',
            ]);

        $reelId = $response->json('reel.id');
        $reel = Reel::find($reelId);
        $this->assertTrue($reel->is_draft);
        $this->assertNull($reel->published_at);

        // 2. Draft is not visible in public reels feed
        $publicFeed = $this->actingAs($this->stranger)
            ->getJson("/pages/{$this->page->slug}/reels");
        $publicFeed->assertOk();
        $this->assertNotContains($reelId, collect($publicFeed->json('data'))->pluck('id')->toArray());

        // 3. Manager can view in drafts tab
        $draftsFeed = $this->actingAs($this->manager)
            ->getJson("/pages/{$this->page->slug}/reels?tab=drafts");
        $draftsFeed->assertOk();
        $this->assertContains($reelId, collect($draftsFeed->json('data'))->pluck('id')->toArray());

        // 4. Publish draft
        $pubResponse = $this->actingAs($this->manager)
            ->postJson("/pages/{$this->page->slug}/reels/{$reelId}/publish");

        $pubResponse->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Reel published successfully.',
            ]);

        $this->assertFalse($reel->fresh()->is_draft);
        $this->assertNotNull($reel->fresh()->published_at);

        // 5. Now visible in public feed
        $publicFeedAfter = $this->actingAs($this->stranger)
            ->getJson("/pages/{$this->page->slug}/reels");
        $this->assertContains($reelId, collect($publicFeedAfter->json('data'))->pluck('id')->toArray());
    }

    public function test_reel_metadata_update_and_deletion(): void
    {
        $video = UploadedFile::fake()->create('edit_reel.mp4', 8000, 'video/mp4');
        $videoPath = $video->store('reels/videos', 'public');

        $reel = Reel::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'caption' => 'Original caption',
            'status' => Reel::STATUS_READY,
            'privacy' => 'public',
            'allow_comments' => true,
            'published_at' => now(),
            'is_draft' => false,
        ]);

        $media = Media::create([
            'user_id' => $this->owner->id,
            'mediable_type' => Reel::class,
            'mediable_id' => $reel->id,
            'collection' => 'reels',
            'disk' => 'public',
            'original_path' => $videoPath,
            'mime_type' => 'video/mp4',
            'size' => 8000,
            'processing_status' => 'ready',
        ]);

        ReelMedia::create([
            'reel_id' => $reel->id,
            'media_id' => $media->id,
            'quality' => 'original',
            'video_path' => $videoPath,
            'mime_type' => 'video/mp4',
            'size' => 8000,
        ]);

        // Update metadata
        $updateRes = $this->actingAs($this->manager)
            ->putJson("/pages/{$this->page->slug}/reels/{$reel->id}", [
                'caption' => 'Updated exciting caption #viral',
                'privacy' => 'followers',
                'allow_comments' => false,
            ]);

        $updateRes->assertOk();
        $fresh = $reel->fresh();
        $this->assertEquals('Updated exciting caption #viral', $fresh->caption);
        $this->assertEquals('followers', $fresh->privacy);
        $this->assertFalse((bool) $fresh->allow_comments);

        // Delete reel
        $delRes = $this->actingAs($this->manager)
            ->deleteJson("/pages/{$this->page->slug}/reels/{$reel->id}");

        $delRes->assertOk();
        $this->assertDatabaseMissing('reels', ['id' => $reel->id]);
        Storage::disk('public')->assertMissing($videoPath);
    }

    public function test_reel_view_tracking_reactions_comments_and_shares(): void
    {
        $reel = Reel::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'caption' => 'Community reel',
            'status' => Reel::STATUS_READY,
            'privacy' => 'public',
            'allow_comments' => true,
            'published_at' => now(),
            'is_draft' => false,
            'views_count' => 0,
            'likes_count' => 0,
            'comments_count' => 0,
            'shares_count' => 0,
        ]);

        // 1. Record view
        $viewRes = $this->actingAs($this->follower)
            ->postJson("/pages/{$this->page->slug}/reels/{$reel->id}/view", [
                'watch_time_seconds' => 12.5,
            ]);

        $viewRes->assertOk()
            ->assertJson(['views_count' => 1]);

        $this->assertDatabaseHas('reel_views', [
            'reel_id' => $reel->id,
            'user_id' => $this->follower->id,
            'watch_time_seconds' => 12.5,
        ]);

        // 2. React to reel
        $reactRes = $this->actingAs($this->follower)
            ->postJson("/pages/{$this->page->slug}/reels/{$reel->id}/react", [
                'type' => 'love',
            ]);

        $reactRes->assertOk();
        $this->assertDatabaseHas('reel_reactions', [
            'reel_id' => $reel->id,
            'user_id' => $this->follower->id,
            'type' => 'love',
        ]);
        $this->assertEquals(1, $reel->fresh()->likes_count);

        // 3. Add comment
        $commentRes = $this->actingAs($this->follower)
            ->postJson("/pages/{$this->page->slug}/reels/{$reel->id}/comments", [
                'comment' => 'This is a fantastic video!',
            ]);

        $commentRes->assertCreated();
        $commentId = $commentRes->json('comment.id');
        $this->assertEquals(1, $reel->fresh()->comments_count);

        // 4. Moderate/delete comment by manager
        $delCommentRes = $this->actingAs($this->manager)
            ->deleteJson("/pages/{$this->page->slug}/reels/comments/{$commentId}");

        $delCommentRes->assertOk();
        $this->assertSoftDeleted('reel_comments', ['id' => $commentId]);
        $this->assertEquals(0, $reel->fresh()->comments_count);

        // 5. Share reel
        $shareRes = $this->actingAs($this->follower)
            ->postJson("/pages/{$this->page->slug}/reels/{$reel->id}/share", [
                'platform' => 'internal',
            ]);

        $shareRes->assertOk();
        $this->assertEquals(1, $reel->fresh()->shares_count);
    }

    /*
     * -------------------------------------------------------------
     * VIDEOS TAB TESTS
     * -------------------------------------------------------------
     */

    public function test_page_videos_tab_returns_video_posts_and_reels_with_playback_metadata(): void
    {
        // 1. Create a video post
        $videoFile = UploadedFile::fake()->create('post_video.mp4', 12000, 'video/mp4');
        $videoPath = $videoFile->store('posts/media', 'public');

        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Watch our keynote video session',
            'audience' => 'public',
            'type' => 'video',
            'status' => 'published',
            'views_count' => 150,
            'likes_count' => 45,
            'comments_count' => 10,
            'shares_count' => 5,
        ]);

        Media::create([
            'user_id' => $this->owner->id,
            'mediable_type' => Post::class,
            'mediable_id' => $post->id,
            'collection' => 'posts',
            'disk' => 'public',
            'original_path' => $videoPath,
            'mime_type' => 'video/mp4',
            'size' => 12000,
            'processing_status' => 'completed',
            'metadata' => [
                'duration' => 125.0,
                'views_count' => 150,
            ],
        ]);

        // 2. Create a Reel
        $reelVideo = UploadedFile::fake()->create('reel_short.mp4', 5000, 'video/mp4');
        $rPath = $reelVideo->store('reels/videos', 'public');

        $reel = Reel::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'caption' => 'Vertical teaser clip',
            'status' => Reel::STATUS_READY,
            'privacy' => 'public',
            'published_at' => now(),
            'is_draft' => false,
            'views_count' => 300,
            'likes_count' => 80,
            'duration' => 30.0,
        ]);

        $rMedia = Media::create([
            'user_id' => $this->owner->id,
            'mediable_type' => Reel::class,
            'mediable_id' => $reel->id,
            'collection' => 'reels',
            'disk' => 'public',
            'original_path' => $rPath,
            'mime_type' => 'video/mp4',
            'size' => 5000,
            'processing_status' => 'ready',
        ]);

        ReelMedia::create([
            'reel_id' => $reel->id,
            'media_id' => $rMedia->id,
            'quality' => 'original',
            'video_path' => $rPath,
            'mime_type' => 'video/mp4',
            'size' => 5000,
        ]);

        // Request Videos API
        $response = $this->actingAs($this->stranger)
            ->getJson("/pages/{$this->page->slug}/videos");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $items = collect($response->json('data'));
        $this->assertEquals(2, $items->count());

        $postItem = $items->firstWhere('source_type', 'post');
        $this->assertNotNull($postItem);
        $this->assertEquals($post->id, $postItem['id']);
        $this->assertEquals(150, $postItem['views_count']);
        $this->assertEquals(45, $postItem['likes_count']);
        $this->assertNotEmpty($postItem['video_url']);
        $this->assertEquals('02:05', $postItem['formatted_duration']);
        $this->assertFalse($postItem['can_manage']);

        $reelItem = $items->firstWhere('source_type', 'reel');
        $this->assertNotNull($reelItem);
        $this->assertEquals($reel->id, $reelItem['id']);
        $this->assertEquals(300, $reelItem['views_count']);
        $this->assertNotEmpty($reelItem['video_url']);

        // Check that page manager sees can_manage = true
        $mgrResponse = $this->actingAs($this->manager)
            ->getJson("/pages/{$this->page->slug}/videos");

        $mgrItems = collect($mgrResponse->json('data'));
        $this->assertTrue($mgrItems->first()['can_manage']);
    }

    public function test_page_videos_tab_filters_by_audience(): void
    {
        // Followers-only video post
        $post = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Follower Exclusive Video',
            'audience' => 'followers',
            'type' => 'video',
            'status' => 'published',
        ]);

        Media::create([
            'user_id' => $this->owner->id,
            'mediable_type' => Post::class,
            'mediable_id' => $post->id,
            'collection' => 'posts',
            'disk' => 'public',
            'original_path' => 'posts/media/follower_vid.mp4',
            'mime_type' => 'video/mp4',
            'size' => 5000,
            'processing_status' => 'completed',
        ]);

        // Stranger should NOT see it
        $strangerRes = $this->actingAs($this->stranger)
            ->getJson("/pages/{$this->page->slug}/videos");
        $this->assertCount(0, $strangerRes->json('data'));

        // Follower SHOULD see it
        $followerRes = $this->actingAs($this->follower)
            ->getJson("/pages/{$this->page->slug}/videos");
        $this->assertCount(1, $followerRes->json('data'));
    }
}
