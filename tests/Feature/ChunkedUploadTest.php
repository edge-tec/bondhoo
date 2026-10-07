<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\MediaUploadChunk;
use App\Models\MediaUploadSession;
use App\Models\User;
use App\Models\UserStorageQuota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * চাঙ্কড ও রেজুমেবল আপলোড ফিচার টেস্ট:
 * সেশন ইনিশিয়ালাইজেশন, চাঙ্ক আপলোড, ইন্টিগ্রিটি চেকসাম, স্ট্রিমিং অ্যাসেম্বলি এবং রেজুম টেস্ট।
 */
class ChunkedUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // টেম্পোরারি টেস্ট চাঙ্ক ডিরেক্টরি ক্লিনআপ
        $chunkDir = storage_path('app/chunks');
        if (File::isDirectory($chunkDir)) {
            File::deleteDirectory($chunkDir);
        }

        parent::tearDown();
    }

    public function test_user_can_initialize_chunked_upload_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/uploads/init', [
                'filename' => 'my_travel_vlog.mp4',
                'file_size' => 10485760, // 10 MB
                'mime_type' => 'video/mp4',
                'collection' => 'reel',
                'chunk_size' => 2097152, // 2 MB
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_chunks', 5)
            ->assertJsonPath('data.filename', 'my_travel_vlog.mp4');

        $sessionId = $response->json('data.session_id');
        $this->assertNotEmpty($sessionId);

        $this->assertDatabaseHas('media_upload_sessions', [
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'collection' => 'reel',
            'status' => 'initialized',
            'total_chunks' => 5,
        ]);
    }

    public function test_user_can_upload_chunks_and_system_assembles_file_when_complete(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $chunk1Data = "\x00\x00\x00\x20ftypmp42\x00\x00\x00\x00isommp42Part 1: The journey begins at sunrise. ";
        $chunk2Data = 'Part 2: Arriving at Cox\'s Bazar beach.';
        $fullData = $chunk1Data.$chunk2Data;
        $totalBytes = strlen($fullData);

        // ১. সেশন শুরু করা
        $initRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/uploads/init', [
                'filename' => 'story_video.mp4',
                'file_size' => $totalBytes,
                'mime_type' => 'video/mp4',
                'collection' => 'story_video',
                'chunk_size' => strlen($chunk1Data),
            ]);

        $initRes->assertStatus(201);
        $sessionId = $initRes->json('data.session_id');

        // ২. চাঙ্ক ১ আপলোড
        $chunk1File = UploadedFile::fake()->createWithContent('chunk_1.part', $chunk1Data);
        $chunk1Checksum = hash('sha256', $chunk1Data);

        $res1 = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v2/uploads/{$sessionId}/chunk", [
                'chunk_number' => 1,
                'chunk' => $chunk1File,
                'checksum' => $chunk1Checksum,
            ]);

        $res1->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.chunk_number', 1)
            ->assertJsonPath('data.is_complete', false);

        $this->assertDatabaseHas('media_upload_chunks', [
            'chunk_number' => 1,
            'status' => 'verified',
        ]);

        // ৩. চাঙ্ক ২ আপলোড (চূড়ান্ত চাঙ্ক -> ফাইল অ্যাসেম্বলি ট্রিগার হবে)
        $chunk2File = UploadedFile::fake()->createWithContent('chunk_2.part', $chunk2Data);
        $chunk2Checksum = hash('sha256', $chunk2Data);

        $res2 = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v2/uploads/{$sessionId}/chunk", [
                'chunk_number' => 2,
                'chunk' => $chunk2File,
                'checksum' => $chunk2Checksum,
            ]);

        $res2->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_complete', true)
            ->assertJsonPath('data.media.collection', 'story_video');

        // ডাটাবেজে Media রেকর্ড চেক
        $this->assertDatabaseHas('media', [
            'user_id' => $user->id,
            'collection' => 'story_video',
            'size' => $totalBytes,
        ]);

        // সেশন স্ট্যাটাস সম্পন্ন
        $this->assertDatabaseHas('media_upload_sessions', [
            'session_id' => $sessionId,
            'status' => 'completed',
        ]);
    }

    public function test_user_can_resume_interrupted_upload_session(): void
    {
        $user = User::factory()->create();

        $session = MediaUploadSession::create([
            'user_id' => $user->id,
            'session_id' => 'test-session-resume-123',
            'collection' => 'reel',
            'filename' => 'my_reel.mp4',
            'original_name' => 'my_reel.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => 6000000,
            'chunk_size' => 2000000,
            'total_chunks' => 3,
            'uploaded_chunks_count' => 1,
            'status' => 'uploading',
            'temp_dir' => storage_path('app/chunks/test-session-resume-123'),
            'expires_at' => now()->addHours(24),
        ]);

        MediaUploadChunk::create([
            'upload_session_id' => $session->id,
            'chunk_number' => 1,
            'chunk_size' => 2000000,
            'temp_path' => 'chunk_1.part',
            'status' => 'verified',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v2/uploads/{$session->session_id}/resume");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session_id', 'test-session-resume-123')
            ->assertJsonPath('data.uploaded_chunks', [1])
            ->assertJsonPath('data.next_chunk', 2);
    }

    public function test_user_cannot_exceed_max_video_size_quota(): void
    {
        $user = User::factory()->create();

        // ইউজারের কোটা ৫০ এমবি সেট করা
        UserStorageQuota::create([
            'user_id' => $user->id,
            'tier' => 'free',
            'max_storage_bytes' => 1073741824,
            'used_storage_bytes' => 0,
            'max_video_size_bytes' => 52428800, // 50 MB
            'max_image_size_bytes' => 10485760,
            'max_daily_uploads' => 10,
            'today_uploads_count' => 0,
        ]);

        // ৬০ মেগাবাইটের ভিডিও আপলোড করার চেষ্টা
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/uploads/init', [
                'filename' => 'very_large_movie.mp4',
                'file_size' => 62914560, // 60 MB (> 50 MB)
                'mime_type' => 'video/mp4',
                'collection' => 'reel',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_cleanup_abandoned_uploads_command_removes_expired_sessions(): void
    {
        $user = User::factory()->create();

        $activeSession = MediaUploadSession::create([
            'session_id' => 'active-session-123',
            'user_id' => $user->id,
            'collection' => 'story',
            'filename' => 'active.jpg',
            'original_name' => 'active.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1000,
            'chunk_size' => 1000,
            'total_chunks' => 1,
            'status' => 'uploading',
            'expires_at' => now()->addHours(12),
        ]);

        $expiredSession = MediaUploadSession::create([
            'session_id' => 'expired-session-456',
            'user_id' => $user->id,
            'collection' => 'story',
            'filename' => 'abandoned.jpg',
            'original_name' => 'abandoned.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1000,
            'chunk_size' => 1000,
            'total_chunks' => 1,
            'status' => 'uploading',
            'expires_at' => now()->subHours(2),
        ]);

        $this->artisan('media:cleanup-abandoned')->assertSuccessful();

        $this->assertDatabaseHas('media_upload_sessions', ['id' => $activeSession->id]);
        $this->assertDatabaseMissing('media_upload_sessions', ['id' => $expiredSession->id]);
    }
}
