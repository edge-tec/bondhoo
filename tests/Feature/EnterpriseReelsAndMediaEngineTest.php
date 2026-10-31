<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Reel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseReelsAndMediaEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. Test Reels Feed Retrieval with pagination and creator info.
     */
    public function test_reels_feed_retrieval(): void
    {
        $creator = User::factory()->create(['name' => 'Music Vibes']);
        $viewer = User::factory()->create();

        $media = Media::factory()->create([
            'user_id' => $creator->id,
            'original_path' => 'reels/sample.mp4',
            'mime_type' => 'video/mp4',
        ]);

        Reel::create([
            'user_id' => $creator->id,
            'media_id' => $media->id,
            'caption' => 'Good Music, Good Vibes ❤️',
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'privacy' => 'public',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->actingAs($viewer)->getJson('/api/v2/reels/feed');
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    /**
     * 2. Test React to Reel (Like / Love).
     */
    public function test_react_to_reel(): void
    {
        $creator = User::factory()->create();
        $viewer = User::factory()->create();

        $media = Media::factory()->create([
            'user_id' => $creator->id,
            'original_path' => 'reels/sample.mp4',
            'mime_type' => 'video/mp4',
        ]);

        $reel = Reel::create([
            'user_id' => $creator->id,
            'media_id' => $media->id,
            'caption' => 'Acoustic Solo Session',
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'privacy' => 'public',
            'expires_at' => now()->addHours(24),
        ]);

        $res = $this->actingAs($viewer)->postJson("/api/v2/reels/{$reel->id}/react", [
            'type' => 'love',
        ]);
        $this->assertContains($res->status(), [200, 201]);
    }

    /**
     * 3. Test Record Reel View with duration and completion status.
     */
    public function test_record_reel_view(): void
    {
        $creator = User::factory()->create();
        $viewer = User::factory()->create();

        $media = Media::factory()->create([
            'user_id' => $creator->id,
            'original_path' => 'reels/sample.mp4',
            'mime_type' => 'video/mp4',
        ]);

        $reel = Reel::create([
            'user_id' => $creator->id,
            'media_id' => $media->id,
            'caption' => 'Views test reel',
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'privacy' => 'public',
            'expires_at' => now()->addHours(24),
        ]);

        $res = $this->actingAs($viewer)->postJson("/api/v2/reels/{$reel->id}/view", [
            'duration_seconds' => 15,
            'completed' => true,
        ]);
        $this->assertContains($res->status(), [200, 204]);
    }

    /**
     * 4. Test Save and Share Reel.
     */
    public function test_save_and_share_reel(): void
    {
        $creator = User::factory()->create();
        $viewer = User::factory()->create();

        $media = Media::factory()->create([
            'user_id' => $creator->id,
            'original_path' => 'reels/sample.mp4',
            'mime_type' => 'video/mp4',
        ]);

        $reel = Reel::create([
            'user_id' => $creator->id,
            'media_id' => $media->id,
            'caption' => 'Save and share reel test',
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'privacy' => 'public',
            'expires_at' => now()->addHours(24),
        ]);

        // Save Reel
        $saveRes = $this->actingAs($viewer)->postJson("/api/v2/reels/{$reel->id}/save");
        $saveRes->assertStatus(200);

        // Share Reel
        $shareRes = $this->actingAs($viewer)->postJson("/api/v2/reels/{$reel->id}/share");
        $shareRes->assertStatus(200);
    }

    /**
     * 5. Test Reel Comments: List and Store.
     */
    public function test_reel_comments_lifecycle(): void
    {
        $creator = User::factory()->create();
        $commenter = User::factory()->create();

        $media = Media::factory()->create([
            'user_id' => $creator->id,
            'original_path' => 'reels/sample.mp4',
            'mime_type' => 'video/mp4',
        ]);

        $reel = Reel::create([
            'user_id' => $creator->id,
            'media_id' => $media->id,
            'caption' => 'Comment test reel',
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'privacy' => 'public',
            'expires_at' => now()->addHours(24),
        ]);

        // Add Comment (using 'comment' field)
        $commentRes = $this->actingAs($commenter)->postJson("/api/v2/reels/{$reel->id}/comments", [
            'comment' => 'Amazing guitar tone and vibe!',
        ]);
        $this->assertContains($commentRes->status(), [200, 201]);

        // List Comments
        $listRes = $this->actingAs($commenter)->getJson("/api/v2/reels/{$reel->id}/comments");
        $listRes->assertStatus(200);
    }
}
