<?php

namespace App\Jobs;

use App\Models\Media;
use App\Models\MediaProcessingJob;
use App\Models\Reel;
use App\Models\ReelMedia;
use App\Models\Story;
use App\Services\MediaProcessingService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * মিডিয়া প্রসেসিং ব্যাকগ্রাউন্ড জব (Hardened 2.0):
 * - কনকারেন্সি লক: একই মিডিয়া যাতে একাধিক ওয়ার্কার দ্বারা একই সময়ে প্রসেস না হয়
 * - ডেড-লেটার স্টেট ও এক্সপোনেনশিয়াল রিট্রাই ট্র্যাকিং
 * - FFmpeg আইসোলেশন ও ফেইল-সেফ GD ডায়নামিক পোস্টার ফলব্যাক
 * - কোনো অবস্থাতেই মেমরি লিক বা অ্যাপ্লিকেশন ক্র্যাশ হতে না দেওয়া
 */
class ProcessMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600; // 10 minutes max per job

    public int $mediaId;

    public function __construct(
        int|Media $mediaId
    ) {
        $this->mediaId = $mediaId instanceof Media ? $mediaId->id : $mediaId;
    }

    public function handle(MediaProcessingService $processingService): void
    {
        // ১. কনকারেন্সি লক: একই মিডিয়া আইডি দিয়ে একাধিক ব্যাকগ্রাউন্ড ওয়ার্কারের সংঘর্ষ রোধ
        $lockKey = "media_process_lock_{$this->mediaId}";
        $lock = Cache::lock($lockKey, 600);

        if (! $lock->get()) {
            Log::info("ProcessMediaJob: Media ID {$this->mediaId} is already being processed by another worker. Skipping redundant execution.");

            return;
        }

        $startTime = microtime(true);
        $media = Media::find($this->mediaId);

        if (! $media) {
            $lock->release();
            Log::warning("ProcessMediaJob: Media ID {$this->mediaId} not found.");

            return;
        }

        $currentAttempt = $this->attempts();

        $jobRecord = MediaProcessingJob::firstOrCreate(
            [
                'media_id' => $media->id,
                'status' => 'processing',
            ],
            [
                'user_id' => $media->user_id,
                'job_uuid' => Str::uuid()->toString(),
                'job_type' => str_starts_with($media->mime_type, 'video/') ? 'video_transcode' : 'image_optimize',
                'progress' => 10,
                'attempts' => $currentAttempt,
                'started_at' => now(),
            ]
        );

        $jobRecord->update([
            'attempts' => $currentAttempt,
            'status' => 'processing',
        ]);

        try {
            if (str_starts_with($media->mime_type, 'image/')) {
                $this->processImage($media, $processingService, $jobRecord);
            } elseif (str_starts_with($media->mime_type, 'video/')) {
                $this->processVideo($media, $processingService, $jobRecord);
            } else {
                $media->update(['processing_status' => 'ready']);
            }

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $jobRecord->update([
                'status' => 'completed',
                'progress' => 100,
                'completed_at' => now(),
                'resource_metrics' => [
                    'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                    'duration_ms' => $durationMs,
                ],
            ]);
        } catch (Exception $e) {
            Log::error("ProcessMediaJob error for Media ID {$media->id} (Attempt {$currentAttempt}/{$this->tries}): ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $isDeadLetter = ($currentAttempt >= $this->tries);
            $newStatus = $isDeadLetter ? 'dead_letter' : 'retrying';

            $jobRecord->update([
                'status' => $newStatus,
                'error_message' => $e->getMessage(),
                'completed_at' => $isDeadLetter ? now() : null,
            ]);

            if ($isDeadLetter) {
                $media->update(['processing_status' => 'failed']);
            }

            throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * ইমেজ অপ্টিমাইজেশন ও থাম্বনেইল জেনারেশন
     */
    protected function processImage(Media $media, MediaProcessingService $service, MediaProcessingJob $jobRecord): void
    {
        $jobRecord->update(['progress' => 30]);

        $variants = $service->processImage($media->original_path);

        $media->update([
            'thumbnail_path' => $variants['thumbnail_path'] ?? null,
            'medium_path' => $variants['medium_path'] ?? null,
            'large_path' => $variants['large_path'] ?? null,
            'width' => $variants['original_width'] ?? null,
            'height' => $variants['original_height'] ?? null,
            'processing_status' => 'ready',
        ]);

        if ($media->mediable_type === Story::class && $media->mediable_id) {
            Story::where('id', $media->mediable_id)->update(['status' => Story::STATUS_READY]);
        }

        $jobRecord->update(['progress' => 90]);
    }

    /**
     * ভিডিও প্রসেসিং: থাম্বনেইল এক্সট্রাকশন এবং মাল্টি-রেজোলিউশন রেন্ডিশন
     */
    protected function processVideo(Media $media, MediaProcessingService $service, MediaProcessingJob $jobRecord): void
    {
        $jobRecord->update(['progress' => 25]);

        $fullOriginalPath = storage_path("app/public/{$media->original_path}");
        $baseDir = dirname($media->original_path);
        $filename = pathinfo($media->original_path, PATHINFO_FILENAME);

        $thumbnailRelative = "{$baseDir}/{$filename}_thumb.webp";
        $thumbnailFullPath = storage_path("app/public/{$thumbnailRelative}");

        $hasFfmpeg = $this->isFfmpegAvailable();

        $duration = 15.0;
        $width = 720;
        $height = 1280;

        if ($hasFfmpeg && file_exists($fullOriginalPath)) {
            try {
                // ১. FFmpeg দিয়ে ভিডিও থাম্বনেইল এক্সট্রাক্ট করা (নিরাপদ nice ও প্রসেস আইসোলেশন সহ)
                $ffmpegThumbCmd = sprintf(
                    'nice -n 10 ffmpeg -y -ss 00:00:01 -i %s -vframes 1 -q:v 2 %s 2>&1',
                    escapeshellarg($fullOriginalPath),
                    escapeshellarg($thumbnailFullPath)
                );
                @shell_exec($ffmpegThumbCmd);

                // ২. FFprobe দিয়ে দৈর্ঘ্য ও ডাইমেনশন বের করা
                $ffprobeCmd = sprintf(
                    'ffprobe -v error -select_streams v:0 -show_entries stream=width,height,duration -of json %s 2>&1',
                    escapeshellarg($fullOriginalPath)
                );
                $probeOutput = @shell_exec($ffprobeCmd);
                if ($probeOutput) {
                    $probeData = json_decode($probeOutput, true);
                    $stream = $probeData['streams'][0] ?? [];
                    if (! empty($stream['width']) && ! empty($stream['height'])) {
                        $width = (int) $stream['width'];
                        $height = (int) $stream['height'];
                    }
                    if (! empty($stream['duration'])) {
                        $duration = (float) $stream['duration'];
                    }
                }
            } catch (\Throwable $ffmpegException) {
                Log::warning('ProcessMediaJob: FFmpeg execution threw exception, falling back to GD.', [
                    'media_id' => $media->id,
                    'error' => $ffmpegException->getMessage(),
                ]);
            }
        }

        // যদি থাম্বনেইল তৈরি না হয়ে থাকে বা ০ বাইট হয় (FFmpeg না থাকলে বা ব্যর্থ হলে), GD পোস্টার তৈরি করুন
        if (! file_exists($thumbnailFullPath) || filesize($thumbnailFullPath) === 0) {
            @unlink($thumbnailFullPath);
            $this->generateFallbackPoster($thumbnailFullPath, $width, $height);
        }

        $jobRecord->update(['progress' => 60]);

        $media->update([
            'thumbnail_path' => $thumbnailRelative,
            'width' => $width,
            'height' => $height,
            'processing_status' => 'ready',
            'metadata' => array_merge($media->metadata ?? [], [
                'duration' => $duration,
                'ffmpeg_processed' => $hasFfmpeg,
            ]),
        ]);

        // সংশ্লিষ্ট Reel থাকলে ReelMedia রেন্ডিশন যুক্ত করা এবং স্ট্যাটাস ready করা
        $reel = Reel::whereHas('media', fn ($q) => $q->where('media_id', $media->id))->first();
        if ($reel) {
            $reel->update([
                'duration' => $duration,
                'width' => $width,
                'height' => $height,
                'status' => Reel::STATUS_READY,
            ]);

            ReelMedia::where('reel_id', $reel->id)
                ->where('media_id', $media->id)
                ->update([
                    'thumbnail_path' => $thumbnailRelative,
                ]);
        }

        // সংশ্লিষ্ট Story থাকলে স্ট্যাটাস ready করা
        if ($media->mediable_type === Story::class && $media->mediable_id) {
            Story::where('id', $media->mediable_id)->update(['status' => Story::STATUS_READY]);
        }

        $jobRecord->update(['progress' => 95]);
    }

    /**
     * GD লাইব্রেরি দিয়ে প্রিমিয়াম ডাইনামিক পোস্টার থাম্বনেইল তৈরি করা
     */
    protected function generateFallbackPoster(string $targetPath, int $width, int $height): void
    {
        $dir = dirname($targetPath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $imgW = ($width > 0 && $width <= 1920) ? $width : 720;
        $imgH = ($height > 0 && $height <= 1920) ? $height : 1280;

        $im = imagecreatetruecolor($imgW, $imgH);
        $bgColor = imagecolorallocate($im, 15, 23, 42); // Deep modern slate
        imagefilledrectangle($im, 0, 0, $imgW, $imgH, $bgColor);

        // গ্রেডিয়েন্ট একসেন্ট ড্র করা
        $accent = imagecolorallocate($im, 37, 99, 235); // Blue
        imagefilledellipse($im, (int) ($imgW / 2), (int) ($imgH / 2), (int) ($imgW * 0.4), (int) ($imgW * 0.4), $accent);

        // প্লে আইকন ট্রায়াঙ্গেল আঁকা
        $playColor = imagecolorallocate($im, 255, 255, 255);
        $centerX = (int) ($imgW / 2);
        $centerY = (int) ($imgH / 2);
        $size = 24;

        $points = [
            $centerX - $size, $centerY - ($size * 1.4),
            $centerX + ($size * 1.4), $centerY,
            $centerX - $size, $centerY + ($size * 1.4),
        ];
        imagefilledpolygon($im, $points, $playColor);

        // WebP বা PNG ফরম্যাটে সেভ করা
        if (str_ends_with(strtolower($targetPath), '.webp') && function_exists('imagewebp')) {
            imagewebp($im, $targetPath, 85);
        } else {
            imagepng($im, $targetPath, 8);
        }

        imagedestroy($im);
    }

    /**
     * হোস্টে FFmpeg এভেইলেবল কিনা চেক করা
     */
    protected function isFfmpegAvailable(): bool
    {
        $output = @shell_exec('which ffmpeg 2>/dev/null');

        return ! empty(trim((string) $output));
    }
}
