<?php

namespace App\Services\Admin;

use App\Models\AdminElevation;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRestriction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EnterpriseAccessControlService
{
    /**
     * Standard Enterprise Permissions Catalog
     */
    public const PERMISSIONS = [
        // Users Management
        'users.view' => 'ব্যবহারকারী তালিকা দর্শন',
        'users.manage' => 'ব্যবহারকারী পরিবর্তন ও স্ট্যাটাস আপডেট',
        'users.delete' => 'ব্যবহারকারী স্থায়ী অপসারণ',
        'users.restrict' => 'ব্যবহারকারী অপব্যবহার সীমাবদ্ধকরণ প্রয়োগ',

        // Content & Moderation
        'content.view' => 'কন্টেন্ট ও পোস্টসমূহ পর্যালোচনা',
        'content.moderate' => 'মডারেশন অ্যাকশন সম্পাদন',
        'content.delete' => 'কন্টেন্ট স্থায়ীভাবে অপসারণ',

        // Security & SOC
        'security.view' => 'সিকিউরিটি অপারেশন সেন্টার পরিদর্শন',
        'security.manage' => 'সিকিউরিটি ঘটনা নিষ্পত্তি ও সমাধান',
        'security.soc' => 'সক্রিয় সেশন বাতিল ও আক্রমণ প্রতিরোধ',

        // Analytics
        'analytics.view' => 'এক্সিকিউটিভ ইন্টেলিজেন্স ও চার্ট দর্শন',
        'analytics.export' => 'এনালিটিক্স ডাটা সিএসভি এক্সপোর্ট',

        // Settings & Feature Flags
        'settings.view' => 'প্ল্যাটফর্ম কনফিগারেশন দর্শন',
        'settings.manage' => 'প্ল্যাটফর্ম কনফিগারেশন হালনাগাদ',
        'settings.rollback' => 'প্ল্যাটফর্ম সেটিং রোলব্যাক',
        'flags.manage' => 'ফিচার ফ্ল্যাগ নিয়ন্ত্রণ',

        // Audit
        'audit.view' => 'অডিট ট্রেইল লগ দর্শন',
        'audit.verify' => 'ক্রিপ্টোগ্রাফিক চেইন ইন্টিগ্রিটি যাচাই',
        'audit.export' => 'অডিট লগ এক্সপোর্ট',

        // Operations & Infrastructure
        'alerts.manage' => 'অ্যালার্ট ব্যবস্থাপনা ও অ্যাকনলেজ',
        'jobs.view' => 'ব্যাকগ্রাউন্ড কিউ ও শিডিউলার নিরীক্ষণ',
        'jobs.manage' => 'ব্যর্থ জবস পুনরায় চেষ্টা ও বাতিল',
        'health.view' => 'সিস্টেম ও অবকাঠামো স্বাস্থ্য দর্শন',

        // Governance
        'governance.view' => 'ডাটা গভর্ন্যান্স ও প্রাইভেসি দর্শন',
        'governance.manage' => 'ডাটা রিটেনশন নীতি প্রয়োগ ও GDPR ব্যবস্থাপনা',

        // Roles & Permissions
        'roles.manage' => 'রোল ও পারমিশন ম্যাট্রিক্স নিয়ন্ত্রণ',
    ];

    /**
     * Check if a user possesses a specific permission (via roles, direct, or temporary elevation).
     */
    public function hasPermission(User $user, string $permission): bool
    {
        // SUPER_ADMIN role bypasses individual checks
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check active temporary elevations
        $elevations = $user->adminElevations()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->get();

        foreach ($elevations as $elevation) {
            if ($elevation->elevated_role === 'SUPER_ADMIN') {
                return true;
            }
            if (is_array($elevation->permissions) && in_array($permission, $elevation->permissions, true)) {
                return true;
            }
        }

        // Check roles associated permissions
        return $user->roles()
            ->whereHas('permissions', function ($q) use ($permission) {
                $q->where('name', $permission);
            })
            ->exists();
    }

    /**
     * Check if a user is a SUPER_ADMIN (live DB query, including active JIT elevation).
     */
    public function isSuperAdmin(User $user): bool
    {
        if ($user->status === 'super_admin') {
            return true;
        }

        return $user->hasRole('SUPER_ADMIN');
    }

    /**
     * Guard: Prevent demoting, suspending, banning or full-banning the last active SUPER_ADMIN.
     * Acquires row-level locks (`lockForUpdate()`) on active SUPER_ADMIN accounts when inside a transaction.
     */
    public function isLastSuperAdmin(User $targetUser, bool $lockForUpdate = true): bool
    {
        if (! $this->isSuperAdmin($targetUser)) {
            return false;
        }

        $superAdminRole = Role::where('name', 'SUPER_ADMIN')->first();
        if (! $superAdminRole) {
            return true;
        }

        $query = User::where('status', 'active')
            ->where(function ($q) use ($superAdminRole) {
                $q->where('status', 'super_admin')
                    ->orWhereHas('roles', fn ($r) => $r->where('roles.id', $superAdminRole->id));
            })
            ->whereDoesntHave('restrictions', function ($q) {
                $q->where('is_active', true)
                    ->where('restriction_type', UserRestriction::TYPE_FULL_BAN)
                    ->where(function ($sub) {
                        $sub->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            });

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $activeSuperAdminIds = $query->pluck('id')->all();

        $otherActiveSuperAdmins = array_filter(
            $activeSuperAdminIds,
            fn (int $id) => $id !== (int) $targetUser->id
        );

        return count($otherActiveSuperAdmins) === 0;
    }

    /**
     * Grant a time-limited privileged access elevation to an admin.
     */
    public function grantElevation(User $targetUser, User $granterAdmin, string $elevatedRole, array $permissions, int $durationMinutes, string $reason): AdminElevation
    {
        return DB::transaction(function () use ($targetUser, $granterAdmin, $elevatedRole, $permissions, $durationMinutes, $reason) {
            $lockedUser = User::where('id', $targetUser->id)->lockForUpdate()->firstOrFail();

            $elevation = AdminElevation::create([
                'user_id' => $lockedUser->id,
                'elevated_role' => $elevatedRole,
                'permissions' => $permissions,
                'reason' => $reason,
                'granted_by' => $granterAdmin->id,
                'expires_at' => Carbon::now()->addMinutes($durationMinutes),
            ]);

            $targetUser->unsetRelation('roles');
            $targetUser->unsetRelation('adminElevations');

            AuditLog::create([
                'user_id' => $granterAdmin->id,
                'action' => 'admin_privilege_elevation_granted',
                'entity_type' => AdminElevation::class,
                'entity_id' => $elevation->id,
                'new_values' => [
                    'target_user_id' => $lockedUser->id,
                    'target_user_email' => $lockedUser->email,
                    'elevated_role' => $elevatedRole,
                    'duration_minutes' => $durationMinutes,
                    'expires_at' => $elevation->expires_at->toIso8601String(),
                    'reason' => $reason,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $elevation;
        });
    }

    /**
     * Revoke an active privileged elevation early.
     */
    public function revokeElevation(AdminElevation $elevation, User $revokingAdmin, string $reason): void
    {
        DB::transaction(function () use ($elevation, $revokingAdmin, $reason) {
            $lockedElevation = AdminElevation::where('id', $elevation->id)->lockForUpdate()->firstOrFail();
            $lockedElevation->update([
                'revoked_at' => now(),
                'revoked_by' => $revokingAdmin->id,
            ]);

            if ($lockedElevation->relationLoaded('user') && $lockedElevation->user) {
                $lockedElevation->user->unsetRelation('roles');
                $lockedElevation->user->unsetRelation('adminElevations');
            }

            AuditLog::create([
                'user_id' => $revokingAdmin->id,
                'action' => 'admin_privilege_elevation_revoked',
                'entity_type' => AdminElevation::class,
                'entity_id' => $lockedElevation->id,
                'new_values' => [
                    'elevation_id' => $lockedElevation->id,
                    'revoked_at' => now()->toIso8601String(),
                    'reason' => $reason,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    /**
     * Ensure all standard permissions exist in the database.
     */
    public function syncStandardPermissions(): void
    {
        foreach (self::PERMISSIONS as $permName => $permLabel) {
            $parts = explode('.', $permName);
            $group = $parts[0] ?? 'general';

            Permission::firstOrCreate(
                ['name' => $permName],
                [
                    'label' => $permLabel,
                    'group' => $group,
                    'description' => "Enterprise permission for {$permName}",
                ]
            );
        }

        // Attach permissions to ADMIN and SUPER_ADMIN roles
        $superAdminRole = Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['label' => 'Super Administrator']);
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Administrator']);

        $allPermIds = Permission::whereIn('name', array_keys(self::PERMISSIONS))->pluck('id')->toArray();
        $superAdminRole->permissions()->syncWithoutDetaching($allPermIds);

        // General admin gets all except destructive role & setting rollback
        $adminPermIds = Permission::whereIn('name', array_keys(self::PERMISSIONS))
            ->whereNotIn('name', ['roles.manage', 'users.delete'])
            ->pluck('id')
            ->toArray();
        $adminRole->permissions()->syncWithoutDetaching($adminPermIds);
    }

    /**
     * Get Complete Roles & Permissions Matrix.
     */
    public function getMatrix(): array
    {
        $this->syncStandardPermissions();

        $roles = Role::with('permissions:id,name,label,group')->get();
        $permissions = Permission::all()->groupBy('group');

        return [
            'roles' => $roles,
            'permission_groups' => $permissions,
            'catalog' => self::PERMISSIONS,
        ];
    }
}
