<?php

namespace Tests\Unit;

use App\Jobs\ProcessVideoJob;
use App\Models\Media;
use App\Models\User;
use App\Services\Contracts\MediaProcessingServiceInterface;
use App\Services\Contracts\MediaStorageServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ভিডিও প্রসেসিং ইউনিট টেস্ট:
 * ভিডিও থেকে পোস্টার থাম্বনেইল তৈরি এবং মাল্টি-রেজোলিউশন প্রোফাইল জেনারেট টেস্ট।
 */
class VideoProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_processing_service_generates_video_poster_and_resolutions(): void
    {
        $service = app(MediaProcessingServiceInterface::class);
        $storage = app(MediaStorageServiceInterface::class);

        // Put fake sample video in storage
        $videoPath = 'videos/sample_clip.mp4';
        Storage::disk($storage->getDisk())->put($videoPath, 'fake-mp4-video-content-stream');

        $result = $service->processVideo($videoPath, [
            'duration' => 45,
            'width' => 1920,
            'height' => 1080,
        ]);

        $this->assertEquals('ready', $result['status']);
        $this->assertNotEmpty($result['thumbnail_path']);
        $this->assertTrue(Storage::disk($storage->getDisk())->exists($result['thumbnail_path']));
        $this->assertEquals(45, $result['duration']);
        $this->assertArrayHasKey('720p', $result['resolutions']);
        $this->assertArrayHasKey('1080p', $result['resolutions']);
    }

    public function test_process_video_job_updates_media_status_to_ready(): void
    {
        $user = User::factory()->create();
        $storage = app(MediaStorageServiceInterface::class);

        $videoPath = 'videos/test_upload.mp4';
        Storage::disk($storage->getDisk())->put($videoPath, 'fake-binary-video-stream');

        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'video',
            'disk' => $storage->getDisk(),
            'original_path' => $videoPath,
            'mime_type' => 'video/mp4',
            'size' => 1048576,
            'processing_status' => 'pending',
        ]);

        $job = new ProcessVideoJob($media);
        $job->handle(app(MediaProcessingServiceInterface::class));

        $fresh = $media->fresh();
        $this->assertEquals('ready', $fresh->processing_status);
        $this->assertNotNull($fresh->thumbnail_path);
        $this->assertNotNull($fresh->metadata['resolutions']);
        $this->assertEquals(15, $fresh->metadata['duration']);
    }
}
