<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\Contracts\MediaStorageServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use App\Services\ProfileService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessCoverJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public Media $media,
        public ?array $crop = null
    ) {
        $this->onQueue(QueueServiceInterface::QUEUE_MEDIA);
    }

    public function handle(MediaStorageServiceInterface $storageService, ProfileService $profileService): void
    {
        $this->media->update(['processing_status' => 'processing']);

        try {
            $disk = $this->media->disk;
            $contents = Storage::disk($disk)->get($this->media->original_path);

            if (! $contents) {
                throw new Exception("Original cover photo not found at path: {$this->media->original_path}");
            }

            $sourceImage = @imagecreatefromstring($contents);
            if (! $sourceImage) {
                throw new Exception("Unable to decode cover image at path: {$this->media->original_path}");
            }

            // 1. Apply cropping if specified
            if ($this->crop && isset($this->crop['width'], $this->crop['height'])) {
                $cropX = (int) ($this->crop['x'] ?? 0);
                $cropY = (int) ($this->crop['y'] ?? 0);
                $cropW = (int) $this->crop['width'];
                $cropH = (int) $this->crop['height'];

                $origW = imagesx($sourceImage);
                $origH = imagesy($sourceImage);

                $cropX = max(0, min($cropX, $origW - 1));
                $cropY = max(0, min($cropY, $origH - 1));
                $cropW = min($cropW, $origW - $cropX);
                $cropH = min($cropH, $origH - $cropY);

                if ($cropW > 0 && $cropH > 0) {
                    $cropped = imagecrop($sourceImage, [
                        'x' => $cropX,
                        'y' => $cropY,
                        'width' => $cropW,
                        'height' => $cropH,
                    ]);

                    if ($cropped !== false) {
                        imagedestroy($sourceImage);
                        $sourceImage = $cropped;
                    }
                }
            }

            $srcW = imagesx($sourceImage);
            $srcH = imagesy($sourceImage);

            $baseDir = dirname($this->media->original_path);
            $filenameWithoutExt = pathinfo($this->media->original_path, PATHINFO_FILENAME);

            // 2. Generate Desktop, Mobile and Thumbnail WebP Variants
            $variants = [
                'desktop' => ['max_w' => 1200, 'max_h' => 450],
                'mobile' => ['max_w' => 640, 'max_h' => 360],
                'thumbnail' => ['max_w' => 300, 'max_h' => 120],
            ];

            $paths = [];

            foreach ($variants as $name => $dims) {
                $variantPath = "{$baseDir}/{$filenameWithoutExt}_{$name}.webp";
                $resized = $this->resizeImage($sourceImage, $srcW, $srcH, $dims['max_w'], $dims['max_h']);

                ob_start();
                imagewebp($resized, null, 85);
                $webpData = ob_get_clean();
                imagedestroy($resized);

                Storage::disk($disk)->put($variantPath, $webpData, 'public');
                $paths[$name] = $variantPath;
            }

            imagedestroy($sourceImage);

            // 3. Update Media Record
            $this->media->update([
                'large_path' => $paths['desktop'],
                'medium_path' => $paths['mobile'],
                'thumbnail_path' => $paths['thumbnail'],
                'width' => $srcW,
                'height' => $srcH,
                'processing_status' => 'ready',
            ]);

            // 4. Update Profile cover_url with desktop WebP CDN URL & link cover_media_id
            $user = $this->media->user;
            if ($user && $user->profile) {
                $desktopUrl = $this->media->getVariantUrl('large');
                $user->profile->update([
                    'cover_url' => $desktopUrl ?? $this->media->getVariantUrl('original'),
                    'cover_media_id' => $this->media->id,
                ]);

                $profileService->invalidateProfileCache($user);
            }
        } catch (Exception $e) {
            Log::error("Failed processing cover media ID {$this->media->id}: ".$e->getMessage(), [
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

    /**
     * Proportional resize maintaining aspect ratio with transparency preservation.
     */
    protected function resizeImage($source, int $srcW, int $srcH, int $maxW, int $maxH)
    {
        $ratio = min($maxW / $srcW, $maxH / $srcH);

        if ($ratio >= 1.0) {
            $newW = $srcW;
            $newH = $srcH;
        } else {
            $newW = (int) round($srcW * $ratio);
            $newH = (int) round($srcH * $ratio);
        }

        $dst = imagecreatetruecolor($newW, $newH);

        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        imagecopyresampled($dst, $source, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        return $dst;
    }
}
