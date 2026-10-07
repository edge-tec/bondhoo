<?php

namespace App\Services;

use App\Events\ProfileUpdatedEvent;
use App\Jobs\ProcessCoverJob;
use App\Models\AuditLog;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Contracts\MediaStorageServiceInterface;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CoverService
{
    public function __construct(
        protected MediaStorageServiceInterface $storageService,
        protected ProfileService $profileService
    ) {}

    /**
     * Upload or replace profile cover photo with deep security inspection,
     * deferred safe deletion of old cover photos, and background queue processing.
     *
     * @return array{cover_url: string, media: array, post: Post}
     */
    public function uploadCover(User $user, UploadedFile $file, ?array $crop = null, ?string $caption = null, ?int $coverPositionY = null): array
    {
        // 1. Deep Security & MIME validation (never trust client MIME)
        $meta = $this->validateAndInspectFile($file);

        // 2. Identify old cover media and paths for safe deferred deletion
        $oldMediaRecords = Media::where('user_id', $user->id)
            ->where('collection', 'covers')
            ->get();

        $oldPathsToDelete = [];
        foreach ($oldMediaRecords as $oldMedia) {
            if ($oldMedia->original_path) {
                $oldPathsToDelete[] = $oldMedia->original_path;
            }
            if ($oldMedia->thumbnail_path) {
                $oldPathsToDelete[] = $oldMedia->thumbnail_path;
            }
            if ($oldMedia->medium_path) {
                $oldPathsToDelete[] = $oldMedia->medium_path;
            }
            if ($oldMedia->large_path) {
                $oldPathsToDelete[] = $oldMedia->large_path;
            }
        }

        // 3. Generate path and store new original file in Object Storage
        $disk = $this->storageService->getDisk();
        $ext = $meta['extension'];
        $path = $this->storageService->generatePath($user->id, 'cover', $ext);

        $fileContents = file_get_contents($file->getRealPath());
        if ($fileContents === false) {
            throw ValidationException::withMessages(['file' => ['ফাইল রিড করা সম্ভব হয়নি।']]);
        }

        $stored = Storage::disk($disk)->put($path, $fileContents, 'public');
        if (! $stored) {
            throw new Exception("Failed to write cover photo to storage disk [{$disk}] at path [{$path}].");
        }

        $coverUrl = $this->storageService->getUrl($path);

        // 4. Update Profile & Create Media record
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);
        $oldProfileValues = $profile->toArray();

        $media = Media::create([
            'user_id' => $user->id,
            'mediable_type' => UserProfile::class,
            'mediable_id' => $profile->id,
            'collection' => 'covers',
            'disk' => $disk,
            'original_path' => $path,
            'mime_type' => $meta['mime'],
            'width' => $meta['width'],
            'height' => $meta['height'],
            'size' => $file->getSize(),
            'checksum' => hash('sha256', $fileContents),
            'processing_status' => 'pending',
            'metadata' => [
                'crop' => $crop,
                'client_original_name' => $file->getClientOriginalName(),
            ],
        ]);

        $profileUpdates = [
            'cover_url' => $coverUrl,
            'cover_media_id' => $media->id,
        ];
        if ($coverPositionY !== null) {
            $profileUpdates['cover_position_y'] = max(0, min(100, $coverPositionY));
        }

        $profile->update($profileUpdates);

        // 5. Create timeline announcement post
        $postContent = $caption ?: ($user->name ?: $user->username).' কভার ছবি আপডেট করেছেন।';
        $post = Post::create([
            'user_id' => $user->id,
            'content' => $postContent,
            'type' => 'cover_update',
            'audience' => 'public',
        ]);

        // 6. SAFE DELETION: Now that the new cover is stored and DB updated,
        // remove old cover media records and physical storage files.
        foreach ($oldMediaRecords as $oldMedia) {
            $oldMedia->delete();
        }

        foreach ($oldPathsToDelete as $oldPath) {
            if ($oldPath !== $path && Storage::disk($disk)->exists($oldPath)) {
                Storage::disk($disk)->delete($oldPath);
            }
        }

        // 7. Dispatch background queue job for desktop/mobile variants & WebP optimization
        ProcessCoverJob::dispatch($media, $crop);

        // 8. Invalidate Redis Profile Cache & Record Audit Log
        $this->profileService->invalidateProfileCache($user);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'PROFILE_COVER_UPLOADED',
            'entity_type' => UserProfile::class,
            'entity_id' => $profile->id,
            'old_values' => $oldProfileValues,
            'new_values' => [
                'cover_url' => $coverUrl,
                'cover_media_id' => $media->id,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        event(new ProfileUpdatedEvent($user, ['cover'], $user));

        return [
            'cover_url' => $coverUrl,
            'cover_media_id' => $media->id,
            'cover_position_y' => $profile->fresh()->cover_position_y,
            'media' => $media->toResponseArray(),
            'post' => $post,
        ];
    }

    /**
     * Delete user cover photo and reset cover_media_id.
     *
     * @return array{message: string}
     */
    public function deleteCover(User $user): array
    {
        $oldMediaRecords = Media::where('user_id', $user->id)
            ->where('collection', 'covers')
            ->get();

        $disk = $this->storageService->getDisk();

        foreach ($oldMediaRecords as $oldMedia) {
            $paths = [
                $oldMedia->original_path,
                $oldMedia->thumbnail_path,
                $oldMedia->medium_path,
                $oldMedia->large_path,
            ];

            foreach ($paths as $p) {
                if ($p && Storage::disk($disk)->exists($p)) {
                    Storage::disk($disk)->delete($p);
                }
            }

            $oldMedia->delete();
        }

        $profile = UserProfile::where('user_id', $user->id)->first();
        if ($profile) {
            $oldValues = $profile->toArray();
            $profile->update([
                'cover_url' => null,
                'cover_media_id' => null,
                'cover_position_y' => 50,
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'PROFILE_COVER_DELETED',
                'entity_type' => UserProfile::class,
                'entity_id' => $profile->id,
                'old_values' => $oldValues,
                'new_values' => [
                    'cover_url' => null,
                    'cover_media_id' => null,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        $user->unsetRelation('profile');
        $this->profileService->invalidateProfileCache($user);
        event(new ProfileUpdatedEvent($user, ['cover'], $user));

        return [
            'message' => 'কভার ছবি সফলভাবে মুছে ফেলা হয়েছে।',
        ];
    }

    /**
     * Restore default cover.
     */
    public function restoreDefaultCover(User $user): array
    {
        return $this->deleteCover($user);
    }

    /**
     * Crop existing active cover photo.
     */
    public function cropCover(User $user, int $x, int $y, int $width, int $height): array
    {
        $media = Media::where('user_id', $user->id)
            ->where('collection', 'covers')
            ->latest()
            ->first();

        if (! $media) {
            throw ValidationException::withMessages(['crop' => ['কোনো সক্রিয় কভার ছবি পাওয়া যায়নি।']]);
        }

        $crop = [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
        ];

        ProcessCoverJob::dispatchSync($media, $crop);

        $profile = UserProfile::where('user_id', $user->id)->first();
        if ($profile) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'PROFILE_COVER_UPDATED',
                'entity_type' => UserProfile::class,
                'entity_id' => $profile->id,
                'new_values' => ['crop' => $crop],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        $this->profileService->invalidateProfileCache($user);

        return [
            'cover_url' => $user->fresh()->profile?->cover_url ?? $media->getVariantUrl('large'),
            'media' => $media->fresh()->toResponseArray(),
            'message' => 'কভার ছবি সফলভাবে ক্রপ করা হয়েছে।',
        ];
    }

    /**
     * Reposition user cover photo.
     */
    public function repositionCover(User $user, int $positionY): int
    {
        $positionY = max(0, min(100, $positionY));

        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);
        $oldPosition = $profile->cover_position_y;
        $profile->update(['cover_position_y' => $positionY]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'PROFILE_COVER_UPDATED',
            'entity_type' => UserProfile::class,
            'entity_id' => $profile->id,
            'old_values' => ['cover_position_y' => $oldPosition],
            'new_values' => ['cover_position_y' => $positionY],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->profileService->invalidateProfileCache($user);

        return $positionY;
    }

    /**
     * Inspect file server-side: magic bytes, real MIME, dimensions, script blacklist.
     *
     * @return array{mime: string, width: int, height: int, extension: string}
     */
    protected function validateAndInspectFile(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages(['file' => ['ফাইল আপলোডে সমস্যা হয়েছে।']]);
        }

        $realPath = $file->getRealPath();

        // 1. Inspect real MIME type via PHP fileinfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $realPath);
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (! array_key_exists($mime, $allowedMimes)) {
            throw ValidationException::withMessages(['file' => ['শুধুমাত্র JPEG, PNG অথবা WEBP ফরম্যাটের ছবি গ্রহণযোগ্য।']]);
        }

        // 2. Reject SVG or XML to prevent SVG XSS
        if ($mime === 'image/svg+xml' || str_contains($mime, 'xml')) {
            throw ValidationException::withMessages(['file' => ['নিরাপত্তা কারণে SVG ফরম্যাটের ছবি গ্রহণযোগ্য নয়।']]);
        }

        // 3. Inspect image dimensions and validity via getimagesize
        $imageInfo = @getimagesize($realPath);
        if ($imageInfo === false) {
            throw ValidationException::withMessages(['file' => ['ফাইলটি একটি বৈধ ছবি নয়।']]);
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        if ($width < 400 || $height < 150) {
            throw ValidationException::withMessages(['file' => ['কভার ছবির সাইজ সর্বনিম্ন ৪০০x১৫০ পিক্সেল হতে হবে।']]);
        }

        if ($width > 6000 || $height > 4000) {
            throw ValidationException::withMessages(['file' => ['কভার ছবির সাইজ সর্বোচ্চ ৬০০০x৪০০০ পিক্সেল হতে পারে।']]);
        }

        // 4. Content Scan for PHP or script code
        $sample = @file_get_contents($realPath, false, null, 0, 8192);
        if ($sample !== false) {
            $lower = strtolower($sample);
            if (
                str_contains($lower, '<?php') ||
                str_contains($lower, '<?=') ||
                str_contains($lower, '<script') ||
                str_contains($lower, '<svg')
            ) {
                throw ValidationException::withMessages(['file' => ['ফাইলটিতে অনিরাপদ কোড শনাক্ত হয়েছে।']]);
            }
        }

        return [
            'mime' => $mime,
            'width' => $width,
            'height' => $height,
            'extension' => $allowedMimes[$mime],
        ];
    }
}
