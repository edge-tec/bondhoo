<?php

declare(strict_types=1);

namespace App\Policies\Parental;

use App\Models\Parental\ChildDevice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class ChildDevicePolicy
 *
 * Prevents unauthorized or cross-family access to child devices.
 */
class ChildDevicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the child device.
     */
    public function view(User $user, ChildDevice $device): bool
    {
        return $device->family->hasGuardianAccess($user->id);
    }

    /**
     * Determine whether the user can manage or revoke the child device.
     */
    public function manage(User $user, ChildDevice $device): bool
    {
        return $device->family->hasGuardianAccess($user->id);
    }

    /**
     * Determine whether the user can dispatch commands to the device.
     */
    public function dispatchCommand(User $user, ChildDevice $device): bool
    {
        return $device->family->hasGuardianAccess($user->id);
    }
}
