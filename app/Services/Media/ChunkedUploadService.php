<?php

namespace App\Services\Media;

use App\Jobs\ProcessMediaJob;
use App\Models\Media;
use App\Models\MediaUploadChunk;
use App\Models\MediaUploadSession;
use App\Models\User;
use App\Models\UserStorageQuota;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * রেজুমেবল ও চাঙ্কড আপলোড সার্ভিস:
 * বড় মিডিয়া ফাইল টুকরো টুকরো (chunks) করে নিরাপদে আপলোড ও স্ট্রিম অ্যাসেম্বলি করে।
 * মেমরি ওভারফ্লো বা সার্ভার হ্যাং সম্পূর্ণ প্রতিরোধ করে।
 */
class ChunkedUploadService
{
    public function __construct(
        protected SystemResourceProtectionService $resourceProtection
    ) {}

    /**
     * নতুন চাঙ্কড আপলোড সেশন ইনিশিয়ালাইজ করা।
     *
     * @param  array<string, mixed>  $params
     *
     * @throws Exception
     */
    public function initSession(User $user, array $params): MediaUploadSession
    {
        $fileSize = (int) ($params['file_size'] ?? 0);
        $collection = (string) ($params['collection'] ?? 'general');
        $mimeType = (string) ($params['mime_type'] ?? 'application/octet-stream');
        $originalName = (string) ($params['filename'] ?? 'upload.bin');

        // ১. সার্ভার ও ইউজার ক্যাপাসিটি যাচাই
        $this->resourceProtection->validateUploadCapacity($user, $fileSize, $collection);

        // ২. চাঙ্ক সাইজ নির্ধারণ (ডিফল্ট ২ মেগাবাইট)
        $chunkSize = max(1, (int) ($params['chunk_size'] ?? 2097152)); // 2 MB default

        $totalChunks = (int) ceil($fileSize / $chunkSize);
        if ($totalChunks <= 0) {
            $totalChunks = 1;
        }

        $sessionId = Str::uuid()->toString();
        $safeExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (empty($safeExtension)) {
            $safeExtension = $this->guessExtensionFromMime($mimeType);
        }

        $sanitizedFilename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)).'-'.Str::random(10).'.'.$safeExtension;
        $tempDir = storage_path("app/chunks/{$sessionId}");

        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        return MediaUploadSession::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'collection' => $collection,
            'filename' => $sanitizedFilename,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'chunk_size' => $chunkSize,
            'total_chunks' => $totalChunks,
            'uploaded_chunks_count' => 0,
            'status' => 'initialized',
            'checksum' => $params['checksum'] ?? null,
            'temp_dir' => $tempDir,
            'metadata' => $params['metadata'] ?? [],
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * নির্দিষ্ট চাঙ্ক আপলোড ও সেভ করা।
     *
     * @throws Exception
     */
    public function uploadChunk(
        User $user,
        string $sessionId,
        int $chunkNumber,
        UploadedFile $chunkFile,
        ?string $clientChecksum = null
    ): array {
        $session = MediaUploadSession::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (! $session) {
            throw new Exception('Upload session not found or access denied.');
        }

        if (in_array($session->status, ['completed', 'cancelled'])) {
            throw new Exception("Upload session is already {$session->status}.");
        }

        if ($chunkNumber < 1 || $chunkNumber > $session->total_chunks) {
            throw new Exception("Invalid chunk number {$chunkNumber}. Total expected chunks: {$session->total_chunks}");
        }

        // ডিস্ক স্পেস প্রোটেকশন চেক
        $this->resourceProtection->ensureDiskCapacity($chunkFile->getSize());

        $tempDir = $session->temp_dir;
        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $chunkPath = "{$tempDir}/chunk_{$chunkNumber}.part";

        // চাঙ্ক ফাইল সেভ করা
        $chunkFile->move($tempDir, "chunk_{$chunkNumber}.part");

        // চাঙ্ক চেকসাম ভেরিফিকেশন (যদি ক্লায়েন্ট পাঠায়)
        $actualChecksum = hash_file('sha256', $chunkPath);
        if ($clientChecksum && ! hash_equals(strtolower($clientChecksum), strtolower($actualChecksum))) {
            @unlink($chunkPath);
            throw new Exception("Chunk {$chunkNumber} integrity check failed. Checksum mismatch.");
        }

        // চাঙ্ক রেকর্ড ডাটাবেজে সংরক্ষণ
        MediaUploadChunk::updateOrCreate(
            [
                'upload_session_id' => $session->id,
                'chunk_number' => $chunkNumber,
            ],
            [
                'chunk_size' => filesize($chunkPath),
                'chunk_checksum' => $actualChecksum,
                'temp_path' => $chunkPath,
                'status' => 'verified',
                'uploaded_at' => now(),
            ]
        );

        // আপলোডেড চাঙ্ক কাউন্ট আপডেট
        $verifiedCount = MediaUploadChunk::where('upload_session_id', $session->id)
            ->where('status', 'verified')
            ->count();

        $session->update([
            'uploaded_chunks_count' => $verifiedCount,
            'status' => ($verifiedCount >= $session->total_chunks) ? 'assembling' : 'uploading',
        ]);

        $isComplete = ($verifiedCount >= $session->total_chunks);
        $media = null;

        // সমস্ত চাঙ্ক আপলোড সম্পন্ন হলে স্ট্রিম অ্যাসেম্বলি শুরু হবে
        if ($isComplete) {
            $media = $this->assembleFile($session);
        }

        return [
            'session_id' => $session->session_id,
            'chunk_number' => $chunkNumber,
            'uploaded_chunks_count' => $verifiedCount,
            'total_chunks' => $session->total_chunks,
            'progress' => round(($verifiedCount / $session->total_chunks) * 100, 1),
            'is_complete' => $isComplete,
            'media' => $media?->toResponseArray(),
        ];
    }

    /**
     * ক্লায়েন্ট রিকোয়েস্টের মাধ্যমে অ্যাসেম্বলি সম্পূর্ণ করা (যদি স্বয়ংক্রিয় না হয়)
     *
     * @throws Exception
     */
    public function completeSession(User $user, string $sessionId): Media
    {
        $session = MediaUploadSession::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (! $session) {
            throw new Exception('Upload session not found.');
        }

        if ($session->status === 'completed' && $session->target_path) {
            $existingMedia = Media::where('original_path', $session->target_path)->first();
            if ($existingMedia) {
                return $existingMedia;
            }
        }

        $verifiedCount = MediaUploadChunk::where('upload_session_id', $session->id)
            ->where('status', 'verified')
            ->count();

        if ($verifiedCount < $session->total_chunks) {
            throw new Exception("Cannot complete upload. Uploaded {$verifiedCount} of {$session->total_chunks} chunks.");
        }

        return $this->assembleFile($session);
    }

    /**
     * চাঙ্কগুলোকে মেমরি-সেফ স্ট্রিমিং পদ্ধতির মাধ্যমে অ্যাসেম্বল করে ফাইনাল ফাইল তৈরি করা।
     * কখনোই পুরো ফাইলকে PHP র‍্যামে লোড করে না (Stream Copy Buffer 64KB)।
     * অ্যাটমিক অ্যাসেম্বলি (.tmp ফাইল থেকে রিনেম) এবং বাইনারি ম্যাজিক বাইটস ভ্যালিডেশন নিশ্চিত করে।
     *
     * @throws Exception
     */
    public function assembleFile(MediaUploadSession $session): Media
    {
        $startTime = microtime(true);
        $session->update(['status' => 'assembling']);

        $dateFolder = now()->format('Y/m');
        $collection = $session->collection ?: 'general';
        $relativeDir = "uploads/{$collection}/{$dateFolder}";
        $fullDestDir = storage_path("app/public/{$relativeDir}");

        if (! File::isDirectory($fullDestDir)) {
            File::makeDirectory($fullDestDir, 0755, true);
        }

        // অ্যাটমিক অ্যাসেম্বলির জন্য সাময়িক টেম্প ফাইল তৈরি করা
        $tempAssembleFilePath = "{$fullDestDir}/.tmp_{$session->filename}";
        $destFilePath = "{$fullDestDir}/{$session->filename}";
        $relativeFilePath = "{$relativeDir}/{$session->filename}";

        $outHandle = @fopen($tempAssembleFilePath, 'wb');
        if (! $outHandle) {
            $session->update(['status' => 'failed']);
            throw new Exception('Could not initialize destination media file for writing.');
        }

        try {
            // ক্রম অনুযায়ী প্রতিটি চাঙ্ক স্ট্রিম কপি করা (আউট-অফ-অর্ডার চাঙ্ক এলেও অ্যাসেম্বলি সঠিক অর্ডারে হবে)
            for ($i = 1; $i <= $session->total_chunks; $i++) {
                $chunkPartPath = "{$session->temp_dir}/chunk_{$i}.part";
                if (! file_exists($chunkPartPath)) {
                    throw new Exception("Missing chunk {$i} during media assembly.");
                }

                $inHandle = @fopen($chunkPartPath, 'rb');
                if (! $inHandle) {
                    throw new Exception("Could not read chunk {$i} file.");
                }

                // 64 KB বাফারে স্ট্রিম কপি - মেমরি কখনোই ফুল ফাইলের সাইজে পৌঁছাবে না!
                stream_copy_to_stream($inHandle, $outHandle, -1, 0);
                fclose($inHandle);
            }
        } finally {
            fclose($outHandle);
        }

        // সাইজ ও ইন্টিগ্রিটি ভেরিফিকেশন
        $finalSize = filesize($tempAssembleFilePath);
        $finalChecksum = hash_file('sha256', $tempAssembleFilePath);

        // ক্লায়েন্ট চেকসাম ভ্যালিডেশন
        if ($session->checksum && ! hash_equals(strtolower($session->checksum), strtolower($finalChecksum))) {
            @unlink($tempAssembleFilePath);
            $session->update(['status' => 'failed']);
            throw new Exception('Final media integrity checksum mismatch. File is corrupted.');
        }

        // বাইনারি ম্যাজিক বাইটস / ফাইল সিগনেচার ভ্যালিডেশন (ম্যালিসিয়াস ফাইল ব্লকিং)
        if (! $this->validateFileSignature($tempAssembleFilePath, $session->mime_type)) {
            @unlink($tempAssembleFilePath);
            $session->update(['status' => 'failed']);
            Log::warning('SecurityViolation: Malicious or mismatched file signature detected.', [
                'session_id' => $session->session_id,
                'user_id' => $session->user_id,
                'mime_type' => $session->mime_type,
            ]);
            throw new Exception('File signature verification failed. The file format does not match the declared MIME type.');
        }

        // অ্যাটমিক রিনেম (POSIX অ্যাটমিক মুভ - ফাইল কখনো অসমাপ্ত অবস্থায় পাবলিক পাথে দৃশ্যমান হবে না)
        if (! @rename($tempAssembleFilePath, $destFilePath)) {
            @unlink($tempAssembleFilePath);
            $session->update(['status' => 'failed']);
            throw new Exception('Failed to finalize media file atomic move.');
        }

        // টেম্পোরারি চাঙ্ক ডিরেক্টরি মুছে ফেলা
        try {
            File::deleteDirectory($session->temp_dir);
        } catch (\Throwable $e) {
            Log::warning('Failed to clean up chunk dir: '.$e->getMessage());
        }

        // ডাটাবেজে Media রেকর্ড তৈরি ও ট্রানজেকশনে ইউজার স্টোরেজ কোটা আপডেট
        $media = DB::transaction(function () use ($session, $relativeFilePath, $finalSize, $finalChecksum) {
            $media = Media::create([
                'user_id' => $session->user_id,
                'collection' => $session->collection,
                'disk' => 'public',
                'original_path' => $relativeFilePath,
                'mime_type' => $session->mime_type,
                'size' => $finalSize,
                'checksum' => $finalChecksum,
                'processing_status' => 'processing',
                'metadata' => array_merge($session->metadata ?? [], [
                    'original_filename' => $session->original_name,
                    'upload_session_id' => $session->session_id,
                ]),
            ]);

            // ইউজারের ব্যবহৃত স্টোরেজ কোটা বাড়ানো
            $quota = UserStorageQuota::firstOrCreate(['user_id' => $session->user_id]);
            $quota->increment('used_storage_bytes', $finalSize);
            $quota->increment('today_uploads_count');
            $quota->update(['last_upload_date' => now()->toDateString()]);

            $session->update([
                'status' => 'completed',
                'target_path' => $relativeFilePath,
            ]);

            return $media;
        });

        $assemblyDuration = round((microtime(true) - $startTime) * 1000, 2);
        Log::info('ChunkedUploadService: Media assembled successfully.', [
            'session_id' => $session->session_id,
            'user_id' => $session->user_id,
            'media_id' => $media->id,
            'size_bytes' => $finalSize,
            'duration_ms' => $assemblyDuration,
        ]);

        // ব্যাকগ্রাউন্ড প্রসেসিং কিউ জব প্রেরণ
        ProcessMediaJob::dispatch($media->id);

        return $media;
    }

    /**
     * ইন্টারাপ্টেড আপলোড সেশন রেজুম করার তথ্য ফেরত দেওয়া।
     * মিসিং চাঙ্কসমূহের তালিকা প্রদান করে যাতে আউট-অফ-অর্ডার থাকা সত্ত্বেও রেজুম করা যায়।
     *
     * @throws Exception
     */
    public function resumeSession(User $user, string $sessionId): array
    {
        $session = MediaUploadSession::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (! $session) {
            throw new Exception('Upload session not found.');
        }

        $uploadedChunks = MediaUploadChunk::where('upload_session_id', $session->id)
            ->where('status', 'verified')
            ->pluck('chunk_number')
            ->toArray();

        // মিসিং চাঙ্কসমূহের অ্যারে নির্ণয়
        $missingChunks = [];
        $nextChunk = null;
        for ($i = 1; $i <= $session->total_chunks; $i++) {
            if (! in_array($i, $uploadedChunks)) {
                $missingChunks[] = $i;
                if ($nextChunk === null) {
                    $nextChunk = $i;
                }
            }
        }

        return [
            'session_id' => $session->session_id,
            'filename' => $session->original_name,
            'file_size' => $session->file_size,
            'chunk_size' => $session->chunk_size,
            'total_chunks' => $session->total_chunks,
            'uploaded_chunks' => $uploadedChunks,
            'missing_chunks' => $missingChunks,
            'next_chunk' => $nextChunk ?? 1,
            'status' => $session->status,
            'progress' => round((count($uploadedChunks) / $session->total_chunks) * 100, 1),
        ];
    }

    /**
     * আপলোড সেশন বাতিল এবং টেম্প ডাটা ক্লিনআপ করা।
     */
    public function cancelSession(User $user, string $sessionId): bool
    {
        $session = MediaUploadSession::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (! $session) {
            return false;
        }

        if (File::isDirectory($session->temp_dir)) {
            File::deleteDirectory($session->temp_dir);
        }

        $session->update(['status' => 'cancelled']);

        return true;
    }

    /**
     * বাইনারি ফাইল সিগনেচার / ম্যাজিক বাইটস যাচাই
     */
    public function validateFileSignature(string $filePath, string $mimeType): bool
    {
        if (! file_exists($filePath) || filesize($filePath) < 4) {
            return false;
        }

        $handle = @fopen($filePath, 'rb');
        if (! $handle) {
            return false;
        }

        $header = fread($handle, 32);
        fclose($handle);

        if ($header === false || strlen($header) < 4) {
            return false;
        }

        return match ($mimeType) {
            'video/mp4' => str_contains(substr($header, 4, 8), 'ftyp') ||
                           str_contains(substr($header, 4, 12), 'isom') ||
                           str_contains(substr($header, 4, 12), 'mp42') ||
                           str_contains(substr($header, 4, 12), 'MSNV'),
            'video/quicktime' => str_contains(substr($header, 4, 8), 'ftyp') ||
                                 str_contains(substr($header, 4, 8), 'moov') ||
                                 str_contains(substr($header, 4, 8), 'wide'),
            'video/webm', 'video/x-matroska' => str_starts_with($header, "\x1A\x45\xDF\xA3"),
            'image/jpeg' => str_starts_with($header, "\xFF\xD8\xFF"),
            'image/png' => str_starts_with($header, "\x89PNG\r\n\x1a\n"),
            'image/gif' => str_starts_with($header, 'GIF87a') || str_starts_with($header, 'GIF89a'),
            'image/webp' => str_starts_with($header, 'RIFF') && str_contains(substr($header, 8, 8), 'WEBP'),
            'application/octet-stream' => true, // Generic binary payload allowed in controlled sessions
            default => true,
        };
    }

    /**
     * MIME টাইপ থেকে এক্সটেনশন অনুমান
     */
    protected function guessExtensionFromMime(string $mime): string
    {
        return match ($mime) {
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
            'video/x-matroska' => 'mkv',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/avif' => 'avif',
            default => 'bin',
        };
    }
}
