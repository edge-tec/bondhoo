<?php

namespace App\Services;

use App\Models\UserProfile;
use App\Services\Contracts\MediaStorageServiceInterface;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MediaStorageService implements MediaStorageServiceInterface
{
    protected ?string $disk;

    protected ?string $cdnUrl;

    public function __construct(?string $disk = null)
    {
        $this->disk = $disk;
        $this->cdnUrl = config('filesystems.cdn_url', env('CDN_URL'));
    }

    /**
     * Generate storage key according to enterprise structure:
     * users/{user_id}/profile/{filename}
     * posts/{post_id}/images/{filename}
     * etc.
     */
    public function generatePath(int $userId, string $collection, string $extension, ?int $entityId = null): string
    {
        $filename = Str::uuid()->toString().'.'.$extension;

        return match ($collection) {
            'profile' => "users/{$userId}/profile/{$filename}",
            'cover' => "users/{$userId}/cover/{$filename}",
            'post' => 'posts/'.($entityId ?? 'pending')."/images/{$filename}",
            'video' => 'posts/'.($entityId ?? 'pending')."/videos/{$filename}",
            'story' => 'stories/'.($entityId ?? 'pending')."/{$filename}",
            'message' => 'messages/'.($entityId ?? 'pending')."/{$filename}",
            'group' => 'groups/'.($entityId ?? 'pending')."/{$filename}",
            'page' => 'pages/'.($entityId ?? 'pending')."/{$filename}",
            default => "media/{$userId}/{$filename}",
        };
    }

    public function generateSignedUploadUrl(string $path, string $mimeType, int $expiryMinutes = 15): array
    {
        $driver = config("filesystems.disks.{$this->disk}.driver");

        // If using S3/R2/MinIO, generate presigned S3 PUT URL
        if ($driver === 's3') {
            $s3Config = config("filesystems.disks.{$this->disk}");
            $client = new S3Client([
                'version' => 'latest',
                'region' => $s3Config['region'] ?? 'us-east-1',
                'endpoint' => $s3Config['endpoint'] ?? null,
                'use_path_style_endpoint' => $s3Config['use_path_style_endpoint'] ?? false,
                'credentials' => [
                    'key' => $s3Config['key'],
                    'secret' => $s3Config['secret'],
                ],
            ]);

            $cmd = $client->getCommand('PutObject', [
                'Bucket' => $s3Config['bucket'],
                'Key' => $path,
                'ContentType' => $mimeType,
            ]);

            $request = $client->createPresignedRequest($cmd, "+{$expiryMinutes} minutes");

            return [
                'upload_url' => (string) $request->getUri(),
                'method' => 'PUT',
                'headers' => [
                    'Content-Type' => $mimeType,
                ],
                'path' => $path,
                'expires_at' => now()->addMinutes($expiryMinutes)->toIso8601String(),
            ];
        }

        // For local/public environments, generate signed API upload route
        $signedRoute = URL::temporarySignedRoute(
            'api.media.direct-upload',
            now()->addMinutes($expiryMinutes),
            ['path' => base64_encode($path)]
        );

        return [
            'upload_url' => $signedRoute,
            'method' => 'POST',
            'headers' => [
                'Content-Type' => $mimeType,
            ],
            'path' => $path,
            'expires_at' => now()->addMinutes($expiryMinutes)->toIso8601String(),
        ];
    }

    public function getUrl(string $path): string
    {
        if ($this->cdnUrl) {
            return rtrim($this->cdnUrl, '/').'/'.ltrim($path, '/');
        }

        $url = Storage::disk($this->getDisk())->url($path);

        return UserProfile::normalizeStorageUrl($url) ?? $url;
    }

    public function getTemporaryUrl(string $path, int $expiryMinutes = 30): string
    {
        $disk = $this->getDisk();
        $driver = config("filesystems.disks.{$disk}.driver");

        if ($driver === 's3') {
            return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes($expiryMinutes));
        }

        return URL::temporarySignedRoute(
            'api.media.view-private',
            now()->addMinutes($expiryMinutes),
            ['path' => base64_encode($path)]
        );
    }

    public function put(string $path, mixed $contents, array $options = []): bool
    {
        return Storage::disk($this->getDisk())->put($path, $contents, $options);
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->getDisk())->delete($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->getDisk())->exists($path);
    }

    public function getDisk(): string
    {
        if ($this->disk) {
            return $this->disk;
        }

        $configured = config('filesystems.media_disk');
        if ($configured) {
            return $configured;
        }

        $default = config('filesystems.default', 'public');

        // Laravel 11/12 defaults 'local' to storage/app/private, which is inaccessible to web /storage symlink.
        // Public media assets (profile avatars, covers, posts, etc.) served via URL must use 'public' disk.
        return $default === 'local' ? 'public' : $default;
    }
}
