<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class RbacService
{
    /**
     * Assign a role to a user.
     */
    public function assignRole(User $user, string $roleName, ?User $actor = null): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user->assignRole($role);

        AuditLog::create([
            'user_id' => $actor?->id,
            'action' => 'rbac.role_assigned',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'new_values' => ['role' => $role->name],
        ]);
    }

    /**
     * Remove a role from a user.
     */
    public function removeRole(User $user, string $roleName, ?User $actor = null): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user->removeRole($role);

        AuditLog::create([
            'user_id' => $actor?->id,
            'action' => 'rbac.role_removed',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'old_values' => ['role' => $role->name],
        ]);
    }

    /**
     * Check if a user has a specific permission.
     */
    public function hasPermission(User $user, string $permission): bool
    {
        return $user->hasPermission($permission);
    }
}
