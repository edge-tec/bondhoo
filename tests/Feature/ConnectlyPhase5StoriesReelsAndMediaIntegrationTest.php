<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\Story;
use App\Models\StoryMedia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConnectlyPhase5StoriesReelsAndMediaIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        $chunkDir = storage_path('app/chunks');
        if (File::isDirectory($chunkDir)) {
            File::deleteDirectory($chunkDir);
        }
        parent::tearDown();
    }

    protected function createSampleMedia(User $user, string $collection = 'story'): Media
    {
        return Media::create([
            'user_id' => $user->id,
            'collection' => $collection,
            'disk' => 'public',
            'original_path' => "uploads/{$collection}_sample.mp4",
            'thumbnail_path' => "uploads/{$collection}_thumb.webp",
            'mime_type' => 'video/mp4',
            'size' => 2048000,
            'processing_status' => 'ready',
            'width' => 1080,
            'height' => 1920,
            'metadata' => ['duration' => 15.0],
        ]);
    }

    /**
     * 1. Stories: Photo and Video story creation with privacy controls.
     */
    public function test_story_creation_with_privacy_controls(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $media = $this->createSampleMedia($user, 'story');

        // Create media story with 'friends' privacy
        $response = $this->postJson('/api/v2/stories', [
            'type' => 'media',
            'content' => 'Exploring beautiful Cox Bazar beach! 🌊',
            'privacy' => 'friends',
            'media_ids' => [$media->id],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.privacy', 'friends')
            ->assertJsonPath('data.type', 'media');

        $storyId = $response->json('data.id');
        $this->assertDatabaseHas('stories', [
            'id' => $storyId,
            'user_id' => $user->id,
            'privacy' => 'friends',
            'status' => 'ready',
        ]);
    }

    /**
     * 2. Stories: Active feed, view tracking, and viewers list.
     */
    public function test_stories_feed_view_recording_and_viewers(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $media = $this->createSampleMedia($author, 'story');

        $story = Story::create([
            'user_id' => $author->id,
            'type' => 'media',
            'content' => 'Sunset at Padma bridge',
            'privacy' => 'public',
            'status' => 'ready',
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'views_count' => 0,
        ]);
        $media->update([
            'mediable_type' => Story::class,
            'mediable_id' => $story->id,
            'collection' => 'story',
        ]);
        StoryMedia::create([
            'story_id' => $story->id,
            'media_id' => $media->id,
            'order' => 0,
            'duration' => 15,
        ]);

        // Viewer retrieves active feed
        Sanctum::actingAs($viewer);
        $feedRes = $this->getJson('/api/v2/stories/feed');
        $feedRes->assertOk()
            ->assertJsonPath('success', true);

        // Viewer records view
        $viewRes = $this->postJson("/api/v2/stories/{$story->id}/view");
        $viewRes->assertOk();

        $this->assertDatabaseHas('story_views', [
            'story_id' => $story->id,
            'user_id' => $viewer->id,
        ]);
        $this->assertEquals(1, $story->fresh()->views_count);

        // Author inspects viewers
        Sanctum::actingAs($author);
        $viewersRes = $this->getJson("/api/v2/stories/{$story->id}/viewers");
        $viewersRes->assertOk()
            ->assertJsonPath('success', true);

        $viewerIds = collect($viewersRes->json('data'))->pluck('user.id')->all();
        $this->assertContains($viewer->id, $viewerIds);
    }

    /**
     * 3. Stories: Reactions and Direct Replies.
     */
    public function test_story_reactions_and_replies(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        $story = Story::create([
            'user_id' => $author->id,
            'type' => 'text',
            'content' => 'Inspiring day with technology! 💡',
            'privacy' => 'public',
            'status' => 'ready',
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        Sanctum::actingAs($viewer);

        // React with 'love'
        $reactRes = $this->postJson("/api/v2/stories/{$story->id}/react", [
            'type' => 'love',
        ]);
        $reactRes->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('story_reactions', [
            'story_id' => $story->id,
            'user_id' => $viewer->id,
            'type' => 'love',
        ]);

        // Direct Reply to story
        $replyRes = $this->postJson("/api/v2/stories/{$story->id}/reply", [
            'message' => 'Totally agree with you brother!',
        ]);
        $replyRes->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    /**
     * 4. Stories: 24-hour expiration lifecycle and deletion.
     */
    public function test_story_expiration_and_deletion(): void
    {
        $author = User::factory()->create();
        $story = Story::create([
            'user_id' => $author->id,
            'type' => 'text',
            'content' => 'Story about to expire',
            'privacy' => 'public',
            'status' => 'ready',
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        $this->assertFalse($story->isExpired());
        $this->assertTrue($story->isActive());

        // Author deletes story
        Sanctum::actingAs($author);
        $deleteRes = $this->deleteJson("/api/v2/stories/{$story->id}");
        $deleteRes->assertOk();

        $this->assertDatabaseMissing('stories', ['id' => $story->id]);
    }

    /**
     * 5. Reels: Creation, 9:16 layout, and feed retrieval.
     */
    public function test_reels_creation_and_feed_retrieval(): void
    {
        $creator = User::factory()->create();
        Sanctum::actingAs($creator);
        $videoMedia = $this->createSampleMedia($creator, 'reel');

        $response = $this->postJson('/api/v2/reels', [
            'media_id' => $videoMedia->id,
            'caption' => 'Experience the natural beauty of Sylhet tea gardens! 🍵 #reels',
            'audio_title' => 'Tea Garden Folk Acoustic',
            'audio_artist' => $creator->name,
            'privacy' => 'public',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $reelId = $response->json('data.id');
        $reel = Reel::findOrFail($reelId);
        $this->assertEquals(Reel::STATUS_READY, $reel->status);
        $this->assertEquals('9:16', $reel->aspect_ratio);

        // Fetch Reels feed
        $feedRes = $this->getJson('/api/v2/reels/feed?per_page=5');
        $feedRes->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'user',
                        'caption',
                        'audio_title',
                        'audio_artist',
                        'duration',
                        'views_count',
                        'likes_count',
                        'comments_count',
                    ],
                ],
            ]);
    }

    /**
     * 6. Reels: Views counter, liking, commenting, and bookmarking.
     */
    public function test_reels_interactions_view_like_comment_save(): void
    {
        $creator = User::factory()->create();
        $viewer = User::factory()->create();
        $videoMedia = $this->createSampleMedia($creator, 'reel');

        $reel = Reel::create([
            'user_id' => $creator->id,
            'caption' => 'Amazing drone shot of Dhaka metro rail 🚆',
            'status' => Reel::STATUS_READY,
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'duration' => 15,
            'width' => 1080,
            'height' => 1920,
            'aspect_ratio' => '9:16',
            'views_count' => 0,
            'likes_count' => 0,
            'comments_count' => 0,
            'shares_count' => 0,
            'saves_count' => 0,
        ]);
        ReelMedia::create([
            'reel_id' => $reel->id,
            'media_id' => $videoMedia->id,
            'quality' => 'original',
            'video_path' => $videoMedia->original_path,
            'thumbnail_path' => $videoMedia->thumbnail_path,
            'mime_type' => 'video/mp4',
            'size' => 2048000,
        ]);

        Sanctum::actingAs($viewer);

        // 1. Record Reel view
        $viewRes = $this->postJson("/api/v2/reels/{$reel->id}/view", [
            'duration_seconds' => 12,
            'completed' => true,
        ]);
        $viewRes->assertOk();
        $this->assertEquals(1, $reel->fresh()->views_count);

        // 2. Like Reel
        $likeRes = $this->postJson("/api/v2/reels/{$reel->id}/react", [
            'type' => 'like',
        ]);
        $likeRes->assertOk();
        $this->assertEquals(1, $reel->fresh()->likes_count);

        // 3. Comment on Reel
        $commentRes = $this->postJson("/api/v2/reels/{$reel->id}/comments", [
            'comment' => 'Metro rail looks futuristic! Great angle!',
        ]);
        $commentRes->assertStatus(201);
        $this->assertEquals(1, $reel->fresh()->comments_count);

        // 4. Save Reel (Bookmark)
        $saveRes = $this->postJson("/api/v2/reels/{$reel->id}/save");
        $saveRes->assertOk();
        $this->assertEquals(1, $reel->fresh()->saves_count);

        // 5. Share Reel
        $shareRes = $this->postJson("/api/v2/reels/{$reel->id}/share");
        $shareRes->assertOk();
        $this->assertEquals(1, $reel->fresh()->shares_count);
    }

    /**
     * 7. Media: Enterprise Chunked & Resumable Upload Lifecycle.
     */
    public function test_enterprise_chunked_upload_lifecycle(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // 1. Init session (5MB video with 2MB chunks = 3 chunks)
        $fileSize = 5242880;
        $chunkSize = 2097152;
        $initRes = $this->postJson('/api/v2/uploads/init', [
            'filename' => 'connectly_reel_upload.mp4',
            'file_size' => $fileSize,
            'mime_type' => 'video/mp4',
            'collection' => 'reel',
            'chunk_size' => $chunkSize,
        ]);

        $initRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $sessionId = $initRes->json('data.session_id');
        $this->assertNotEmpty($sessionId);

        // 2. Upload Chunk 1
        $chunk1 = UploadedFile::fake()->create('chunk1.part', 2048); // 2MB
        $chunkRes = $this->postJson('/api/v2/uploads/chunks', [
            'session_id' => $sessionId,
            'chunk_number' => 1,
            'chunk' => $chunk1,
        ]);
        $chunkRes->assertOk()
            ->assertJsonPath('success', true);

        // 3. Check Resume Status
        $resumeRes = $this->getJson("/api/v2/uploads/{$sessionId}/resume");
        $resumeRes->assertOk()
            ->assertJsonPath('success', true);

        $uploadedChunks = $resumeRes->json('data.uploaded_chunks');
        $this->assertContains(1, $uploadedChunks);

        // 4. Cancel upload session
        $cancelRes = $this->deleteJson("/api/v2/uploads/sessions/{$sessionId}");
        $cancelRes->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('media_upload_sessions', [
            'session_id' => $sessionId,
            'status' => 'cancelled',
        ]);
    }

    /**
     * 8. Cross-platform Media Consistency: Verification that created content formats properly for web & mobile.
     */
    public function test_cross_platform_media_formatting_consistency(): void
    {
        $user = User::factory()->create();
        $media = $this->createSampleMedia($user, 'reel');

        $reel = Reel::create([
            'user_id' => $user->id,
            'caption' => 'Cross-platform check',
            'status' => Reel::STATUS_READY,
            'published_at' => now(),
            'expires_at' => now()->addHours(24),
            'duration' => 15,
            'width' => 1080,
            'height' => 1920,
            'aspect_ratio' => '9:16',
        ]);
        ReelMedia::create([
            'reel_id' => $reel->id,
            'media_id' => $media->id,
            'quality' => 'original',
            'video_path' => $media->original_path,
            'thumbnail_path' => $media->thumbnail_path,
            'mime_type' => 'video/mp4',
            'size' => 2048000,
        ]);

        $array = $reel->toResponseArray($user);

        // Verify keys needed by mobile Media3 player and web video elements
        $this->assertArrayHasKey('video_url', $array);
        $this->assertArrayHasKey('thumbnail_url', $array);
        $this->assertArrayHasKey('renditions', $array);
        $this->assertArrayHasKey('user', $array);
        $this->assertEquals($user->name, $array['user']['name']);
    }
}
