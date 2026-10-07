<?php

namespace App\Services;

use App\Events\ProfileUpdatedEvent;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Security\ProfileTextSanitizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BioAboutService
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Retrieve Bio, Headline, and About information for a user.
     */
    public function getBioAbout(User $targetUser, ?User $viewer = null): array
    {
        $isOwner = $viewer && $viewer->id === $targetUser->id;
        $isFriend = $viewer && in_array($viewer->id, $targetUser->getFriendIds(), true);
        $isLocked = (bool) ($targetUser->settings?->is_profile_locked ?? false);
        $isLockedView = $isLocked && ! $isFriend && ! $isOwner;

        $username = strtolower($targetUser->username);

        $privacyService = app(ProfilePrivacyService::class);
        if (! $privacyService->canViewProfile($targetUser, $viewer)) {
            abort(403, 'এই প্রোফাইলটি ব্যক্তিগত অথবা দেখার অনুমতি আপনার নেই।');
        }

        $canViewBio = $privacyService->canViewBio($targetUser, $viewer);
        $canViewAbout = $privacyService->canViewAbout($targetUser, $viewer) && ! $isLockedView;

        // For public stranger view, utilize cache
        if (! $viewer && ! $isLocked) {
            return Cache::remember("profile:about:{$username}", 3600, function () use ($targetUser, $canViewBio, $canViewAbout) {
                return $this->buildBioAboutPayload($targetUser, $canViewBio, $canViewAbout);
            });
        }

        return $this->buildBioAboutPayload($targetUser, $canViewBio, $canViewAbout);
    }

    /**
     * Build the Bio and About payload array.
     */
    protected function buildBioAboutPayload(User $targetUser, bool $canViewBio, bool $canViewAbout): array
    {
        $profile = $targetUser->profile;

        return [
            'user_id' => $targetUser->id,
            'username' => $targetUser->username,
            'name' => $targetUser->name ?: $targetUser->username,
            'bio' => $canViewBio ? $profile?->bio : null,
            'headline' => $profile?->headline,
            'intro' => $profile?->headline,
            'about' => $canViewAbout ? $profile?->about : null,
            'character_limits' => [
                'bio_max' => 255,
                'headline_max' => 191,
                'about_max' => 5000,
            ],
            'is_profile_locked' => (bool) ($targetUser->settings?->is_profile_locked ?? false),
        ];
    }

    /**
     * Update Bio, Headline/Introduction, and About for a target user with sanitization and audit logging.
     */
    public function updateBioAbout(
        User $targetUser,
        array $data,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): array {
        return DB::transaction(function () use ($targetUser, $data, $actor, $ip, $userAgent) {
            /** @var UserProfile $profile */
            $profile = $targetUser->profile ?: $targetUser->profile()->create();

            $oldValues = [
                'bio' => $profile->bio,
                'headline' => $profile->headline,
                'about' => $profile->about,
            ];

            $updates = [];
            $updatedFields = [];

            // 1. Process Bio (Short Bio - Plain text)
            if (array_key_exists('bio', $data)) {
                $sanitizedBio = ProfileTextSanitizer::sanitizePlainText($data['bio'], true);
                $updates['bio'] = $sanitizedBio;
                $updatedFields[] = 'bio';
            }

            // 2. Process Headline / Introduction (Profile Introduction - Plain text, single line)
            $headlineInput = null;
            if (array_key_exists('headline', $data)) {
                $headlineInput = $data['headline'];
            } elseif (array_key_exists('intro', $data)) {
                $headlineInput = $data['intro'];
            } elseif (array_key_exists('introduction', $data)) {
                $headlineInput = $data['introduction'];
            }

            if ($headlineInput !== null || array_key_exists('headline', $data) || array_key_exists('intro', $data) || array_key_exists('introduction', $data)) {
                $sanitizedHeadline = ProfileTextSanitizer::sanitizePlainText($headlineInput, false);
                $updates['headline'] = $sanitizedHeadline;
                $updatedFields[] = 'headline';
            }

            // 3. Process About (Long About - Safe Rich HTML/Formatted text)
            if (array_key_exists('about', $data)) {
                $sanitizedAbout = ProfileTextSanitizer::sanitizeRichAbout($data['about']);
                $updates['about'] = $sanitizedAbout;
                $updatedFields[] = 'about';
            }

            // Persist profile updates
            if (! empty($updates)) {
                $profile->update($updates);
            }

            $freshProfile = $profile->fresh();

            // 4. Record Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'BIO_ABOUT_UPDATED',
                'entity_type' => UserProfile::class,
                'entity_id' => $profile->id,
                'old_values' => $oldValues,
                'new_values' => [
                    'bio' => $freshProfile->bio,
                    'headline' => $freshProfile->headline,
                    'about' => $freshProfile->about,
                    'updated_fields' => $updatedFields,
                ],
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // 5. Invalidate Profile Caches
            $this->profileService->invalidateProfileCache($targetUser);

            // 6. Dispatch Profile Updated Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['about'],
                actor: $actor
            ));

            return [
                'user_id' => $targetUser->id,
                'username' => $targetUser->username,
                'bio' => $freshProfile->bio,
                'headline' => $freshProfile->headline,
                'intro' => $freshProfile->headline,
                'about' => $freshProfile->about,
                'profile_completion_percentage' => $freshProfile->calculateCompletionPercentage(),
                'updated_fields' => $updatedFields,
            ];
        });
    }
}
