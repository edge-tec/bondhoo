<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\MediaUploadSession;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    public function test_user_can_create_reel_with_comments_enabled_and_duet_enabled_aliases(): void
    {
        $user = User::factory()->create();

        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'reel',
            'disk' => 'public',
            'original_path' => 'uploads/reel/video_vertical_2.mp4',
            'thumbnail_path' => 'uploads/reel/video_vertical_thumb_2.webp',
            'mime_type' => 'video/mp4',
            'size' => 5242880,
            'processing_status' => 'ready',
            'metadata' => ['duration' => 15.0],
            'width' => 720,
            'height' => 1280,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/reels', [
                'media_id' => $media->id,
                'caption' => 'Testing aliases #Bondhoo #Reels',
                'comments_enabled' => 1,
                'duet_enabled' => 0,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('reels', [
            'id' => $response->json('data.id'),
            'allow_comments' => true,
            'allow_duet' => false,
        ]);
    }

    public function test_stale_upload_sessions_auto_cancel_and_prevent_lockout(): void
    {
        $user = User::factory()->create();

        // Create 5 stale sessions older than 20 minutes
        for ($i = 1; $i <= 5; $i++) {
            MediaUploadSession::create([
                'user_id' => $user->id,
                'session_id' => (string) Str::uuid(),
                'collection' => 'reel',
                'filename' => "stale_{$i}.mp4",
                'original_name' => "stale_{$i}.mp4",
                'mime_type' => 'video/mp4',
                'file_size' => 1048576,
                'chunk_size' => 1048576,
                'total_chunks' => 1,
                'uploaded_chunks_count' => 0,
                'status' => 'initialized',
                'temp_dir' => storage_path("app/chunks/test_{$i}"),
                'expires_at' => now()->addHours(24),
            ]);
        }
        MediaUploadSession::where('user_id', $user->id)
            ->update(['updated_at' => now()->subMinutes(25)]);

        // Now initializing a new session must succeed because stale sessions are auto-cancelled
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/uploads/init', [
                'filename' => 'new_fresh_reel.mp4',
                'file_size' => 2097152,
                'mime_type' => 'video/mp4',
                'collection' => 'reel',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        // Previous 5 sessions should now be cancelled
        $this->assertEquals(5, MediaUploadSession::where('user_id', $user->id)->where('status', 'cancelled')->count());
    }
}
