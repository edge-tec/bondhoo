<?php

namespace App\Services\Contracts;

interface MediaProcessingServiceInterface
{
    /**
     * Process image to generate WebP/AVIF variants (thumbnail, medium, large).
     */
    public function processImage(string $originalPath, array $options = []): array;

    /**
     * Process video to generate multiple resolutions (360p, 480p, 720p, 1080p) and thumbnails.
     */
    public function processVideo(string $originalPath, array $options = []): array;

    /**
     * Extract metadata (mime, width, height, size, checksum) from file.
     */
    public function extractMetadata(string $path): array;
}
