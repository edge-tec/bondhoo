<?php

namespace App\Services\Contracts;

interface MediaStorageServiceInterface
{
    /**
     * Generate a temporary signed upload URL for direct S3-compatible client upload.
     */
    public function generateSignedUploadUrl(string $path, string $mimeType, int $expiryMinutes = 15): array;

    /**
     * Get permanent public or CDN URL for a stored object.
     */
    public function getUrl(string $path): string;

    /**
     * Get temporary signed download/view URL for private media.
     */
    public function getTemporaryUrl(string $path, int $expiryMinutes = 30): string;

    /**
     * Store a file directly to object storage.
     */
    public function put(string $path, mixed $contents, array $options = []): bool;

    /**
     * Delete an object from storage.
     */
    public function delete(string $path): bool;

    /**
     * Check if an object exists in storage.
     */
    public function exists(string $path): bool;
}
