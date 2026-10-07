<?php

namespace App\Services;

use App\Models\ProfileCompletion;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ProfileCompletionService
{
    /**
     * Cache TTL in seconds (24 hours).
     */
    public const CACHE_TTL = 86400;

    /**
     * Map of section keys to human-readable English / Bengali labels.
     */
    public const SECTION_LABELS = [
        ProfileCompletion::SECTION_NAME => 'Name',
        ProfileCompletion::SECTION_USERNAME => 'Username',
        ProfileCompletion::SECTION_AVATAR => 'Profile Photo',
        ProfileCompletion::SECTION_COVER => 'Cover Photo',
        ProfileCompletion::SECTION_BIO => 'Bio',
        ProfileCompletion::SECTION_ABOUT => 'About',
        ProfileCompletion::SECTION_LOCATION => 'Location',
        ProfileCompletion::SECTION_EDUCATION => 'Education',
        ProfileCompletion::SECTION_WORK => 'Work Experience',
        ProfileCompletion::SECTION_SKILLS => 'Skills',
        ProfileCompletion::SECTION_INTERESTS => 'Interests',
        ProfileCompletion::SECTION_LANGUAGES => 'Languages',
        ProfileCompletion::SECTION_SOCIAL_LINKS => 'Social Links',
    ];

    /**
     * Get completion data for a user with caching.
     *
     * @return array{percentage: int, completed_items: array<string>, remaining_items: array<string>, completed_count: int, total_count: int, sections: array<string, bool>, last_calculated_at: ?string}
     */
    public function getCompletion(User $user): array
    {
        $cacheKey = $this->getCacheKey($user->id);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return $this->calculate($user, true);
        });
    }

    /**
     * Dynamically calculate completion based on actual database records.
     *
     * @return array{percentage: int, completed_items: array<string>, remaining_items: array<string>, completed_count: int, total_count: int, sections: array<string, bool>, last_calculated_at: string}
     */
    public function calculate(User $user, bool $persist = true): array
    {
        $profile = $user->profile;

        // 1. Name
        $hasName = ! empty(trim((string) $user->name));

        // 2. Username
        $hasUsername = ! empty(trim((string) $user->username));

        // 3. Avatar (custom avatar media id or valid non-default avatar URL)
        $hasAvatar = false;
        if ($profile) {
            if (! empty($profile->avatar_media_id)) {
                $hasAvatar = true;
            } elseif (! empty($profile->avatar_url) && ! str_contains($profile->avatar_url, 'default-avatar') && ! str_contains($profile->avatar_url, 'ui-avatars.com')) {
                $hasAvatar = true;
            }
        }

        // 4. Cover (custom cover media id or valid non-default cover URL)
        $hasCover = false;
        if ($profile) {
            if (! empty($profile->cover_media_id)) {
                $hasCover = true;
            } elseif (! empty($profile->cover_url) && ! str_contains($profile->cover_url, 'default-cover')) {
                $hasCover = true;
            }
        }

        // 5. Bio
        $hasBio = $profile && ! empty(trim((string) $profile->bio));

        // 6. About
        $hasAbout = $profile && ! empty(trim((string) $profile->about));

        // 7. Location (Country, City, or Location on user or profile)
        $hasLocation = ! empty(trim((string) $user->country));
        if (! $hasLocation && $profile) {
            $hasLocation = ! empty(trim((string) ($profile->city ?? '')))
                || ! empty(trim((string) ($profile->location ?? '')))
                || ! empty(trim((string) ($profile->country ?? '')));
        }

        // 8. Education (at least one education record or legacy profile education)
        $hasEducation = $user->educations()->exists()
            || ($profile && ! empty(trim((string) ($profile->education ?? ''))));

        // 9. Work (at least one experience record or legacy profile work)
        $hasWork = $user->workExperiences()->exists()
            || ($profile && ! empty(trim((string) ($profile->work ?? ''))));

        // 10. Skills (at least one skill recorded in database)
        $hasSkills = $user->skills()->exists();

        // 11. Interests (at least one interest recorded in database)
        $hasInterests = $user->interests()->exists();

        // 12. Languages (at least one language recorded in database)
        $hasLanguages = $user->languages()->exists();

        // 13. Social Links (at least one social link recorded in database)
        $hasSocialLinks = $user->socialLinks()->exists();

        $sectionsMap = [
            ProfileCompletion::SECTION_NAME => $hasName,
            ProfileCompletion::SECTION_USERNAME => $hasUsername,
            ProfileCompletion::SECTION_AVATAR => $hasAvatar,
            ProfileCompletion::SECTION_COVER => $hasCover,
            ProfileCompletion::SECTION_BIO => $hasBio,
            ProfileCompletion::SECTION_ABOUT => $hasAbout,
            ProfileCompletion::SECTION_LOCATION => $hasLocation,
            ProfileCompletion::SECTION_EDUCATION => $hasEducation,
            ProfileCompletion::SECTION_WORK => $hasWork,
            ProfileCompletion::SECTION_SKILLS => $hasSkills,
            ProfileCompletion::SECTION_INTERESTS => $hasInterests,
            ProfileCompletion::SECTION_LANGUAGES => $hasLanguages,
            ProfileCompletion::SECTION_SOCIAL_LINKS => $hasSocialLinks,
        ];

        $completedItems = [];
        $remainingItems = [];

        foreach ($sectionsMap as $sectionKey => $isCompleted) {
            if ($isCompleted) {
                $completedItems[] = $sectionKey;
            } else {
                $remainingItems[] = $sectionKey;
            }
        }

        $totalSections = count($sectionsMap);
        $completedCount = count($completedItems);
        $percentage = (int) round(($completedCount / $totalSections) * 100);
        $now = now();

        if ($persist) {
            ProfileCompletion::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'completion_percentage' => $percentage,
                    'has_name' => $hasName,
                    'has_username' => $hasUsername,
                    'has_avatar' => $hasAvatar,
                    'has_cover' => $hasCover,
                    'has_bio' => $hasBio,
                    'has_about' => $hasAbout,
                    'has_education' => $hasEducation,
                    'has_experience' => $hasWork,
                    'has_skills' => $hasSkills,
                    'has_interests' => $hasInterests,
                    'has_languages' => $hasLanguages,
                    'has_social_links' => $hasSocialLinks,
                    'has_location' => $hasLocation,
                    'completed_sections' => $completedItems,
                    'missing_sections' => $remainingItems,
                    'total_sections' => $totalSections,
                    'completed_count' => $completedCount,
                    'last_calculated_at' => $now,
                ]
            );
        }

        return [
            'percentage' => $percentage,
            'completed_items' => $completedItems,
            'remaining_items' => $remainingItems,
            'completed_count' => $completedCount,
            'total_count' => $totalSections,
            'sections' => $sectionsMap,
            'last_calculated_at' => $now->toIso8601String(),
        ];
    }

    /**
     * Recalculate and update cache immediately.
     */
    public function recalculate(User $user): array
    {
        $this->invalidate($user);

        return $this->getCompletion($user);
    }

    /**
     * Invalidate completion cache for a given user.
     */
    public function invalidate(User|int|string $user): void
    {
        $userId = null;

        if ($user instanceof User) {
            $userId = $user->id;
        } elseif (is_numeric($user)) {
            $userId = (int) $user;
        } else {
            $found = User::where('username', strtolower($user))->first();
            $userId = $found?->id;
        }

        if ($userId) {
            Cache::forget($this->getCacheKey($userId));
        }
    }

    protected function getCacheKey(int $userId): string
    {
        return "profile_completion_{$userId}";
    }
}
