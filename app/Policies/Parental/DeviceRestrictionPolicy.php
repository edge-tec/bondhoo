<?php

declare(strict_types=1);

namespace App\Policies\Parental;

use App\Models\Parental\DeviceRestriction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class DeviceRestrictionPolicy
 */
class DeviceRestrictionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, DeviceRestriction $restriction): bool
    {
        return $restriction->family->hasGuardianAccess($user->id);
    }

    public function update(User $user, DeviceRestriction $restriction): bool
    {
        return $restriction->family->hasGuardianAccess($user->id);
    }

    public function delete(User $user, DeviceRestriction $restriction): bool
    {
        return $restriction->family->hasGuardianAccess($user->id);
    }
}
