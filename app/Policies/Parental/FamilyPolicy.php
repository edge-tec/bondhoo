<?php

declare(strict_types=1);

namespace App\Policies\Parental;

use App\Models\Parental\Family;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class FamilyPolicy
 *
 * Ensures strictly isolated cross-family access control.
 */
class FamilyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the family.
     */
    public function view(User $user, Family $family): bool
    {
        return $family->hasGuardianAccess($user->id);
    }

    /**
     * Determine whether the user can update the family.
     */
    public function update(User $user, Family $family): bool
    {
        return $family->owner_user_id === $user->id;
    }

    /**
     * Determine whether the user can manage members.
     */
    public function manageMembers(User $user, Family $family): bool
    {
        return $family->hasGuardianAccess($user->id);
    }

    /**
     * Determine whether the user can issue pairing codes.
     */
    public function issuePairingCode(User $user, Family $family): bool
    {
        return $family->hasGuardianAccess($user->id);
    }
}
