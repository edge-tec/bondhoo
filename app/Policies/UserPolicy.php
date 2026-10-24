<?php

namespace App\Policies;

use App\Models\User;
use App\Services\ProfilePrivacyService;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models (user directory).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view') || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the specific user model.
     */
    public function view(User $user, User $model): bool
    {
        // 1. User can always view self
        if ($user->id === $model->id) {
            return true;
        }

        // 2. Admins and moderators can always view users
        if ($user->hasPermission('users.view') || $user->isAdmin()) {
            return true;
        }

        // 3. Regular users cannot view banned users or users with an active mutual block
        if ($model->status === 'banned') {
            return false;
        }

        return ! app(ProfilePrivacyService::class)->isBlocked($user->id, $model->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('users.create') || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // 1. User can update their own account
        if ($user->id === $model->id) {
            return true;
        }

        // 2. Admins with users.edit permission
        return $user->hasPermission('users.edit') || $user->hasRole('SUPER_ADMIN');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Prevent deleting self via administrative delete
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermission('users.delete') || $user->hasRole('SUPER_ADMIN');
    }

    /**
     * Determine whether the user can suspend the model.
     */
    public function suspend(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermission('users.suspend') || $user->isAdmin();
    }

    /**
     * Determine whether the user can assign roles to the model.
     */
    public function assignRole(User $user, User $model): bool
    {
        return $user->hasPermission('users.edit') || $user->hasPermission('roles.manage') || $user->hasRole('SUPER_ADMIN');
    }
}
