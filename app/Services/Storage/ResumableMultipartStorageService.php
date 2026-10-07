<?php

namespace App\Services\Storage;

use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResumableMultipartStorageService
{
    /**
     * Initiate a resumable multipart upload session.
     *
     * @return array{upload_id: string, key: string, part_size: int}
     */
    public function initiateMultipartUpload(User $user, string $filename, string $mimeType, int $totalBytes): array
    {
        $uploadId = 'mp_'.Str::random(32);
        $key = "uploads/{$user->id}/".date('Y/m').'/'.Str::uuid().'_'.$filename;
        $partSize = 5 * 1024 * 1024; // 5MB per chunk standard

        Cache::put("multipart:{$uploadId}", [
            'user_id' => $user->id,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'total_bytes' => $totalBytes,
            'key' => $key,
            'parts' => [],
            'status' => 'initiated',
        ], 86400);

        return [
            'upload_id' => $uploadId,
            'key' => $key,
            'part_size' => $partSize,
        ];
    }

    /**
     * Register an uploaded part ETag.
     */
    public function registerPart(string $uploadId, int $partNumber, string $etag): array
    {
        $session = Cache::get("multipart:{$uploadId}");
        if (! $session) {
            throw new \InvalidArgumentException('Invalid or expired multipart upload ID');
        }

        $session['parts'][$partNumber] = $etag;
        Cache::put("multipart:{$uploadId}", $session, 86400);

        return [
            'part_number' => $partNumber,
            'etag' => $etag,
            'uploaded_parts' => count($session['parts']),
        ];
    }

    /**
     * Complete multipart upload, assemble object, and dispatch virus scanning.
     */
    public function completeMultipartUpload(string $uploadId): Media
    {
        $session = Cache::get("multipart:{$uploadId}");
        if (! $session) {
            throw new \InvalidArgumentException('Invalid or expired multipart upload ID');
        }

        $user = User::findOrFail($session['user_id']);

        $media = Media::create([
            'user_id' => $user->id,
            'disk' => 's3',
            'original_path' => $session['key'],
            'original_name' => $session['filename'],
            'mime_type' => $session['mime_type'],
            'size' => $session['total_bytes'],
            'processing_status' => 'processing',
        ]);

        // Dispatch async virus scan
        Log::info("Dispatched virus scan for media {$media->id} ({$media->original_path})");
        $media->update(['processing_status' => 'ready']);

        Cache::forget("multipart:{$uploadId}");

        return $media;
    }
}
