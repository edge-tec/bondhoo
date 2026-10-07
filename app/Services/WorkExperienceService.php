<?php

namespace App\Services;

use App\Events\ProfileUpdatedEvent;
use App\Models\AuditLog;
use App\Models\ProfileExperience;
use App\Models\User;
use App\Services\Security\ProfileTextSanitizer;
use Illuminate\Support\Facades\DB;

class WorkExperienceService
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Get work experiences for target user, filtered by viewer privacy.
     */
    public function getWorkExperiences(User $targetUser, ?User $viewer = null): array
    {
        $privacyService = app(ProfilePrivacyService::class);
        if (! $privacyService->canViewProfile($targetUser, $viewer)) {
            abort(403, 'এই প্রোফাইলটি ব্যক্তিগত অথবা দেখার অনুমতি আপনার নেই।');
        }

        $isAdmin = $privacyService->isAdmin($viewer);
        $isOwner = $viewer && $viewer->id === $targetUser->id;

        if (! $isAdmin && ! $isOwner && ! $privacyService->canViewWork($targetUser, $viewer)) {
            return [];
        }

        $isFriend = $viewer && in_array($viewer->id, $targetUser->getFriendIds(), true);
        $isLocked = (bool) ($targetUser->settings?->is_profile_locked ?? false);
        $isLockedView = $isLocked && ! $isFriend && ! $isOwner && ! $isAdmin;

        if ($isLockedView) {
            return [];
        }

        $records = $targetUser->experiences()
            ->visibleFor($viewer)
            ->orderBy('display_order', 'asc')
            ->orderBy('start_date', 'desc')
            ->get();

        return $records->map(fn (ProfileExperience $item) => $this->formatExperienceItem($item))->toArray();
    }

    /**
     * Format a work experience record array.
     */
    public function formatExperienceItem(ProfileExperience $item): array
    {
        return [
            'id' => $item->id,
            'user_id' => $item->user_id,
            'company' => $item->company_name,
            'company_name' => $item->company_name,
            'position' => $item->job_title,
            'job_title' => $item->job_title,
            'employment_type' => $item->employment_type,
            'location' => $item->location,
            'is_remote' => (bool) $item->is_remote,
            'start_date' => $item->start_date?->format('Y-m-d'),
            'end_date' => $item->end_date?->format('Y-m-d'),
            'is_current' => (bool) $item->is_current,
            'currently_working' => (bool) $item->is_current,
            'description' => $item->description,
            'privacy' => $item->privacy ?? 'public',
            'display_order' => (int) $item->display_order,
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Create a new work experience record.
     */
    public function createWorkExperience(
        User $targetUser,
        array $data,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileExperience {
        return DB::transaction(function () use ($targetUser, $data, $actor, $ip, $userAgent) {
            $isCurrent = (bool) ($data['is_current'] ?? $data['currently_working'] ?? false);

            $maxOrder = (int) $targetUser->experiences()->max('display_order');
            $displayOrder = isset($data['display_order']) ? (int) $data['display_order'] : $maxOrder + 1;

            $experience = $targetUser->experiences()->create([
                'company_name' => ProfileTextSanitizer::sanitizePlainText($data['company_name'] ?? $data['company'] ?? null, false),
                'job_title' => ProfileTextSanitizer::sanitizePlainText($data['job_title'] ?? $data['position'] ?? null, false),
                'employment_type' => $data['employment_type'] ?? null,
                'location' => ProfileTextSanitizer::sanitizePlainText($data['location'] ?? null, false),
                'is_remote' => (bool) ($data['is_remote'] ?? false),
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $isCurrent ? null : ($data['end_date'] ?? null),
                'is_current' => $isCurrent,
                'description' => ProfileTextSanitizer::sanitizePlainText($data['description'] ?? null, true),
                'privacy' => $data['privacy'] ?? 'public',
                'display_order' => $displayOrder,
            ]);

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'WORK_EXPERIENCE_CREATED',
                'entity_type' => ProfileExperience::class,
                'entity_id' => $experience->id,
                'old_values' => null,
                'new_values' => $experience->toArray(),
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // Dispatch Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['experiences'],
                actor: $actor
            ));

            return $experience;
        });
    }

    /**
     * Update an existing work experience record.
     */
    public function updateWorkExperience(
        ProfileExperience $experience,
        array $data,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileExperience {
        return DB::transaction(function () use ($experience, $data, $actor, $ip, $userAgent) {
            $oldValues = $experience->toArray();
            $targetUser = $experience->user;

            $updates = [];

            if (array_key_exists('company_name', $data) || array_key_exists('company', $data)) {
                $updates['company_name'] = ProfileTextSanitizer::sanitizePlainText($data['company_name'] ?? $data['company'], false);
            }
            if (array_key_exists('job_title', $data) || array_key_exists('position', $data)) {
                $updates['job_title'] = ProfileTextSanitizer::sanitizePlainText($data['job_title'] ?? $data['position'], false);
            }
            if (array_key_exists('employment_type', $data)) {
                $updates['employment_type'] = $data['employment_type'];
            }
            if (array_key_exists('location', $data)) {
                $updates['location'] = ProfileTextSanitizer::sanitizePlainText($data['location'], false);
            }
            if (array_key_exists('is_remote', $data)) {
                $updates['is_remote'] = (bool) $data['is_remote'];
            }
            if (array_key_exists('start_date', $data)) {
                $updates['start_date'] = $data['start_date'];
            }
            if (array_key_exists('is_current', $data) || array_key_exists('currently_working', $data)) {
                $isCurrent = (bool) ($data['is_current'] ?? $data['currently_working']);
                $updates['is_current'] = $isCurrent;
                if ($isCurrent) {
                    $updates['end_date'] = null;
                }
            }
            if (array_key_exists('end_date', $data)) {
                if (! ($updates['is_current'] ?? $experience->is_current)) {
                    $updates['end_date'] = $data['end_date'];
                }
            }
            if (array_key_exists('description', $data)) {
                $updates['description'] = ProfileTextSanitizer::sanitizePlainText($data['description'], true);
            }
            if (array_key_exists('privacy', $data)) {
                $updates['privacy'] = $data['privacy'];
            }
            if (array_key_exists('display_order', $data)) {
                $updates['display_order'] = (int) $data['display_order'];
            }

            $experience->update($updates);
            $fresh = $experience->fresh();

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'WORK_EXPERIENCE_UPDATED',
                'entity_type' => ProfileExperience::class,
                'entity_id' => $experience->id,
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
                updatedSections: ['experiences'],
                actor: $actor
            ));

            return $fresh;
        });
    }

    /**
     * Delete a work experience record.
     */
    public function deleteWorkExperience(
        ProfileExperience $experience,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        DB::transaction(function () use ($experience, $actor, $ip, $userAgent) {
            $oldValues = $experience->toArray();
            $targetUser = $experience->user;

            $experience->delete();

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'WORK_EXPERIENCE_DELETED',
                'entity_type' => ProfileExperience::class,
                'entity_id' => $experience->id,
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
                updatedSections: ['experiences'],
                actor: $actor
            ));
        });
    }

    /**
     * Reorder user's work experience records.
     */
    public function reorderWorkExperiences(
        User $targetUser,
        array $orders,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): array {
        return DB::transaction(function () use ($targetUser, $orders, $actor, $ip, $userAgent) {
            $normalizedOrders = [];

            // Case A: array of IDs in order [3, 1, 2]
            if (isset($orders['ids']) && is_array($orders['ids'])) {
                foreach ($orders['ids'] as $index => $id) {
                    $normalizedOrders[$id] = $index + 1;
                }
            } elseif (isset($orders['orders']) && is_array($orders['orders'])) {
                // Case B: array of objects [['id' => 1, 'display_order' => 2], ...]
                foreach ($orders['orders'] as $item) {
                    if (isset($item['id'], $item['display_order'])) {
                        $normalizedOrders[$item['id']] = (int) $item['display_order'];
                    }
                }
            } elseif (isset($orders['items']) && is_array($orders['items'])) {
                // Case C: array of items [['id' => 1, 'display_order' => 2], ...]
                foreach ($orders['items'] as $item) {
                    if (isset($item['id'], $item['display_order'])) {
                        $normalizedOrders[$item['id']] = (int) $item['display_order'];
                    }
                }
            }

            foreach ($normalizedOrders as $id => $order) {
                ProfileExperience::where('id', $id)
                    ->where('user_id', $targetUser->id)
                    ->update(['display_order' => $order]);
            }

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'WORK_EXPERIENCE_REORDERED',
                'entity_type' => ProfileExperience::class,
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
                updatedSections: ['experiences'],
                actor: $actor
            ));

            return $this->getWorkExperiences($targetUser, $actor);
        });
    }
}
