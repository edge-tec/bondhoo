<?php

namespace Tests\Feature;

use App\Jobs\ProcessMediaJob;
use App\Models\LiveStream;
use App\Models\LiveStreamViewer;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageFollower;
use App\Models\PageMember;
use App\Models\PageType;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\PageTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PART 5 — Production Live Video & Share Video Test Suite
 */
class PageLiveVideoAndShareVideoPart5Test extends TestCase
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

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->manager->id,
            'role' => Page::ROLE_MANAGER,
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        PageFollower::create([
            'page_id' => $this->page->id,
            'user_id' => $this->follower->id,
            'created_at' => now(),
        ]);
        $this->page->increment('followers_count');
    }

    // ==========================================
    // 1. LIVE BROADCAST SESSION CREATION & CREDENTIAL SECURITY
    // ==========================================

    public function test_manager_can_create_live_broadcast_session_with_scoped_host_token(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson(route('pages.live.create', ['slug' => $this->page->slug]), [
                'title' => 'Product Keynote 2026',
                'description' => 'Unveiling new innovations',
                'privacy' => 'public',
                'record' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'stream' => ['id', 'title', 'status', 'page_id'],
                'host_token',
                'ice_servers',
            ]);

        // Verify credentials security: stream_key must NOT be exposed in API payload
        $this->assertArrayNotHasKey('stream_key', $response->json('stream'));
        $this->assertNotEmpty($response->json('host_token'));

        $this->assertDatabaseHas('live_streams', [
            'page_id' => $this->page->id,
            'title' => 'Product Keynote 2026',
            'privacy' => 'public',
            'status' => LiveStream::STATUS_READY,
            'recording_enabled' => true,
        ]);
    }

    public function test_unauthorized_user_cannot_create_live_broadcast_on_page(): void
    {
        $response = $this->actingAs($this->stranger)
            ->postJson(route('pages.live.create', ['slug' => $this->page->slug]), [
                'title' => 'Unauthorized Broadcast',
            ]);

        $response->assertStatus(403);
    }

    // ==========================================
    // 2. GENUINE LIVE PUBLICATION & FEED VISIBILITY
    // ==========================================

    public function test_stream_is_not_publicly_live_until_broadcaster_confirms_publication(): void
    {
        // Create session
        $createRes = $this->actingAs($this->owner)
            ->postJson(route('pages.live.create', ['slug' => $this->page->slug]), [
                'title' => 'Waiting for Media Stream',
                'privacy' => 'public',
            ]);

        $streamId = $createRes->json('stream.id');
        $hostToken = $createRes->json('host_token');

        // Check active stream for viewer: should not be active yet (it is ready, not live)
        $activeRes = $this->actingAs($this->follower)
            ->getJson(route('pages.live.active', ['slug' => $this->page->slug]));

        $activeRes->assertStatus(200)
            ->assertJsonPath('active', false);

        // Broadcaster confirms WebRTC media tracks are transmitting
        $pubRes = $this->actingAs($this->owner)
            ->postJson(route('pages.live.publish', ['slug' => $this->page->slug, 'streamId' => $streamId]), [
                'host_token' => $hostToken,
            ]);

        $pubRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('stream.status', LiveStream::STATUS_LIVE);

        // Now stream MUST appear as active
        $activeResAfter = $this->actingAs($this->follower)
            ->getJson(route('pages.live.active', ['slug' => $this->page->slug]));

        $activeResAfter->assertStatus(200)
            ->assertJsonPath('active', true)
            ->assertJsonPath('stream.id', $streamId);
    }

    public function test_publish_fails_with_invalid_host_token(): void
    {
        $createRes = $this->actingAs($this->owner)
            ->postJson(route('pages.live.create', ['slug' => $this->page->slug]), [
                'title' => 'Test Stream',
            ]);
        $streamId = $createRes->json('stream.id');

        $pubRes = $this->actingAs($this->stranger)
            ->postJson(route('pages.live.publish', ['slug' => $this->page->slug, 'streamId' => $streamId]), [
                'host_token' => 'invalid-token-here',
            ]);

        $pubRes->assertStatus(403);
    }

    // ==========================================
    // 3. AUDIENCE AUTHORIZATION & VIEWER CREDENTIALS
    // ==========================================

    public function test_followers_only_stream_restricts_strangers_and_allows_followers(): void
    {
        $stream = LiveStream::create([
            'channel_id' => 'chan_followers_test',
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'title' => 'Follower Exclusive Stream',
            'privacy' => LiveStream::PRIVACY_FRIENDS, // followers mapping
            'stream_key' => 'live_sk_followers_test',
            'ingest_url' => 'rtmp://live.edge-tec.com/live',
            'playback_url' => 'https://live.edge-tec.com/hls/followers_test.m3u8',
            'status' => LiveStream::STATUS_LIVE,
            'started_at' => now(),
        ]);

        // Stranger attempts to view token
        $strangerRes = $this->actingAs($this->stranger)
            ->getJson(route('pages.live.viewerToken', ['slug' => $this->page->slug, 'streamId' => $stream->id]));
        $strangerRes->assertStatus(403);

        // Follower requests token
        $followerRes = $this->actingAs($this->follower)
            ->getJson(route('pages.live.viewerToken', ['slug' => $this->page->slug, 'streamId' => $stream->id]));
        $followerRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['stream_id', 'viewer_token', 'ice_servers']);
    }

    public function test_admins_only_stream_restricts_regular_followers(): void
    {
        $stream = LiveStream::create([
            'channel_id' => 'chan_admins_only_test',
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'title' => 'Internal Strategy Live',
            'privacy' => LiveStream::PRIVACY_ONLY_ME,
            'stream_key' => 'live_sk_admins_only_test',
            'ingest_url' => 'rtmp://live.edge-tec.com/live',
            'playback_url' => 'https://live.edge-tec.com/hls/admins_test.m3u8',
            'status' => LiveStream::STATUS_LIVE,
            'started_at' => now(),
        ]);

        // Follower receives 403
        $followerRes = $this->actingAs($this->follower)
            ->getJson(route('pages.live.viewerToken', ['slug' => $this->page->slug, 'streamId' => $stream->id]));
        $followerRes->assertStatus(403);

        // Manager receives 200
        $managerRes = $this->actingAs($this->manager)
            ->getJson(route('pages.live.viewerToken', ['slug' => $this->page->slug, 'streamId' => $stream->id]));
        $managerRes->assertStatus(200);
    }

    // ==========================================
    // 4. REAL VIEWER COUNTS & PRESENCE TRACKING
    // ==========================================

    public function test_viewer_join_and_leave_tracks_real_viewer_count_and_history(): void
    {
        $stream = LiveStream::create([
            'channel_id' => 'chan_viewers_metric_test',
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'title' => 'Townhall Meeting',
            'privacy' => LiveStream::PRIVACY_PUBLIC,
            'stream_key' => 'live_sk_viewers_test',
            'ingest_url' => 'rtmp://live.edge-tec.com/live',
            'playback_url' => 'https://live.edge-tec.com/hls/viewers_test.m3u8',
            'status' => LiveStream::STATUS_LIVE,
            'viewers_count' => 0,
            'peak_viewers' => 0,
            'started_at' => now(),
        ]);

        // Viewer 1 joins
        $this->actingAs($this->follower)
            ->postJson(route('pages.live.join', ['slug' => $this->page->slug, 'streamId' => $stream->id]))
            ->assertStatus(200)
            ->assertJsonPath('viewers_count', 1);

        $stream->refresh();
        $this->assertEquals(1, $stream->viewers_count);
        $this->assertEquals(1, $stream->peak_viewers);

        // Viewer 2 joins
        $this->actingAs($this->stranger)
            ->postJson(route('pages.live.join', ['slug' => $this->page->slug, 'streamId' => $stream->id]))
            ->assertStatus(200)
            ->assertJsonPath('viewers_count', 2);

        $stream->refresh();
        $this->assertEquals(2, $stream->viewers_count);
        $this->assertEquals(2, $stream->peak_viewers);
        $this->assertEquals(2, LiveStreamViewer::where('live_stream_id', $stream->id)->count());

        // Viewer 1 leaves
        $this->actingAs($this->follower)
            ->postJson(route('pages.live.leave', ['slug' => $this->page->slug, 'streamId' => $stream->id]))
            ->assertStatus(200)
            ->assertJsonPath('viewers_count', 1);

        $stream->refresh();
        $this->assertEquals(1, $stream->viewers_count);
        $this->assertEquals(2, $stream->peak_viewers); // Peak remains 2
    }

    // ==========================================
    // 5. HEARTBEAT AND RECONNECTION STATUS
    // ==========================================

    public function test_broadcaster_heartbeat_updates_timestamp_and_returns_live_metrics(): void
    {
        $createRes = $this->actingAs($this->owner)
            ->postJson(route('pages.live.create', ['slug' => $this->page->slug]), [
                'title' => 'Heartbeat Test',
                'privacy' => 'public',
            ]);
        $streamId = $createRes->json('stream.id');
        $hostToken = $createRes->json('host_token');

        // Confirm publication
        $this->actingAs($this->owner)
            ->postJson(route('pages.live.publish', ['slug' => $this->page->slug, 'streamId' => $streamId]), [
                'host_token' => $hostToken,
            ]);

        // Heartbeat tick
        $hbRes = $this->actingAs($this->owner)
            ->postJson(route('pages.live.heartbeat', ['slug' => $this->page->slug, 'streamId' => $streamId]), [
                'host_token' => $hostToken,
            ]);

        $hbRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('active', true)
            ->assertJsonStructure(['success', 'active', 'viewer_count']);

        $stream = LiveStream::find($streamId);
        $this->assertNotNull($stream->last_heartbeat_at);
    }

    // ==========================================
    // 6. BROADCAST TERMINATION & CREDENTIAL INVALIDATION
    // ==========================================

    public function test_ending_broadcast_invalidates_credentials_and_marks_status_ended(): void
    {
        $createRes = $this->actingAs($this->owner)
            ->postJson(route('pages.live.create', ['slug' => $this->page->slug]), [
                'title' => 'Closing Broadcast Test',
                'privacy' => 'public',
            ]);
        $streamId = $createRes->json('stream.id');
        $hostToken = $createRes->json('host_token');

        $this->actingAs($this->owner)
            ->postJson(route('pages.live.publish', ['slug' => $this->page->slug, 'streamId' => $streamId]), [
                'host_token' => $hostToken,
            ]);

        // End broadcast
        $endRes = $this->actingAs($this->owner)
            ->postJson(route('pages.live.end', ['slug' => $this->page->slug, 'streamId' => $streamId]), [
                'host_token' => $hostToken,
            ]);

        $endRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('stream.status', LiveStream::STATUS_ENDED);

        $stream = LiveStream::find($streamId);
        $this->assertEquals(LiveStream::STATUS_ENDED, $stream->status);
        $this->assertNotNull($stream->ended_at);
        // Active SFU host token must be invalidated
        $this->assertNull($stream->sfu_host_token);

        // It must NO LONGER appear as active in live queries
        $activeRes = $this->actingAs($this->follower)
            ->getJson(route('pages.live.active', ['slug' => $this->page->slug]));
        $activeRes->assertStatus(200)->assertJsonPath('active', false);
    }

    // ==========================================
    // 7. RECORDING PERSISTENCE UPON BROADCAST COMPLETION
    // ==========================================

    public function test_uploading_valid_recording_persists_video_to_page_feed(): void
    {
        $createRes = $this->actingAs($this->owner)
            ->postJson(route('pages.live.create', ['slug' => $this->page->slug]), [
                'title' => 'Broadcast With Recording',
                'privacy' => 'public',
                'record' => true,
            ]);
        $streamId = $createRes->json('stream.id');
        $hostToken = $createRes->json('host_token');

        // Broadcaster ends stream
        $this->actingAs($this->owner)
            ->postJson(route('pages.live.end', ['slug' => $this->page->slug, 'streamId' => $streamId]), [
                'host_token' => $hostToken,
            ]);

        // Upload real recording replay file
        $videoFile = UploadedFile::fake()->create('live_recording.mp4', 5000, 'video/mp4');

        $recRes = $this->actingAs($this->owner)
            ->post(route('pages.live.recording', ['slug' => $this->page->slug, 'streamId' => $streamId]), [
                'host_token' => $hostToken,
                'recording_file' => $videoFile,
            ]);

        $recRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $stream = LiveStream::find($streamId);
        $this->assertEquals(LiveStream::RECORDING_STATUS_READY, $stream->recording_status);
        $this->assertNotNull($stream->recording_url);
        $this->assertNotNull($stream->generated_post_id);

        // Verify generated post is attached to the Page
        $post = Post::find($stream->generated_post_id);
        $this->assertNotNull($post);
        $this->assertEquals($this->page->id, $post->page_id);
        $this->assertEquals('video', $post->type);
        $this->assertTrue($post->media()->exists());
    }

    public function test_recording_failure_handles_gracefully_and_does_not_publish_fake_post(): void
    {
        $createRes = $this->actingAs($this->owner)
            ->postJson(route('pages.live.create', ['slug' => $this->page->slug]), [
                'title' => 'Broadcast Without Recording File',
                'privacy' => 'public',
                'record' => true,
            ]);
        $streamId = $createRes->json('stream.id');
        $hostToken = $createRes->json('host_token');

        $this->actingAs($this->owner)
            ->postJson(route('pages.live.end', ['slug' => $this->page->slug, 'streamId' => $streamId]), [
                'host_token' => $hostToken,
            ]);

        // Attempt recording upload with invalid file (not a video)
        $invalidFile = UploadedFile::fake()->create('bad_file.txt', 10, 'text/plain');

        $recRes = $this->actingAs($this->owner)
            ->postJson(route('pages.live.recording', ['slug' => $this->page->slug, 'streamId' => $streamId]), [
                'host_token' => $hostToken,
                'recording_file' => $invalidFile,
            ]);

        $recRes->assertStatus(422);

        $stream = LiveStream::find($streamId);
        $this->assertEquals(LiveStream::RECORDING_STATUS_FAILED, $stream->recording_status);
        $this->assertNull($stream->generated_post_id);
    }

    // ==========================================
    // 8. SHARE VIDEO (DIRECT UPLOAD & PERMISSION-AWARE SHARING)
    // ==========================================

    public function test_manager_can_upload_and_publish_page_video(): void
    {
        $videoFile = UploadedFile::fake()->create('company_showreel.mp4', 8000, 'video/mp4');
        $thumbFile = UploadedFile::fake()->image('showreel_thumb.jpg', 640, 360);

        $response = $this->actingAs($this->manager)
            ->post(route('pages.videos.store', ['slug' => $this->page->slug]), [
                'video_file' => $videoFile,
                'thumbnail' => $thumbFile,
                'caption' => 'Official 2026 Company Showreel #Innovation',
                'audience' => 'public',
                'allow_comments' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'post' => ['id', 'page_id', 'type', 'content'],
                'media' => ['id', 'url'],
            ]);

        $postId = $response->json('post.id');
        $post = Post::find($postId);
        $this->assertNotNull($post);
        $this->assertEquals($this->page->id, $post->page_id);
        $this->assertEquals('video', $post->type);
        $this->assertStringContainsString('Company Showreel', $post->content);
        $this->assertTrue($post->media()->exists());
    }

    public function test_authorized_viewer_can_share_page_video_to_timeline(): void
    {
        // Manager publishes public video
        $videoFile = UploadedFile::fake()->create('public_announcement.mp4', 4000, 'video/mp4');
        $createRes = $this->actingAs($this->manager)
            ->post(route('pages.videos.store', ['slug' => $this->page->slug]), [
                'video_file' => $videoFile,
                'caption' => 'Exciting news for all fans!',
                'audience' => 'public',
            ]);
        $videoPostId = $createRes->json('post.id');

        // Follower shares the video to their personal timeline
        $shareRes = $this->actingAs($this->follower)
            ->postJson(route('pages.videos.share', ['slug' => $this->page->slug, 'videoId' => $videoPostId]), [
                'caption' => 'Check out this awesome announcement from TechCorp!',
            ]);

        $shareRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'share_post' => ['id', 'user_id', 'content']]);

        $sharedPostId = $shareRes->json('share_post.id');
        $sharedPost = Post::find($sharedPostId);
        $this->assertNotNull($sharedPost);
        $this->assertEquals($this->follower->id, $sharedPost->user_id);
        $this->assertEquals($videoPostId, $shareRes->json('share_post.original_post_id'));
        $this->assertDatabaseHas('post_shares', [
            'post_id' => $videoPostId,
            'user_id' => $this->follower->id,
        ]);
    }

    public function test_unauthorized_user_cannot_share_restricted_page_video(): void
    {
        // Manager publishes private "only_me" video
        $videoFile = UploadedFile::fake()->create('internal_confidential.mp4', 4000, 'video/mp4');
        $createRes = $this->actingAs($this->manager)
            ->post(route('pages.videos.store', ['slug' => $this->page->slug]), [
                'video_file' => $videoFile,
                'caption' => 'Confidential internal video',
                'audience' => 'only_me',
            ]);
        $videoPostId = $createRes->json('post.id');

        // Stranger attempts to share private video -> must be 403 Forbidden
        $shareRes = $this->actingAs($this->stranger)
            ->postJson(route('pages.videos.share', ['slug' => $this->page->slug, 'videoId' => $videoPostId]), [
                'caption' => 'Trying to leak private content',
            ]);

        $shareRes->assertStatus(403);
    }
}
