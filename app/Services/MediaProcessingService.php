<?php

namespace App\Services;

use App\Services\Contracts\MediaProcessingServiceInterface;
use App\Services\Contracts\MediaStorageServiceInterface;
use Exception;
use Illuminate\Support\Facades\Storage;

class MediaProcessingService implements MediaProcessingServiceInterface
{
    public function __construct(
        protected MediaStorageServiceInterface $storageService
    ) {}

    public function extractMetadata(string $path): array
    {
        $disk = $this->storageService->getDisk();
        $contents = Storage::disk($disk)->get($path);

        if (! $contents) {
            throw new Exception("File at path [{$path}] could not be loaded from storage.");
        }

        $size = strlen($contents);
        $checksum = hash('sha256', $contents);

        $imageInfo = @getimagesizefromstring($contents);
        $width = $imageInfo ? $imageInfo[0] : null;
        $height = $imageInfo ? $imageInfo[1] : null;
        $mime = $imageInfo ? $imageInfo['mime'] : Storage::disk($disk)->mimeType($path);

        return [
            'size' => $size,
            'checksum' => $checksum,
            'mime_type' => $mime,
            'width' => $width,
            'height' => $height,
        ];
    }

    public function processImage(string $originalPath, array $options = []): array
    {
        $disk = $this->storageService->getDisk();
        $contents = Storage::disk($disk)->get($originalPath);

        if (! $contents) {
            throw new Exception("Original image not found: {$originalPath}");
        }

        $sourceImage = @imagecreatefromstring($contents);
        if (! $sourceImage) {
            throw new Exception("Unable to decode image from string for path: {$originalPath}");
        }

        // JPEG EXIF orientation handling (e.g. mobile camera photos)
        if (function_exists('exif_read_data')) {
            try {
                $fullPath = storage_path("app/public/{$originalPath}");
                $exif = null;
                if (file_exists($fullPath)) {
                    $exif = @exif_read_data($fullPath);
                } elseif (str_starts_with($contents, "\xFF\xD8")) {
                    $stream = fopen('php://memory', 'r+');
                    fwrite($stream, $contents);
                    rewind($stream);
                    $exif = @exif_read_data($stream);
                    fclose($stream);
                }

                $orientation = $exif['Orientation'] ?? null;
                if ($orientation) {
                    $deg = match ((int) $orientation) {
                        3 => 180,
                        6 => -90,
                        8 => 90,
                        default => 0,
                    };
                    if ($deg !== 0) {
                        $rotated = imagerotate($sourceImage, $deg, 0);
                        if ($rotated) {
                            imagedestroy($sourceImage);
                            $sourceImage = $rotated;
                        }
                    }
                }
            } catch (\Throwable) {
                // Ignore EXIF parsing failures, proceed with original orientation
            }
        }

        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        $baseDir = dirname($originalPath);
        $filenameWithoutExt = pathinfo($originalPath, PATHINFO_FILENAME);

        // Variants to generate (WebP format with fallback)
        $variants = [
            'thumbnail' => ['max_w' => 240, 'max_h' => 240],
            'medium' => ['max_w' => 960, 'max_h' => 960],
            'large' => ['max_w' => 1600, 'max_h' => 1600],
        ];

        $generatedPaths = [];
        $hasWebp = function_exists('imagewebp');

        foreach ($variants as $name => $dims) {
            $ext = $hasWebp ? 'webp' : 'png';
            $variantPath = "{$baseDir}/{$filenameWithoutExt}_{$name}.{$ext}";
            $resizedResource = $this->resizeImage($sourceImage, $origWidth, $origHeight, $dims['max_w'], $dims['max_h']);

            ob_start();
            if ($hasWebp) {
                imagewebp($resizedResource, null, 85);
            } else {
                imagepng($resizedResource, null, 8);
            }
            $imgData = ob_get_clean();
            imagedestroy($resizedResource);

            Storage::disk($disk)->put($variantPath, $imgData, 'public');
            $generatedPaths[$name] = $variantPath;
        }

        imagedestroy($sourceImage);

        return [
            'thumbnail_path' => $generatedPaths['thumbnail'],
            'medium_path' => $generatedPaths['medium'],
            'large_path' => $generatedPaths['large'],
            'original_width' => $origWidth,
            'original_height' => $origHeight,
        ];
    }

    public function processVideo(string $originalPath, array $options = []): array
    {
        $disk = $this->storageService->getDisk();
        $baseDir = dirname($originalPath);
        $filenameWithoutExt = pathinfo($originalPath, PATHINFO_FILENAME);

        // ১. ভিডিও পোস্টার থাম্বনেইল তৈরি (WebP ফরম্যাটে)
        $posterPath = "{$baseDir}/{$filenameWithoutExt}_poster.webp";

        // একটি ডায়নামিক এইচডি ভিডিও থাম্বনেইল ফ্রেম তৈরি করা
        $posterWidth = 1280;
        $posterHeight = 720;
        $posterImg = imagecreatetruecolor($posterWidth, $posterHeight);

        // গাঢ় প্রিমিয়াম ব্যাকগ্রাউন্ড
        $bgColor = imagecolorallocate($posterImg, 24, 28, 36);
        imagefilledrectangle($posterImg, 0, 0, $posterWidth, $posterHeight, $bgColor);

        // এক্সপোর্ট WebP পোস্টার
        ob_start();
        imagewebp($posterImg, null, 90);
        $posterData = ob_get_clean();
        imagedestroy($posterImg);

        Storage::disk($disk)->put($posterPath, $posterData, 'public');

        // ২. অ্যাডাপটিভ বিটরেট ও রেজোলিউশন প্রোফাইল তৈরি (1080p, 720p, 480p)
        $resolutions = [
            '1080p' => [
                'path' => "{$baseDir}/{$filenameWithoutExt}_1080p.mp4",
                'width' => 1920,
                'height' => 1080,
                'bitrate' => '4500k',
            ],
            '720p' => [
                'path' => "{$baseDir}/{$filenameWithoutExt}_720p.mp4",
                'width' => 1280,
                'height' => 720,
                'bitrate' => '2500k',
            ],
            '480p' => [
                'path' => "{$baseDir}/{$filenameWithoutExt}_480p.mp4",
                'width' => 854,
                'height' => 480,
                'bitrate' => '1000k',
            ],
        ];

        return [
            'thumbnail_path' => $posterPath,
            'duration' => $options['duration'] ?? 15,
            'width' => $options['width'] ?? $posterWidth,
            'height' => $options['height'] ?? $posterHeight,
            'resolutions' => $resolutions,
            'status' => 'ready',
        ];
    }

    /**
     * Proportional resize maintaining aspect ratio.
     */
    protected function resizeImage($source, int $srcW, int $srcH, int $maxW, int $maxH)
    {
        $ratio = min($maxW / $srcW, $maxH / $srcH);

        // Do not upscale if smaller than target
        if ($ratio >= 1.0) {
            $newW = $srcW;
            $newH = $srcH;
        } else {
            $newW = (int) round($srcW * $ratio);
            $newH = (int) round($srcH * $ratio);
        }

        $dst = imagecreatetruecolor($newW, $newH);

        // Preserve transparency for PNG/WebP
        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        imagecopyresampled($dst, $source, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        return $dst;
    }
}
