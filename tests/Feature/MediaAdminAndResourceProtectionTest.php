<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\MediaSystemSetting;
use App\Models\MediaUploadSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * মিডিয়া সিস্টেম অ্যাডমিন ও রিসোর্স প্রোটেকশন টেস্ট:
 * সার্ভার ক্যাপাসিটি, মেমরি ওভারলোড প্রতিরোধ, ইউজার কোটা ম্যানেজমেন্ট এবং পরিত্যক্ত সেশন ক্লিনআপ।
 */
class MediaAdminAndResourceProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_retrieve_media_and_server_metrics(): void
    {
        $admin = User::factory()->create();

        Media::create([
            'user_id' => $admin->id,
            'collection' => 'general',
            'disk' => 'public',
            'original_path' => 'uploads/test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'processing_status' => 'ready',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v2/admin/media/metrics');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_media_count', 1)
            ->assertJsonStructure([
                'data' => [
                    'total_media_count',
                    'total_storage_mb',
                    'active_upload_sessions',
                    'processing_jobs',
                    'server_resources' => [
                        'disk_free_gb',
                        'memory_usage_mb',
                    ],
                    'settings',
                ],
            ]);
    }

    public function test_admin_can_update_media_system_settings(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v2/admin/media/settings', [
                'max_video_size_mb' => 200,
                'max_image_size_mb' => 30,
                'max_concurrent_workers' => 6,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(200, MediaSystemSetting::get('max_video_size_mb'));
        $this->assertEquals(30, MediaSystemSetting::get('max_image_size_mb'));
        $this->assertEquals(6, MediaSystemSetting::get('max_concurrent_workers'));
    }

    public function test_admin_can_update_user_storage_quota(): void
    {
        $admin = User::factory()->create();
        $targetUser = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v2/admin/media/users/{$targetUser->id}/quota", [
                'tier' => 'pro',
                'max_storage_mb' => 5120, // 5 GB
                'max_video_size_mb' => 500,
                'is_unlimited' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tier', 'pro')
            ->assertJsonPath('data.is_unlimited', true);

        $this->assertDatabaseHas('user_storage_quotas', [
            'user_id' => $targetUser->id,
            'tier' => 'pro',
            'is_unlimited' => true,
        ]);
    }

    public function test_cleanup_abandoned_uploads_command_deletes_expired_sessions(): void
    {
        $user = User::factory()->create();

        $tempDir = storage_path('app/chunks/test-abandoned-session');
        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        file_put_contents("{$tempDir}/chunk_1.part", 'test data');

        $session = MediaUploadSession::create([
            'user_id' => $user->id,
            'session_id' => 'test-abandoned-session',
            'collection' => 'reel',
            'filename' => 'abandoned.mp4',
            'original_name' => 'abandoned.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => 1000,
            'chunk_size' => 500,
            'total_chunks' => 2,
            'status' => 'uploading',
            'temp_dir' => $tempDir,
            'expires_at' => now()->subHours(2), // Expired 2 hours ago
        ]);

        $this->artisan('media:cleanup-abandoned')->assertSuccessful();

        $this->assertDatabaseMissing('media_upload_sessions', ['id' => $session->id]);
        $this->assertFalse(File::isDirectory($tempDir));
    }
}
