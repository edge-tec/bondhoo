<?php

namespace App\Services;

use App\Events\ProfileUpdatedEvent;
use App\Models\AuditLog;
use App\Models\ProfileInterest;
use App\Models\ProfileLanguage;
use App\Models\ProfileSkill;
use App\Models\User;
use App\Services\Security\ProfileTextSanitizer;
use Illuminate\Support\Facades\DB;

class ProfileTaxonomyService
{
    /**
     * Standard world languages list for autocomplete and lookup.
     */
    public const COMMON_LANGUAGES = [
        'Bengali',
        'English',
        'Spanish',
        'Arabic',
        'Hindi',
        'Mandarin Chinese',
        'French',
        'German',
        'Russian',
        'Portuguese',
        'Japanese',
        'Urdu',
        'Turkish',
        'Korean',
        'Italian',
        'Vietnamese',
        'Persian',
        'Dutch',
        'Polish',
        'Swedish',
        'Indonesian',
        'Malay',
        'Thai',
        'Greek',
    ];

    public function __construct(
        protected ProfileService $profileService
    ) {}

    // ==========================================
    // 1. SKILLS MANAGEMENT
    // ==========================================

    /**
     * Get user's skills respecting privacy settings.
     */
    public function getSkills(User $targetUser, ?User $viewer = null): array
    {
        $privacyService = app(ProfilePrivacyService::class);
        if (! $privacyService->canViewProfile($targetUser, $viewer)) {
            abort(403, 'এই প্রোফাইলটি ব্যক্তিগত অথবা দেখার অনুমতি আপনার নেই।');
        }

        $isAdmin = $privacyService->isAdmin($viewer);
        $isOwner = $viewer && $viewer->id === $targetUser->id;

        if (! $isAdmin && ! $isOwner && ! $privacyService->canViewSkills($targetUser, $viewer)) {
            return [];
        }

        return $targetUser->skills()
            ->orderBy('display_order', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn (ProfileSkill $item) => $this->formatSkillItem($item))
            ->toArray();
    }

    public function formatSkillItem(ProfileSkill $item): array
    {
        return [
            'id' => $item->id,
            'user_id' => $item->user_id,
            'name' => $item->name,
            'level' => $item->level ?? 'beginner',
            'endorsements_count' => (int) $item->endorsements_count,
            'display_order' => (int) $item->display_order,
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }

    /**
     * Add a skill to user's profile.
     */
    public function addSkill(
        User $targetUser,
        array $data,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileSkill {
        return DB::transaction(function () use ($targetUser, $data, $actor, $ip, $userAgent) {
            $name = ProfileTextSanitizer::sanitizePlainText($data['name'], false);
            $maxOrder = (int) $targetUser->skills()->max('display_order');
            $displayOrder = isset($data['display_order']) ? (int) $data['display_order'] : $maxOrder + 1;

            $skill = $targetUser->skills()->create([
                'name' => $name,
                'level' => $data['level'] ?? 'beginner',
                'display_order' => $displayOrder,
            ]);

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'SKILL_ADDED',
                'entity_type' => ProfileSkill::class,
                'entity_id' => $skill->id,
                'old_values' => null,
                'new_values' => $skill->toArray(),
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // Dispatch Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['skills'],
                actor: $actor
            ));

            return $skill;
        });
    }

    /**
     * Remove a skill from user's profile.
     */
    public function removeSkill(
        ProfileSkill $skill,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        DB::transaction(function () use ($skill, $actor, $ip, $userAgent) {
            $oldValues = $skill->toArray();
            $targetUser = $skill->user;

            $skill->delete();

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'SKILL_REMOVED',
                'entity_type' => ProfileSkill::class,
                'entity_id' => $skill->id,
                'old_values' => $oldValues,
                'new_values' => null,
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // Dispatch Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['skills'],
                actor: $actor
            ));
        });
    }

    /**
     * Reorder user's skills.
     */
    public function reorderSkills(
        User $targetUser,
        array $orders,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): array {
        return DB::transaction(function () use ($targetUser, $orders, $actor, $ip, $userAgent) {
            $normalizedOrders = [];

            if (isset($orders['ids']) && is_array($orders['ids'])) {
                foreach ($orders['ids'] as $index => $id) {
                    $normalizedOrders[$id] = $index + 1;
                }
            } elseif (isset($orders['orders']) && is_array($orders['orders'])) {
                foreach ($orders['orders'] as $item) {
                    if (isset($item['id'], $item['display_order'])) {
                        $normalizedOrders[$item['id']] = (int) $item['display_order'];
                    }
                }
            } elseif (isset($orders['items']) && is_array($orders['items'])) {
                foreach ($orders['items'] as $item) {
                    if (isset($item['id'], $item['display_order'])) {
                        $normalizedOrders[$item['id']] = (int) $item['display_order'];
                    }
                }
            }

            foreach ($normalizedOrders as $id => $order) {
                ProfileSkill::where('id', $id)
                    ->where('user_id', $targetUser->id)
                    ->update(['display_order' => $order]);
            }

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'SKILL_REORDERED',
                'entity_type' => ProfileSkill::class,
                'entity_id' => $targetUser->id,
                'old_values' => null,
                'new_values' => ['reordered' => $normalizedOrders],
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // Dispatch Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['skills'],
                actor: $actor
            ));

            return $this->getSkills($targetUser);
        });
    }

    /**
     * Search distinct skills across platform.
     */
    public function searchSkills(string $query, int $limit = 20): array
    {
        $term = trim($query);
        if ($term === '') {
            return [];
        }

        return ProfileSkill::query()
            ->search($term)
            ->select('name', DB::raw('COUNT(*) as count'))
            ->groupBy('name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'count' => (int) $r->count,
            ])
            ->toArray();
    }

    // ==========================================
    // 2. INTERESTS MANAGEMENT
    // ==========================================

    /**
     * Get user's interests respecting privacy settings.
     */
    public function getInterests(User $targetUser, ?User $viewer = null): array
    {
        $privacyService = app(ProfilePrivacyService::class);
        if (! $privacyService->canViewProfile($targetUser, $viewer)) {
            abort(403, 'এই প্রোফাইলটি ব্যক্তিগত অথবা দেখার অনুমতি আপনার নেই।');
        }

        $isAdmin = $privacyService->isAdmin($viewer);
        $isOwner = $viewer && $viewer->id === $targetUser->id;

        if (! $isAdmin && ! $isOwner && ! $privacyService->canViewInterests($targetUser, $viewer)) {
            return [];
        }

        return $targetUser->interests()
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn (ProfileInterest $item) => $this->formatInterestItem($item))
            ->toArray();
    }

    public function formatInterestItem(ProfileInterest $item): array
    {
        return [
            'id' => $item->id,
            'user_id' => $item->user_id,
            'name' => $item->name,
            'category' => $item->category,
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }

    /**
     * Add an interest to user's profile.
     */
    public function addInterest(
        User $targetUser,
        array $data,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileInterest {
        return DB::transaction(function () use ($targetUser, $data, $actor, $ip, $userAgent) {
            $name = ProfileTextSanitizer::sanitizePlainText($data['name'], false);
            $category = isset($data['category']) ? ProfileTextSanitizer::sanitizePlainText($data['category'], false) : null;

            $interest = $targetUser->interests()->create([
                'name' => $name,
                'category' => $category,
            ]);

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'INTEREST_ADDED',
                'entity_type' => ProfileInterest::class,
                'entity_id' => $interest->id,
                'old_values' => null,
                'new_values' => $interest->toArray(),
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // Dispatch Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['interests'],
                actor: $actor
            ));

            return $interest;
        });
    }

    /**
     * Remove an interest from user's profile.
     */
    public function removeInterest(
        ProfileInterest $interest,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        DB::transaction(function () use ($interest, $actor, $ip, $userAgent) {
            $oldValues = $interest->toArray();
            $targetUser = $interest->user;

            $interest->delete();

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'INTEREST_REMOVED',
                'entity_type' => ProfileInterest::class,
                'entity_id' => $interest->id,
                'old_values' => $oldValues,
                'new_values' => null,
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // Dispatch Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['interests'],
                actor: $actor
            ));
        });
    }

    /**
     * Search distinct interests across platform.
     */
    public function searchInterests(string $query, int $limit = 20): array
    {
        $term = trim($query);
        if ($term === '') {
            return [];
        }

        return ProfileInterest::query()
            ->search($term)
            ->select('name', 'category', DB::raw('COUNT(*) as count'))
            ->groupBy('name', 'category')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'category' => $r->category,
                'count' => (int) $r->count,
            ])
            ->toArray();
    }

    // ==========================================
    // 3. LANGUAGES MANAGEMENT
    // ==========================================

    /**
     * Get user's languages respecting privacy settings.
     */
    public function getLanguages(User $targetUser, ?User $viewer = null): array
    {
        $privacyService = app(ProfilePrivacyService::class);
        if (! $privacyService->canViewProfile($targetUser, $viewer)) {
            abort(403, 'এই প্রোফাইলটি ব্যক্তিগত অথবা দেখার অনুমতি আপনার নেই।');
        }

        $isAdmin = $privacyService->isAdmin($viewer);
        $isOwner = $viewer && $viewer->id === $targetUser->id;

        if (! $isAdmin && ! $isOwner && ! $privacyService->canViewLanguages($targetUser, $viewer)) {
            return [];
        }

        return $targetUser->languages()
            ->orderBy('language', 'asc')
            ->get()
            ->map(fn (ProfileLanguage $item) => $this->formatLanguageItem($item))
            ->toArray();
    }

    public function formatLanguageItem(ProfileLanguage $item): array
    {
        return [
            'id' => $item->id,
            'user_id' => $item->user_id,
            'language' => $item->language,
            'proficiency' => $item->proficiency,
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }

    /**
     * Add a language to user's profile.
     */
    public function addLanguage(
        User $targetUser,
        array $data,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileLanguage {
        return DB::transaction(function () use ($targetUser, $data, $actor, $ip, $userAgent) {
            $language = ProfileTextSanitizer::sanitizePlainText($data['language'], false);
            $proficiency = strtolower(trim($data['proficiency']));

            $item = $targetUser->languages()->create([
                'language' => $language,
                'proficiency' => $proficiency,
            ]);

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'LANGUAGE_ADDED',
                'entity_type' => ProfileLanguage::class,
                'entity_id' => $item->id,
                'old_values' => null,
                'new_values' => $item->toArray(),
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // Dispatch Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['languages'],
                actor: $actor
            ));

            return $item;
        });
    }

    /**
     * Update an existing language record.
     */
    public function updateLanguage(
        ProfileLanguage $language,
        array $data,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileLanguage {
        return DB::transaction(function () use ($language, $data, $actor, $ip, $userAgent) {
            $oldValues = $language->toArray();
            $targetUser = $language->user;

            $updates = [];
            if (isset($data['language'])) {
                $updates['language'] = ProfileTextSanitizer::sanitizePlainText($data['language'], false);
            }
            if (isset($data['proficiency'])) {
                $updates['proficiency'] = strtolower(trim($data['proficiency']));
            }

            $language->update($updates);
            $fresh = $language->fresh();

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'LANGUAGE_UPDATED',
                'entity_type' => ProfileLanguage::class,
                'entity_id' => $language->id,
                'old_values' => $oldValues,
                'new_values' => $fresh->toArray(),
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // Dispatch Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['languages'],
                actor: $actor
            ));

            return $fresh;
        });
    }

    /**
     * Remove a language from user's profile.
     */
    public function removeLanguage(
        ProfileLanguage $language,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        DB::transaction(function () use ($language, $actor, $ip, $userAgent) {
            $oldValues = $language->toArray();
            $targetUser = $language->user;

            $language->delete();

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'LANGUAGE_REMOVED',
                'entity_type' => ProfileLanguage::class,
                'entity_id' => $language->id,
                'old_values' => $oldValues,
                'new_values' => null,
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // Dispatch Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['languages'],
                actor: $actor
            ));
        });
    }

    /**
     * Search languages from common list & platform database.
     */
    public function searchLanguages(string $query, int $limit = 20): array
    {
        $term = strtolower(trim($query));
        if ($term === '') {
            return array_slice(self::COMMON_LANGUAGES, 0, $limit);
        }

        // 1. Matches from standard common languages list
        $standardMatches = array_values(array_filter(
            self::COMMON_LANGUAGES,
            fn ($lang) => str_contains(strtolower($lang), $term)
        ));

        // 2. Matches from platform database
        $dbMatches = ProfileLanguage::query()
            ->search($term)
            ->distinct()
            ->pluck('language')
            ->toArray();

        // Merge, unique, limit
        $merged = array_values(array_unique(array_merge($standardMatches, $dbMatches)));

        return array_slice($merged, 0, $limit);
    }
}
