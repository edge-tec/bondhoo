<?php

namespace App\Services;

use App\Events\ProfileUpdatedEvent;
use App\Models\AuditLog;
use App\Models\ProfileEducation;
use App\Models\User;
use App\Services\Security\ProfileTextSanitizer;
use Illuminate\Support\Facades\DB;

class EducationService
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Get education records for target user, filtered by viewer privacy.
     */
    public function getEducations(User $targetUser, ?User $viewer = null): array
    {
        $privacyService = app(ProfilePrivacyService::class);
        if (! $privacyService->canViewProfile($targetUser, $viewer)) {
            abort(403, 'এই প্রোফাইলটি ব্যক্তিগত অথবা দেখার অনুমতি আপনার নেই।');
        }

        $isAdmin = $privacyService->isAdmin($viewer);
        $isOwner = $viewer && $viewer->id === $targetUser->id;

        if (! $isAdmin && ! $isOwner && ! $privacyService->canViewEducation($targetUser, $viewer)) {
            return [];
        }

        $isFriend = $viewer && in_array($viewer->id, $targetUser->getFriendIds(), true);
        $isLocked = (bool) ($targetUser->settings?->is_profile_locked ?? false);
        $isLockedView = $isLocked && ! $isFriend && ! $isOwner && ! $isAdmin;

        if ($isLockedView) {
            return [];
        }

        $records = $targetUser->educations()
            ->visibleFor($viewer)
            ->orderBy('display_order', 'asc')
            ->orderBy('start_date', 'desc')
            ->get();

        return $records->map(fn (ProfileEducation $item) => $this->formatEducationItem($item))->toArray();
    }

    /**
     * Format an education record array.
     */
    public function formatEducationItem(ProfileEducation $item): array
    {
        return [
            'id' => $item->id,
            'user_id' => $item->user_id,
            'institution' => $item->institution_name,
            'institution_name' => $item->institution_name,
            'degree' => $item->degree,
            'field_of_study' => $item->field_of_study,
            'start_date' => $item->start_date?->format('Y-m-d'),
            'end_date' => $item->end_date?->format('Y-m-d'),
            'is_current' => (bool) $item->is_current,
            'currently_studying' => (bool) $item->is_current,
            'grade' => $item->grade,
            'description' => $item->description,
            'location' => $item->location,
            'privacy' => $item->privacy ?? 'public',
            'display_order' => (int) $item->display_order,
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Create a new education record.
     */
    public function createEducation(
        User $targetUser,
        array $data,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileEducation {
        return DB::transaction(function () use ($targetUser, $data, $actor, $ip, $userAgent) {
            $isCurrent = (bool) ($data['is_current'] ?? $data['currently_studying'] ?? false);

            $maxOrder = (int) $targetUser->educations()->max('display_order');
            $displayOrder = isset($data['display_order']) ? (int) $data['display_order'] : $maxOrder + 1;

            $education = $targetUser->educations()->create([
                'institution_name' => ProfileTextSanitizer::sanitizePlainText($data['institution_name'] ?? $data['institution'] ?? null, false),
                'degree' => ProfileTextSanitizer::sanitizePlainText($data['degree'] ?? null, false),
                'field_of_study' => ProfileTextSanitizer::sanitizePlainText($data['field_of_study'] ?? null, false),
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $isCurrent ? null : ($data['end_date'] ?? null),
                'is_current' => $isCurrent,
                'grade' => ProfileTextSanitizer::sanitizePlainText($data['grade'] ?? null, false),
                'description' => ProfileTextSanitizer::sanitizePlainText($data['description'] ?? null, true),
                'location' => ProfileTextSanitizer::sanitizePlainText($data['location'] ?? null, false),
                'privacy' => $data['privacy'] ?? 'public',
                'display_order' => $displayOrder,
            ]);

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'EDUCATION_CREATED',
                'entity_type' => ProfileEducation::class,
                'entity_id' => $education->id,
                'old_values' => null,
                'new_values' => $education->toArray(),
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // Dispatch Event
            event(new ProfileUpdatedEvent(
                user: $targetUser,
                updatedSections: ['educations'],
                actor: $actor
            ));

            return $education;
        });
    }

    /**
     * Update an existing education record.
     */
    public function updateEducation(
        ProfileEducation $education,
        array $data,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): ProfileEducation {
        return DB::transaction(function () use ($education, $data, $actor, $ip, $userAgent) {
            $oldValues = $education->toArray();
            $targetUser = $education->user;

            $updates = [];

            if (array_key_exists('institution_name', $data) || array_key_exists('institution', $data)) {
                $updates['institution_name'] = ProfileTextSanitizer::sanitizePlainText($data['institution_name'] ?? $data['institution'], false);
            }
            if (array_key_exists('degree', $data)) {
                $updates['degree'] = ProfileTextSanitizer::sanitizePlainText($data['degree'], false);
            }
            if (array_key_exists('field_of_study', $data)) {
                $updates['field_of_study'] = ProfileTextSanitizer::sanitizePlainText($data['field_of_study'], false);
            }
            if (array_key_exists('start_date', $data)) {
                $updates['start_date'] = $data['start_date'];
            }
            if (array_key_exists('is_current', $data) || array_key_exists('currently_studying', $data)) {
                $isCurrent = (bool) ($data['is_current'] ?? $data['currently_studying']);
                $updates['is_current'] = $isCurrent;
                if ($isCurrent) {
                    $updates['end_date'] = null;
                }
            }
            if (array_key_exists('end_date', $data)) {
                if (! ($updates['is_current'] ?? $education->is_current)) {
                    $updates['end_date'] = $data['end_date'];
                }
            }
            if (array_key_exists('grade', $data)) {
                $updates['grade'] = ProfileTextSanitizer::sanitizePlainText($data['grade'], false);
            }
            if (array_key_exists('description', $data)) {
                $updates['description'] = ProfileTextSanitizer::sanitizePlainText($data['description'], true);
            }
            if (array_key_exists('location', $data)) {
                $updates['location'] = ProfileTextSanitizer::sanitizePlainText($data['location'], false);
            }
            if (array_key_exists('privacy', $data)) {
                $updates['privacy'] = $data['privacy'];
            }
            if (array_key_exists('display_order', $data)) {
                $updates['display_order'] = (int) $data['display_order'];
            }

            $education->update($updates);
            $fresh = $education->fresh();

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'EDUCATION_UPDATED',
                'entity_type' => ProfileEducation::class,
                'entity_id' => $education->id,
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
                updatedSections: ['educations'],
                actor: $actor
            ));

            return $fresh;
        });
    }

    /**
     * Delete an education record.
     */
    public function deleteEducation(
        ProfileEducation $education,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        DB::transaction(function () use ($education, $actor, $ip, $userAgent) {
            $oldValues = $education->toArray();
            $targetUser = $education->user;

            $education->delete();

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'EDUCATION_DELETED',
                'entity_type' => ProfileEducation::class,
                'entity_id' => $education->id,
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
                updatedSections: ['educations'],
                actor: $actor
            ));
        });
    }

    /**
     * Reorder user's education records.
     */
    public function reorderEducations(
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
                ProfileEducation::where('id', $id)
                    ->where('user_id', $targetUser->id)
                    ->update(['display_order' => $order]);
            }

            // Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'EDUCATION_REORDERED',
                'entity_type' => ProfileEducation::class,
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
                updatedSections: ['educations'],
                actor: $actor
            ));

            return $this->getEducations($targetUser, $actor);
        });
    }
}
