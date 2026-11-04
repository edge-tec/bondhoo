<?php

declare(strict_types=1);

namespace App\Services\Parental;

use App\Models\Parental\Family;
use App\Models\Parental\FamilyAuditLog;
use App\Models\Parental\FamilyMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class FamilyService
 *
 * Handles creation, member invitations, and isolation auditing for parental families.
 */
class FamilyService
{
    /**
     * Create a new parental family group with the owner registered as primary parent.
     */
    public function createFamily(User $owner, string $name, string $timezone = 'UTC'): Family
    {
        return DB::transaction(function () use ($owner, $name, $timezone): Family {
            $family = Family::create([
                'name' => $name,
                'owner_user_id' => $owner->id,
                'timezone' => $timezone,
                'status' => 'active',
            ]);

            // Register owner as initial primary parent member
            FamilyMember::create([
                'family_id' => $family->id,
                'user_id' => $owner->id,
                'name' => $owner->name,
                'role' => 'parent',
                'membership_status' => 'active',
                'joined_at' => now(),
            ]);

            $this->logAudit(
                family: $family,
                actorUserId: $owner->id,
                role: 'parent',
                action: 'family.created',
                resourceType: 'family',
                resourceId: (string) $family->id,
                metadata: ['name' => $name, 'timezone' => $timezone]
            );

            return $family;
        });
    }

    /**
     * Add a child profile or invite a secondary guardian to the family.
     */
    public function addMember(
        Family $family,
        string $name,
        string $role = 'child',
        ?User $user = null,
        ?int $actorUserId = null
    ): FamilyMember {
        return DB::transaction(function () use ($family, $name, $role, $user, $actorUserId): FamilyMember {
            $member = FamilyMember::create([
                'family_id' => $family->id,
                'user_id' => $user?->id,
                'name' => $name,
                'role' => $role,
                'membership_status' => 'active',
                'joined_at' => now(),
            ]);

            $this->logAudit(
                family: $family,
                actorUserId: $actorUserId ?? $family->owner_user_id,
                role: 'parent',
                action: 'member.added',
                resourceType: 'family_member',
                resourceId: (string) $member->id,
                metadata: ['role' => $role, 'name' => $name]
            );

            return $member;
        });
    }

    /**
     * Update family configuration details.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateFamily(Family $family, array $attributes, User $actor): Family
    {
        $family->update(array_intersect_key($attributes, array_flip(['name', 'timezone', 'status'])));

        $this->logAudit(
            family: $family,
            actorUserId: $actor->id,
            role: 'parent',
            action: 'family.updated',
            resourceType: 'family',
            resourceId: (string) $family->id,
            metadata: $attributes
        );

        return $family;
    }

    /**
     * Update child or guardian member attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateMember(FamilyMember $member, array $attributes, User $actor): FamilyMember
    {
        $member->update(array_intersect_key($attributes, array_flip(['name', 'role', 'membership_status'])));

        $this->logAudit(
            family: $member->family,
            actorUserId: $actor->id,
            role: 'parent',
            action: 'member.updated',
            resourceType: 'family_member',
            resourceId: (string) $member->id,
            metadata: $attributes
        );

        return $member;
    }

    /**
     * Soft-remove or revoke a member's family access.
     */
    public function removeMember(FamilyMember $member, User $actor): void
    {
        $family = $member->family;

        $member->update([
            'membership_status' => 'left',
        ]);

        $this->logAudit(
            family: $family,
            actorUserId: $actor->id,
            role: 'parent',
            action: 'member.removed',
            resourceType: 'family_member',
            resourceId: (string) $member->id,
            metadata: ['role' => $member->role, 'name' => $member->name]
        );
    }

    /**
     * Retrieve the audit and activity log history for a family.
     *
     * @return Collection<int, FamilyAuditLog>
     */
    public function getActivityHistory(Family $family, int $limit = 50)
    {
        return FamilyAuditLog::where('family_id', $family->id)
            ->with('actor')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Append an immutable record to the family audit trail.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function logAudit(
        Family $family,
        ?int $actorUserId,
        string $role,
        string $action,
        string $resourceType,
        ?string $resourceId = null,
        array $metadata = [],
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): FamilyAuditLog {
        return FamilyAuditLog::create([
            'family_id' => $family->id,
            'actor_user_id' => $actorUserId,
            'actor_role' => $role,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'metadata' => $metadata,
            'ip_address' => $ipAddress ?? request()?->ip(),
            'user_agent' => $userAgent ?? request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
