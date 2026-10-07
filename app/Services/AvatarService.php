<?php

namespace App\Services;

use App\Events\ProfileUpdatedEvent;
use App\Jobs\ProcessAvatarJob;
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

class AvatarService
{
    public function __construct(
        protected MediaStorageServiceInterface $storageService,
        protected ProfileService $profileService
    ) {}

    /**
     * Upload or replace profile avatar with deep security inspection,
     * deferred safe deletion of old avatars, and queue processing.
     *
     * @return array{avatar_url: string, media: array, post: Post}
     */
    public function uploadAvatar(User $user, UploadedFile $file, ?array $crop = null, ?string $caption = null): array
    {
        // 1. Deep Security & MIME validation (never trust client MIME)
        $meta = $this->validateAndInspectFile($file);

        // 2. Identify old avatar media and paths for safe deferred deletion
        $oldMediaRecords = Media::where('user_id', $user->id)
            ->where('collection', 'avatars')
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

        // Also check if current profile avatar_url references a local disk path not tracked in media
        $currentAvatarUrl = $user->profile?->avatar_url;

        // 3. Generate enterprise path and store the new original file
        $disk = $this->storageService->getDisk();
        $ext = $meta['extension'];
        $path = $this->storageService->generatePath($user->id, 'profile', $ext);

        $fileContents = file_get_contents($file->getRealPath());
        if ($fileContents === false) {
            throw ValidationException::withMessages(['file' => ['ফাইল রিড করা সম্ভব হয়নি।']]);
        }

        $stored = Storage::disk($disk)->put($path, $fileContents, 'public');
        if (! $stored) {
            throw new Exception("Failed to write avatar to storage disk [{$disk}] at path [{$path}].");
        }

        $avatarUrl = $this->storageService->getUrl($path);

        // 4. Update Profile & Create Media record
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);
        $oldProfileValues = $profile->toArray();
        $profile->update(['avatar_url' => $avatarUrl]);

        $media = Media::create([
            'user_id' => $user->id,
            'mediable_type' => UserProfile::class,
            'mediable_id' => $profile->id,
            'collection' => 'avatars',
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

        // 5. Create timeline announcement post
        $postContent = $caption ?: ($user->name ?: $user->username).' প্রোফাইল ছবি আপডেট করেছেন।';
        $post = Post::create([
            'user_id' => $user->id,
            'content' => $postContent,
            'type' => 'avatar_update',
            'audience' => 'public',
        ]);

        // 6. SAFE DELETION: Now that the new avatar is safely stored and DB updated,
        // remove old avatar media records and physical storage files.
        foreach ($oldMediaRecords as $oldMedia) {
            $oldMedia->delete();
        }

        foreach ($oldPathsToDelete as $oldPath) {
            if ($oldPath !== $path && Storage::disk($disk)->exists($oldPath)) {
                Storage::disk($disk)->delete($oldPath);
            }
        }

        // 7. Dispatch background queue job for resizing, thumbnails & WebP optimization
        ProcessAvatarJob::dispatch($media, $crop);

        // 8. Invalidate Redis Profile Cache & Record Audit Log
        $this->profileService->invalidateProfileCache($user);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'avatar.uploaded',
            'entity_type' => UserProfile::class,
            'entity_id' => $profile->id,
            'old_values' => $oldProfileValues,
            'new_values' => ['avatar_url' => $avatarUrl, 'media_id' => $media->id],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        event(new ProfileUpdatedEvent($user, ['avatar'], $user));

        return [
            'avatar_url' => $avatarUrl,
            'media' => $media->toResponseArray(),
            'post' => $post,
        ];
    }

    /**
     * Delete user avatar and restore default placeholder.
     *
     * @return array{default_avatar_url: string, message: string}
     */
    public function deleteAvatar(User $user): array
    {
        $oldMediaRecords = Media::where('user_id', $user->id)
            ->where('collection', 'avatars')
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
            $profile->update(['avatar_url' => null]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'avatar.deleted',
                'entity_type' => UserProfile::class,
                'entity_id' => $profile->id,
                'old_values' => $oldValues,
                'new_values' => ['avatar_url' => null],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        $user->unsetRelation('profile');

        $this->profileService->invalidateProfileCache($user);
        event(new ProfileUpdatedEvent($user, ['avatar'], $user));

        return [
            'default_avatar_url' => $this->getDefaultAvatarUrl($user),
            'message' => 'প্রোফাইল ছবি সফলভাবে মুছে ফেলা হয়েছে।',
        ];
    }

    /**
     * Restore default avatar.
     */
    public function restoreDefaultAvatar(User $user): array
    {
        return $this->deleteAvatar($user);
    }

    /**
     * Crop existing active avatar and re-trigger queue optimization.
     */
    public function cropAvatar(User $user, int $x, int $y, int $width, int $height): array
    {
        $media = Media::where('user_id', $user->id)
            ->where('collection', 'avatars')
            ->latest()
            ->first();

        if (! $media) {
            throw ValidationException::withMessages(['crop' => ['কোনো সক্রিয় প্রোফাইল ছবি পাওয়া যায়নি।']]);
        }

        $crop = [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
        ];

        ProcessAvatarJob::dispatchSync($media, $crop);

        $this->profileService->invalidateProfileCache($user);

        return [
            'avatar_url' => $user->fresh()->profile?->avatar_url ?? $media->getVariantUrl('original'),
            'media' => $media->fresh()->toResponseArray(),
            'message' => 'প্রোফাইল ছবি সফলভাবে ক্রপ করা হয়েছে।',
        ];
    }

    /**
     * Get platform standard default avatar URL.
     */
    public function getDefaultAvatarUrl(User $user): string
    {
        $displayName = $user->profile?->display_name ?: $user->name ?: $user->username;

        return 'https://ui-avatars.com/api/?name='.urlencode($displayName).'&background=1877f2&color=fff&size=256';
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

        if ($width < 100 || $height < 100) {
            throw ValidationException::withMessages(['file' => ['ছবির সাইজ সর্বনিম্ন ১০০x১০০ পিক্সেল হতে হবে।']]);
        }

        if ($width > 6000 || $height > 6000) {
            throw ValidationException::withMessages(['file' => ['ছবির সাইজ সর্বোচ্চ ৬০০০x৬০০০ পিক্সেল হতে পারে।']]);
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
