<?php

declare(strict_types=1);

namespace App\Policies\Parental;

use App\Models\Parental\ConsentRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class ConsentRequestPolicy
 */
class ConsentRequestPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ConsentRequest $request): bool
    {
        return $request->family->hasGuardianAccess($user->id);
    }

    public function respond(User $user, ConsentRequest $request): bool
    {
        return $request->family->hasGuardianAccess($user->id);
    }
}
