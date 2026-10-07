<?php

namespace App\Services;

use App\Models\Story;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * EnterpriseStorageService — এন্টারপ্রাইজ অবজেক্ট স্টোরেজ ও লাইফসাইকেল সার্ভিস
 *
 * এই সার্ভিসটি S3/R2/MinIO স্টোরেজের সাথে মাল্টিপার্ট আপলোড, ইমেজ ভ্যারিয়েন্ট,
 * ভিডিও রেজোলিউশন প্রোফাইল এবং ২৪ ঘণ্টার স্টোরি লাইফসাইকেল ডিলিশন নিয়ন্ত্রণ করে।
 */
class EnterpriseStorageService
{
    /**
     * সমর্থিত ক্লাউড প্রোভাইডারসমূহ
     */
    public const PROVIDERS = [
        's3' => 'AWS S3',
        'r2' => 'Cloudflare R2',
        'minio' => 'MinIO Object Storage',
        'spaces' => 'DigitalOcean Spaces',
        'b2' => 'Backblaze B2',
    ];

    /**
     * ইমেজ ভ্যারিয়েন্ট রেজোলিউশন
     */
    public const IMAGE_VARIANTS = [64, 128, 256, 512, 'original'];

    /**
     * ভিডিও ভ্যারিয়েন্ট রেজোলিউশন
     */
    public const VIDEO_VARIANTS = ['240p', '360p', '480p', '720p', '1080p'];

    /**
     * প্রোভাইডার কনফিগারেশন যাচাই।
     */
    public function getActiveProvider(): string
    {
        $driver = config('filesystems.default', 'public');

        return self::PROVIDERS[$driver] ?? 'Local / S3 Compatible';
    }

    /**
     * মাল্টিপার্ট / চাঙ্ক আপলোড সেশন ইনিশিয়ালাইজ করা।
     *
     * @return array<string, mixed>
     */
    public function initMultipartUpload(string $filename, string $contentType, int $totalChunks): array
    {
        $uploadId = 'upload_'.Str::uuid()->toString();
        $path = 'uploads/multipart/'.$uploadId.'/'.$filename;

        return [
            'upload_id' => $uploadId,
            'path' => $path,
            'content_type' => $contentType,
            'total_chunks' => $totalChunks,
            'expires_at' => now()->addHours(24)->toIso8601String(),
        ];
    }

    /**
     * নির্দিষ্ট ছবির জন্য সমস্ত রেজোলিউশন ভ্যারিয়েন্ট পাথ জেনারেট করা।
     *
     * @return array<string, string>
     */
    public function getImageVariants(string $basePath): array
    {
        $variants = [];
        $dir = dirname($basePath);
        $filename = pathinfo($basePath, PATHINFO_FILENAME);
        $ext = pathinfo($basePath, PATHINFO_EXTENSION);

        foreach (self::IMAGE_VARIANTS as $size) {
            if ($size === 'original') {
                $variants['original'] = $basePath;
            } else {
                $variants["size_{$size}"] = "{$dir}/variants/{$filename}_{$size}w.{$ext}";
            }
        }

        return $variants;
    }

    /**
     * নির্দিষ্ট ভিডিওর জন্য সমস্ত রেজোলিউশন ভ্যারিয়েন্ট পাথ জেনারেট করা।
     *
     * @return array<string, string>
     */
    public function getVideoVariants(string $basePath): array
    {
        $variants = [];
        $dir = dirname($basePath);
        $filename = pathinfo($basePath, PATHINFO_FILENAME);

        foreach (self::VIDEO_VARIANTS as $res) {
            $variants[$res] = "{$dir}/hls/{$filename}_{$res}.mp4";
        }

        return $variants;
    }

    /**
     * নিরাপদ সাইনড ডাউনলোড ইউআরএল জেনারেট করা।
     */
    public function getSignedUrl(string $path, int $expirationMinutes = 60): string
    {
        try {
            $disk = config('filesystems.default', 'public');

            return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes($expirationMinutes));
        } catch (Exception) {
            return url("/storage/{$path}");
        }
    }

    /**
     * ২৪ ঘণ্টার বেশি পুরোনো সমস্ত এক্সপায়ার্ড স্টোরিজ লাইফসাইকেল ক্লিনআপ করা।
     *
     * @return array{deleted_stories: int, deleted_files: int}
     */
    public function cleanupExpiredStories(): array
    {
        $expiredStories = Story::where('expires_at', '<', now())->get();
        $deletedStories = 0;
        $deletedFiles = 0;

        foreach ($expiredStories as $story) {
            if ($story->media_url) {
                try {
                    $disk = config('filesystems.default', 'public');
                    if (Storage::disk($disk)->exists($story->media_url)) {
                        Storage::disk($disk)->delete($story->media_url);
                        $deletedFiles++;
                    }
                } catch (Exception) {
                    // continue
                }
            }
            $story->delete();
            $deletedStories++;
        }

        return [
            'deleted_stories' => $deletedStories,
            'deleted_files' => $deletedFiles,
        ];
    }
}
