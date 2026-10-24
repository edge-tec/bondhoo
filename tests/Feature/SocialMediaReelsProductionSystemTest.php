<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Social Media Reels Production System Verification Suite
 * Verifies professional standards comparable to Facebook and Instagram Reels.
 */
class SocialMediaReelsProductionSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $creator;

    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->creator = User::factory()->create([
            'name' => 'Tanvir Ahmed',
            'username' => 'tanvirahmed',
        ]);

        $this->viewer = User::factory()->create([
            'name' => 'Nusrat Jahan',
            'username' => 'nusratjahan',
        ]);
    }

    /**
     * Helper to create a fully initialized test reel
     */
    protected function createTestReel(User $user, array $overrides = []): Reel
    {
        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => 'uploads/reels/video_'.$user->id.'_'.uniqid().'.mp4',
            'mime_type' => 'video/mp4',
            'size' => 4500000,
            'processing_status' => 'ready',
        ]);

        $reel = Reel::create(array_merge([
            'user_id' => $user->id,
            'caption' => 'Exploring Coxs Bazar Beach 🌊 #travel #bangladesh #reels',
            'audio_title' => 'Original Audio - Tanvir Ahmed',
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'privacy' => 'public',
            'allow_comments' => true,
            'allow_duet' => true,
            'views_count' => 1500,
            'likes_count' => 120,
            'comments_count' => 15,
            'shares_count' => 8,
            'saves_count' => 25,
        ], $overrides));

        ReelMedia::create([
            'reel_id' => $reel->id,
            'media_id' => $media->id,
            'quality' => 'original',
            'video_path' => $media->original_path,
            'thumbnail_path' => 'uploads/reels/thumb_'.uniqid().'.jpg',
            'mime_type' => 'video/mp4',
            'size' => 4500000,
        ]);

        return $reel;
    }

    /**
     * 1. Test dedicated /reels page loads with Inertia and delivers active reels feed
     */
    public function test_reels_page_delivers_active_reels_feed(): void
    {
        $reel1 = $this->createTestReel($this->creator, ['caption' => 'First Travel Reel #travel']);
        $reel2 = $this->createTestReel($this->creator, ['caption' => 'Second Dhaka City Reel #dhaka']);

        $response = $this->actingAs($this->viewer)->get('/reels');
        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reels')
            ->has('reels.data', 2)
            ->where('activeTab', 'for_you')
            ->where('reels.data.0.caption', 'Second Dhaka City Reel #dhaka')
            ->where('reels.data.1.caption', 'First Travel Reel #travel')
        );
    }

    /**
     * 2. Test /reels/{id} highlights and displays the requested specific reel
     */
    public function test_reels_show_highlights_requested_reel(): void
    {
        $reel1 = $this->createTestReel($this->creator, ['caption' => 'Reel A']);
        $reel2 = $this->createTestReel($this->creator, ['caption' => 'Reel B']);

        $response = $this->actingAs($this->viewer)->get("/reels/{$reel1->id}");
        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reels')
            ->where('initialReelId', $reel1->id)
            ->where('reels.data.0.id', $reel1->id)
            ->where('reels.data.0.caption', 'Reel A')
        );
    }

    /**
     * 3. Test /reels tab filters (my_reels, saved)
     */
    public function test_reels_page_tab_filtering_my_reels_and_saved(): void
    {
        $creatorReel = $this->createTestReel($this->creator, ['caption' => 'Creator Own Reel']);
        $viewerReel = $this->createTestReel($this->viewer, ['caption' => 'Viewer Own Reel']);

        // Check My Reels tab
        $myReelsRes = $this->actingAs($this->viewer)->get('/reels?tab=my_reels');
        $myReelsRes->assertOk();
        $myReelsRes->assertInertia(fn (Assert $page) => $page
            ->component('Reels')
            ->where('activeTab', 'my_reels')
            ->has('reels.data', 1)
            ->where('reels.data.0.caption', 'Viewer Own Reel')
        );

        // Viewer saves creator reel
        $this->actingAs($this->viewer, 'sanctum')->postJson("/api/v2/reels/{$creatorReel->id}/save");

        // Check Saved tab
        $savedRes = $this->actingAs($this->viewer)->get('/reels?tab=saved');
        $savedRes->assertOk();
        $savedRes->assertInertia(fn (Assert $page) => $page
            ->component('Reels')
            ->where('activeTab', 'saved')
            ->has('reels.data', 1)
            ->where('reels.data.0.id', $creatorReel->id)
        );
    }

    /**
     * 4. Test uploading, draft saving, and publishing workflow
     */
    public function test_reels_upload_and_publishing_workflow(): void
    {
        $videoFile = UploadedFile::fake()->create('beach_waves.mp4', 8000, 'video/mp4');

        // Step 1: Upload media via /api/v1/media/upload
        $uploadRes = $this->actingAs($this->creator, 'sanctum')
            ->postJson('/api/v1/media/upload', [
                'file' => $videoFile,
                'collection' => 'reel',
            ]);

        $uploadRes->assertStatus(201);
        $mediaId = $uploadRes->json('data.id') ?? $uploadRes->json('data.media_id');
        $this->assertNotNull($mediaId);

        // Step 2: Publish Reel via /api/v2/reels
        $publishRes = $this->actingAs($this->creator, 'sanctum')
            ->postJson('/api/v2/reels', [
                'media_id' => $mediaId,
                'caption' => 'Sundarbans Wild Tour! #wildlife #bangladesh',
                'audio_title' => 'Sundarbans Ambience',
                'privacy' => 'public',
                'allow_comments' => true,
                'allow_duet' => true,
                'is_draft' => false,
            ]);

        $publishRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.caption', 'Sundarbans Wild Tour! #wildlife #bangladesh')
            ->assertJsonPath('data.audio_title', 'Sundarbans Ambience');

        $reelId = $publishRes->json('data.id');
        $this->assertDatabaseHas('reels', [
            'id' => $reelId,
            'user_id' => $this->creator->id,
            'caption' => 'Sundarbans Wild Tour! #wildlife #bangladesh',
            'is_draft' => false,
        ]);
    }

    /**
     * 5. Test complete Reel interactions (React/Like, Comment, Delete Comment, Like Comment, Save, Share, View, Report)
     */
    public function test_reels_complete_interaction_suite(): void
    {
        $reel = $this->createTestReel($this->creator);

        // 1. Record Watch View
        $viewRes = $this->actingAs($this->viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/view", [
                'watch_time_seconds' => 5.5,
            ]);
        $viewRes->assertOk()
            ->assertJsonPath('success', true);

        // 2. React / Like
        $likeRes = $this->actingAs($this->viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/react", [
                'type' => 'love',
            ]);
        $likeRes->assertOk()
            ->assertJsonPath('success', true);

        // 3. Post Comment
        $commentRes = $this->actingAs($this->viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/comments", [
                'comment' => 'Incredible cinematography and sound quality! 🔥',
            ]);
        $commentRes->assertStatus(201)
            ->assertJsonPath('success', true);
        $commentId = $commentRes->json('data.id');
        $this->assertNotNull($commentId);

        // 4. Like Comment
        $likeCommentRes = $this->actingAs($this->viewer, 'sanctum')
            ->postJson("/api/v2/reels/comments/{$commentId}/like");
        $likeCommentRes->assertOk()
            ->assertJsonPath('success', true);

        // 5. Delete Comment
        $deleteCommentRes = $this->actingAs($this->viewer, 'sanctum')
            ->deleteJson("/api/v2/reels/comments/{$commentId}");
        $deleteCommentRes->assertOk()
            ->assertJsonPath('success', true);

        // 6. Toggle Save Bookmark
        $saveRes = $this->actingAs($this->viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/save");
        $saveRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.saved', true);

        // 7. Record Share
        $shareRes = $this->actingAs($this->viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/share", [
                'platform' => 'clipboard',
            ]);
        $shareRes->assertOk()
            ->assertJsonPath('success', true);

        // 8. Submit Report
        $reportRes = $this->actingAs($this->viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/report", [
                'reason' => 'spam',
                'details' => 'Promotional link spam',
            ]);
        $reportRes->assertOk()
            ->assertJsonPath('success', true);
    }

    /**
     * 6. Test unauthenticated guest access is redirected to login
     */
    public function test_guest_is_redirected_to_login_from_reels(): void
    {
        $response = $this->get('/reels');
        $response->assertRedirect('/login');
    }
}
