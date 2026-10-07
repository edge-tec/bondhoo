<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use App\Services\Media\VideoTranscodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoTranscodingTest extends TestCase
{
    use RefreshDatabase;

    public function test_transcode_job_generates_multi_rendition_ffmpeg_commands(): void
    {
        $user = User::factory()->create();
        $media = Media::create([
            'user_id' => $user->id,
            'disk' => 'local',
            'original_path' => 'videos/raw_input.mp4',
            'original_name' => 'raw_input.mp4',
            'mime_type' => 'video/mp4',
            'size' => 10485760,
            'status' => 'ready',
        ]);

        $transcoder = new VideoTranscodingService;
        $job = $transcoder->generateTranscodeJob($media, ['watermark' => 'Jugajug BD', 'use_gpu' => true]);

        $this->assertSame($media->id, $job['media_id']);
        $this->assertContains('1080p', $job['renditions']);
        $this->assertContains('720p', $job['renditions']);
        $this->assertContains('480p', $job['renditions']);
        $this->assertArrayHasKey('1080p', $job['commands']);
        $this->assertStringContainsString('h264_nvenc', $job['commands']['1080p']);
        $this->assertStringContainsString('master.m3u8', $job['master_playlist']);
    }

    public function test_preview_and_thumbnail_extraction_paths(): void
    {
        $user = User::factory()->create();
        $media = Media::create([
            'user_id' => $user->id,
            'disk' => 'local',
            'original_path' => 'videos/clip.mp4',
            'original_name' => 'clip.mp4',
            'mime_type' => 'video/mp4',
            'size' => 5242880,
            'status' => 'ready',
        ]);

        $transcoder = new VideoTranscodingService;
        $preview = $transcoder->generatePreview($media, 4);
        $this->assertStringEndsWith('_preview.webp', $preview);

        $thumbnails = $transcoder->extractThumbnailKeyframes($media);
        $this->assertCount(4, $thumbnails);

        $audio = $transcoder->extractAudioTrack($media);
        $this->assertStringEndsWith('_audio.wav', $audio);
    }
}
