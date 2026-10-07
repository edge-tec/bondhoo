<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\Contracts\MediaProcessingServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * ভিডিও প্রসেসিং ও ট্রান্সকোডিং জব:
 * ব্যাকগ্রাউন্ড কিউতে ভিডিওর পোস্টার থাম্বনেইল, সময়কাল এবং মাল্টি-রেজোলিউশন প্রোফাইল তৈরি করে।
 */
class ProcessVideoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public array $backoff = [15, 45, 90];

    public function __construct(
        public Media $media
    ) {
        $this->onQueue(QueueServiceInterface::QUEUE_MEDIA);
    }

    public function handle(MediaProcessingServiceInterface $processingService): void
    {
        $this->media->update(['processing_status' => 'processing']);

        try {
            // ১. ফাইল মেটাডেটা সংগ্রহ
            $meta = $processingService->extractMetadata($this->media->original_path);

            // ২. ভিডিও ট্রান্সকোডিং ও পোস্টার থাম্বনেইল জেনারেট
            $videoResult = $processingService->processVideo($this->media->original_path, [
                'width' => $meta['width'] ?? 1280,
                'height' => $meta['height'] ?? 720,
            ]);

            // ৩. মিডিয়া রেকর্ড সফলভাবে আপডেট
            $this->media->update([
                'thumbnail_path' => $videoResult['thumbnail_path'],
                'width' => $videoResult['width'],
                'height' => $videoResult['height'],
                'size' => $meta['size'],
                'checksum' => $meta['checksum'],
                'processing_status' => 'ready',
                'metadata' => array_merge($this->media->metadata ?? [], [
                    'duration' => $videoResult['duration'],
                    'resolutions' => $videoResult['resolutions'],
                    'video_processed_at' => now()->toIso8601String(),
                ]),
            ]);
        } catch (Exception $e) {
            Log::error("Failed processing video ID {$this->media->id}: ".$e->getMessage(), [
                'exception' => $e,
            ]);

            $this->media->update([
                'processing_status' => 'failed',
                'metadata' => array_merge($this->media->metadata ?? [], [
                    'error' => $e->getMessage(),
                ]),
            ]);

            throw $e;
        }
    }
}
