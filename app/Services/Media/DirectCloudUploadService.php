<?php

namespace App\Services\Media;

use App\Jobs\ProcessMediaJob;
use App\Models\Media;
use App\Models\MediaUploadSession;
use App\Models\User;
use App\Models\UserStorageQuota;
use Aws\S3\S3Client;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * ক্লাউড স্টোরেজ ও সরাসরি S3 / Cloudflare R2 মাল্টিপার্ট ভিডিও আপলোড সার্ভিস:
 * ক্লায়েন্ট সরাসরি ক্লাউড বাক্যাটে (S3 / R2) ভিডিও ও বড় ফাইল মাল্টিপার্ট আকারে
 * প্রিসাইনড ইউআরএল (Presigned URL) দিয়ে আপলোড করতে পারে, যা অ্যাপ্লিকেশনের
 * সার্ভার ব্যান্ডউইথ ও মেমরি সম্পূর্ণ সুরক্ষিত রাখে এবং শূন্য ইগ্রেস ফিতে ফাস্ট আপলোড নিশ্চিত করে।
 */
class DirectCloudUploadService
{
    /**
     * Minimum part size for S3/R2 multipart upload (5 MB in bytes).
     */
    public const MIN_PART_SIZE = 5242880;

    /**
     * Maximum part size for S3/R2 multipart upload (50 MB in bytes).
     */
    public const MAX_PART_SIZE = 52428800;

    /**
     * Optional custom S3Client instance for dependency injection & testing.
     */
    protected ?S3Client $customS3Client = null;

    public function __construct(
        protected SystemResourceProtectionService $resourceProtection
    ) {}

    /**
     * Inject custom S3Client (useful for unit/feature testing with mocks).
     */
    public function setS3Client(?S3Client $client): self
    {
        $this->customS3Client = $client;

        return $this;
    }

    /**
     * Resolve the configured S3 / R2 Client.
     */
    public function getS3Client(string $disk = 's3'): S3Client
    {
        if ($this->customS3Client !== null) {
            return $this->customS3Client;
        }

        $config = config("filesystems.disks.{$disk}") ?? config('filesystems.disks.s3');

        if (empty($config)) {
            throw new InvalidArgumentException("Storage disk [{$disk}] is not configured in filesystems.php.");
        }

        $s3Options = [
            'version' => 'latest',
            'region' => $config['region'] ?? 'us-east-1',
            'endpoint' => $config['endpoint'] ?? null,
            'use_path_style_endpoint' => (bool) ($config['use_path_style_endpoint'] ?? false),
            'credentials' => [
                'key' => $config['key'] ?? 'minioadmin',
                'secret' => $config['secret'] ?? 'minioadmin',
            ],
        ];

        return new S3Client($s3Options);
    }

    /**
     * Resolve target bucket name for specified disk.
     */
    public function getBucket(string $disk = 's3'): string
    {
        $config = config("filesystems.disks.{$disk}") ?? config('filesystems.disks.s3');

        return (string) ($config['bucket'] ?? env('AWS_BUCKET', 'jugajug-media'));
    }

    /**
     * Initialize Direct Multipart Upload on S3 / Cloudflare R2 and generate presigned part URLs.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function initDirectMultipart(User $user, array $params, string $disk = 's3'): array
    {
        $fileSize = (int) ($params['file_size'] ?? 0);
        $collection = (string) ($params['collection'] ?? 'general');
        $mimeType = (string) ($params['mime_type'] ?? 'video/mp4');
        $originalName = (string) ($params['filename'] ?? 'video.mp4');

        // 1. Quota & capacity validation
        $this->resourceProtection->validateUploadCapacity($user, $fileSize, $collection);

        // 2. Part size determination (minimum 5MB, default 5MB or customized)
        $partSize = (int) ($params['part_size'] ?? self::MIN_PART_SIZE);
        if ($partSize < self::MIN_PART_SIZE) {
            $partSize = self::MIN_PART_SIZE;
        } elseif ($partSize > self::MAX_PART_SIZE) {
            $partSize = self::MAX_PART_SIZE;
        }

        $totalParts = (int) ceil($fileSize / $partSize);
        if ($totalParts <= 0) {
            $totalParts = 1;
        }

        // 3. Build unique storage key
        $sessionId = Str::uuid()->toString();
        $safeExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (empty($safeExtension)) {
            $safeExtension = $this->guessExtensionFromMime($mimeType);
        }

        $dateFolder = now()->format('Y/m');
        $sanitizedBasename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'video';
        $s3Key = "uploads/{$collection}/{$dateFolder}/{$sanitizedBasename}-{$sessionId}.{$safeExtension}";

        $bucket = $this->getBucket($disk);
        $s3Client = $this->getS3Client($disk);

        // 4. Create Multipart Upload in S3 / R2
        $result = $s3Client->createMultipartUpload([
            'Bucket' => $bucket,
            'Key' => $s3Key,
            'ContentType' => $mimeType,
            'Metadata' => [
                'user_id' => (string) $user->id,
                'original_name' => $originalName,
                'collection' => $collection,
            ],
        ]);

        $uploadId = (string) ($result['UploadId'] ?? '');
        if (empty($uploadId)) {
            throw new Exception('Failed to initialize S3/R2 multipart upload: empty upload ID received.');
        }

        // 5. Generate Presigned PUT URLs for each part (valid for 3 hours)
        $parts = [];
        $expiryMinutes = 180;

        for ($partNumber = 1; $partNumber <= $totalParts; $partNumber++) {
            $command = $s3Client->getCommand('UploadPart', [
                'Bucket' => $bucket,
                'Key' => $s3Key,
                'UploadId' => $uploadId,
                'PartNumber' => $partNumber,
            ]);

            $presignedRequest = $s3Client->createPresignedRequest($command, "+{$expiryMinutes} minutes");

            $parts[] = [
                'part_number' => $partNumber,
                'upload_url' => (string) $presignedRequest->getUri(),
            ];
        }

        // 6. Record upload session in database
        $session = MediaUploadSession::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'collection' => $collection,
            'filename' => basename($s3Key),
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'chunk_size' => $partSize,
            'total_chunks' => $totalParts,
            'uploaded_chunks_count' => 0,
            'status' => MediaUploadSession::STATUS_UPLOADING,
            'checksum' => $params['checksum'] ?? null,
            'temp_dir' => "s3://{$bucket}/{$s3Key}",
            'target_path' => $s3Key,
            'metadata' => [
                'driver' => $disk,
                'bucket' => $bucket,
                's3_key' => $s3Key,
                'upload_id' => $uploadId,
                'part_size' => $partSize,
                'client_metadata' => $params['metadata'] ?? [],
            ],
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        Log::info('DirectCloudUploadService: Multipart upload initialized.', [
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'upload_id' => $uploadId,
            'key' => $s3Key,
            'total_parts' => $totalParts,
            'disk' => $disk,
        ]);

        return [
            'session_id' => $session->session_id,
            'upload_id' => $uploadId,
            'key' => $s3Key,
            'bucket' => $bucket,
            'disk' => $disk,
            'part_size' => $partSize,
            'total_parts' => $totalParts,
            'expires_at' => $session->expires_at->toIso8601String(),
            'parts' => $parts,
        ];
    }

    /**
     * Complete Direct Multipart Upload on S3 / Cloudflare R2 and persist Media record.
     *
     * @param  array<int, array{PartNumber: int, ETag: string}>  $completedParts
     *
     * @throws Exception
     */
    public function completeDirectMultipart(User $user, string $sessionId, array $completedParts): Media
    {
        $session = MediaUploadSession::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (! $session) {
            throw new Exception("Media upload session [{$sessionId}] not found or access denied.");
        }

        if ($session->status === MediaUploadSession::STATUS_COMPLETED && $session->target_path) {
            $existing = Media::where('original_path', $session->target_path)->first();
            if ($existing) {
                return $existing;
            }
        }

        $metadata = $session->metadata ?? [];
        $disk = (string) ($metadata['driver'] ?? 's3');
        $bucket = (string) ($metadata['bucket'] ?? $this->getBucket($disk));
        $s3Key = (string) ($metadata['s3_key'] ?? $session->target_path);
        $uploadId = (string) ($metadata['upload_id'] ?? '');

        if (empty($uploadId) || empty($s3Key)) {
            throw new Exception('Invalid upload session metadata: missing upload_id or s3_key.');
        }

        if (empty($completedParts)) {
            throw new Exception('Cannot complete multipart upload without parts and ETags list.');
        }

        // Sort parts by PartNumber ascending
        usort($completedParts, static fn (array $a, array $b): int => ($a['PartNumber'] ?? 0) <=> ($b['PartNumber'] ?? 0));

        // Format parts array for S3 SDK CompleteMultipartUpload
        $formattedParts = array_map(static fn (array $part): array => [
            'PartNumber' => (int) $part['PartNumber'],
            'ETag' => trim((string) $part['ETag'], '"'),
        ], $completedParts);

        $s3Client = $this->getS3Client($disk);

        // 1. Complete Multipart Upload on S3 / R2
        $s3Client->completeMultipartUpload([
            'Bucket' => $bucket,
            'Key' => $s3Key,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => $formattedParts,
            ],
        ]);

        // 2. Fetch object metadata to confirm size and existence
        $head = $s3Client->headObject([
            'Bucket' => $bucket,
            'Key' => $s3Key,
        ]);

        $finalSize = (int) ($head['ContentLength'] ?? $session->file_size);
        $finalETag = trim((string) ($head['ETag'] ?? ''), '"');

        // 3. Atomically create Media record and update storage quota
        $media = DB::transaction(function () use ($session, $disk, $s3Key, $finalSize, $finalETag, $metadata): Media {
            $media = Media::create([
                'user_id' => $session->user_id,
                'collection' => $session->collection,
                'disk' => $disk,
                'original_path' => $s3Key,
                'mime_type' => $session->mime_type,
                'size' => $finalSize,
                'checksum' => $finalETag,
                'processing_status' => 'processing',
                'metadata' => array_merge($metadata['client_metadata'] ?? [], [
                    'original_filename' => $session->original_name,
                    'upload_session_id' => $session->session_id,
                    'storage_driver' => $disk,
                    'bucket' => $metadata['bucket'] ?? null,
                    'etag' => $finalETag,
                ]),
            ]);

            // Increment user storage quota
            $quota = UserStorageQuota::firstOrCreate(['user_id' => $session->user_id]);
            $quota->increment('used_storage_bytes', $finalSize);
            $quota->increment('today_uploads_count');
            $quota->update(['last_upload_date' => now()->toDateString()]);

            $session->update([
                'status' => MediaUploadSession::STATUS_COMPLETED,
                'uploaded_chunks_count' => count($metadata['parts'] ?? []),
            ]);

            return $media;
        });

        // 4. Dispatch background video processing / thumbnail job if video
        if ($this->isVideo($session->mime_type)) {
            try {
                ProcessMediaJob::dispatch($media);
            } catch (\Throwable $e) {
                Log::warning('ProcessMediaJob dispatch failed: '.$e->getMessage());
            }
        }

        Log::info('DirectCloudUploadService: Multipart upload completed successfully.', [
            'session_id' => $session->session_id,
            'media_id' => $media->id,
            'key' => $s3Key,
            'size' => $finalSize,
        ]);

        return $media;
    }

    /**
     * Abort in-progress direct multipart upload and clean up parts.
     *
     * @throws Exception
     */
    public function abortDirectMultipart(User $user, string $sessionId): bool
    {
        $session = MediaUploadSession::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (! $session) {
            return false;
        }

        $metadata = $session->metadata ?? [];
        $disk = (string) ($metadata['driver'] ?? 's3');
        $bucket = (string) ($metadata['bucket'] ?? $this->getBucket($disk));
        $s3Key = (string) ($metadata['s3_key'] ?? $session->target_path);
        $uploadId = (string) ($metadata['upload_id'] ?? '');

        if ($uploadId && $s3Key) {
            try {
                $s3Client = $this->getS3Client($disk);
                $s3Client->abortMultipartUpload([
                    'Bucket' => $bucket,
                    'Key' => $s3Key,
                    'UploadId' => $uploadId,
                ]);
            } catch (\Throwable $e) {
                Log::warning("Failed to abort S3 multipart upload [{$uploadId}]: ".$e->getMessage());
            }
        }

        $session->update(['status' => MediaUploadSession::STATUS_CANCELLED]);

        return true;
    }

    /**
     * Generate presigned single PUT URL for smaller files / images (< 10 MB).
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function generatePresignedPutUrl(User $user, array $params, string $disk = 's3'): array
    {
        $fileSize = (int) ($params['file_size'] ?? 0);
        $collection = (string) ($params['collection'] ?? 'general');
        $mimeType = (string) ($params['mime_type'] ?? 'image/jpeg');
        $originalName = (string) ($params['filename'] ?? 'file.bin');

        $this->resourceProtection->validateUploadCapacity($user, $fileSize, $collection);

        $sessionId = Str::uuid()->toString();
        $safeExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) ?: $this->guessExtensionFromMime($mimeType);
        $dateFolder = now()->format('Y/m');
        $s3Key = "uploads/{$collection}/{$dateFolder}/".Str::random(16).".{$safeExtension}";

        $bucket = $this->getBucket($disk);
        $s3Client = $this->getS3Client($disk);
        $expiryMinutes = 30;

        $command = $s3Client->getCommand('PutObject', [
            'Bucket' => $bucket,
            'Key' => $s3Key,
            'ContentType' => $mimeType,
        ]);

        $presignedRequest = $s3Client->createPresignedRequest($command, "+{$expiryMinutes} minutes");

        $session = MediaUploadSession::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'collection' => $collection,
            'filename' => basename($s3Key),
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'chunk_size' => $fileSize,
            'total_chunks' => 1,
            'uploaded_chunks_count' => 0,
            'status' => MediaUploadSession::STATUS_UPLOADING,
            'target_path' => $s3Key,
            'metadata' => [
                'driver' => $disk,
                'bucket' => $bucket,
                's3_key' => $s3Key,
                'upload_type' => 'single_put',
            ],
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        return [
            'session_id' => $session->session_id,
            'upload_url' => (string) $presignedRequest->getUri(),
            'method' => 'PUT',
            'headers' => [
                'Content-Type' => $mimeType,
            ],
            'key' => $s3Key,
            'bucket' => $bucket,
            'disk' => $disk,
            'expires_at' => $session->expires_at->toIso8601String(),
        ];
    }

    /**
     * Check if a MIME type represents a video.
     */
    protected function isVideo(string $mimeType): bool
    {
        return str_starts_with(strtolower($mimeType), 'video/')
            || in_array(strtolower($mimeType), ['application/x-mpegurl', 'application/vnd.apple.mpegurl'], true);
    }

    /**
     * Fallback MIME to extension mapper.
     */
    protected function guessExtensionFromMime(string $mimeType): string
    {
        return match (strtolower($mimeType)) {
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
            'video/x-matroska' => 'mkv',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/wav' => 'wav',
            default => 'bin',
        };
    }
}
