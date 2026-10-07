<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * এন্টারপ্রাইজ রিলস টেস্ট:
 * শর্ট-ফর্ম ভার্টিক্যাল (9:16) ভিডিও আপলোড, কোয়ালিটি রেন্ডিশন, ফিড, ভিউ ও এনগেজমেন্ট।
 */
class ReelEnterpriseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_reel_with_attached_media(): void
    {
        $user = User::factory()->create();

        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => 'uploads/reel/video_vertical.mp4',
            'thumbnail_path' => 'uploads/reel/video_vertical_thumb.webp',
            'mime_type' => 'video/mp4',
            'size' => 5242880,
            'processing_status' => 'ready',
            'metadata' => ['duration' => 28.5],
            'width' => 720,
            'height' => 1280,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/reels', [
                'media_id' => $media->id,
                'caption' => 'প্রাকৃতিক সৌন্দর্যের সাজেক ভ্যালি! #travel #bangladesh #reels',
                'audio_title' => 'Sajek Breeze',
                'audio_artist' => 'Local Artist',
                'privacy' => 'public',
                'allow_comments' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.caption', 'প্রাকৃতিক সৌন্দর্যের সাজেক ভ্যালি! #travel #bangladesh #reels')
            ->assertJsonPath('data.audio_title', 'Sajek Breeze')
            ->assertJsonPath('data.aspect_ratio', '9:16');

        $reelId = $response->json('data.id');

        $this->assertDatabaseHas('reels', [
            'id' => $reelId,
            'user_id' => $user->id,
            'aspect_ratio' => '9:16',
            'status' => 'ready',
        ]);

        $this->assertDatabaseHas('reel_media', [
            'reel_id' => $reelId,
            'media_id' => $media->id,
            'quality' => 'original',
        ]);

        $this->assertEquals(Reel::class, $media->fresh()->mediable_type);
        $this->assertEquals($reelId, $media->fresh()->mediable_id);
    }

    public function test_public_reels_feed_returns_ready_reels(): void
    {
        $user = User::factory()->create();
        $creator = User::factory()->create();

        $media = Media::create([
            'user_id' => $creator->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => 'uploads/reel/ready.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024,
            'processing_status' => 'ready',
        ]);

        $reelReady = Reel::create([
            'user_id' => $creator->id,
            'caption' => 'Public ready reel',
            'aspect_ratio' => '9:16',
            'status' => 'ready',
            'privacy' => 'public',
        ]);

        ReelMedia::create([
            'reel_id' => $reelReady->id,
            'media_id' => $media->id,
            'quality' => 'original',
            'video_path' => 'uploads/reel/ready.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024,
        ]);

        // প্রসেসিং অবস্থায় থাকা রিল (ফিডে আসা উচিত নয়)
        Reel::create([
            'user_id' => $creator->id,
            'caption' => 'Processing reel',
            'aspect_ratio' => '9:16',
            'status' => 'processing',
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/reels/feed');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $reels = $response->json('data');
        $this->assertCount(1, $reels);
        $this->assertEquals('Public ready reel', $reels[0]['caption']);
    }

    public function test_user_can_record_reel_view_with_watch_time(): void
    {
        $creator = User::factory()->create();
        $viewer = User::factory()->create();

        $reel = Reel::create([
            'user_id' => $creator->id,
            'caption' => 'Watch time test',
            'status' => 'ready',
            'privacy' => 'public',
            'views_count' => 0,
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/view", [
                'watch_time_seconds' => 14.5,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.view_recorded', true)
            ->assertJsonPath('data.views_count', 1);

        $this->assertDatabaseHas('reel_views', [
            'reel_id' => $reel->id,
            'user_id' => $viewer->id,
            'watch_time_seconds' => 14.5,
        ]);

        $this->assertEquals(1, $reel->fresh()->views_count);
    }

    public function test_user_can_react_to_reel_and_toggle(): void
    {
        $creator = User::factory()->create();
        $viewer = User::factory()->create();

        $reel = Reel::create([
            'user_id' => $creator->id,
            'caption' => 'Reel reaction test',
            'status' => 'ready',
            'privacy' => 'public',
            'likes_count' => 0,
        ]);

        // ১. লাইক দেওয়া
        $res1 = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/react", [
                'type' => 'like',
            ]);

        $res1->assertStatus(200)
            ->assertJsonPath('data.reacted', true)
            ->assertJsonPath('data.count', 1);

        $this->assertEquals(1, $reel->fresh()->likes_count);

        // ২. পুনরায় লাইক পাঠানো -> আনলাইক হবে
        $res2 = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/reels/{$reel->id}/react", [
                'type' => 'like',
            ]);

        $res2->assertStatus(200)
            ->assertJsonPath('data.reacted', false)
            ->assertJsonPath('data.count', 0);

        $this->assertEquals(0, $reel->fresh()->likes_count);
    }

    public function test_user_can_delete_own_reel_stranger_cannot(): void
    {
        $creator = User::factory()->create();
        $stranger = User::factory()->create();

        $reel = Reel::create([
            'user_id' => $creator->id,
            'caption' => 'Delete test',
            'status' => 'ready',
        ]);

        // Stranger tries to delete -> 403 Forbidden
        $this->actingAs($stranger, 'sanctum')
            ->deleteJson("/api/v2/reels/{$reel->id}")
            ->assertStatus(403);

        // Creator deletes -> 200 OK
        $this->actingAs($creator, 'sanctum')
            ->deleteJson("/api/v2/reels/{$reel->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('reels', ['id' => $reel->id]);
    }
}
