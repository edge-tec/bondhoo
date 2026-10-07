<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $roles = [
            'SUPER_ADMIN' => ['label' => 'Super Administrator', 'description' => 'Unrestricted system access'],
            'ADMIN' => ['label' => 'Administrator', 'description' => 'System administration and user moderation'],
            'MODERATOR' => ['label' => 'Content Moderator', 'description' => 'Moderates posts, comments, and reports'],
            'CREATOR' => ['label' => 'Content Creator', 'description' => 'Verified content producer and publisher'],
            'SUPPORT' => ['label' => 'Customer Support', 'description' => 'Assists users and reviews reports'],
            'SUPPORT_MANAGER' => ['label' => 'Support Manager', 'description' => 'Manages support escalations and user verifications'],
            'ADVERTISER' => ['label' => 'Advertiser', 'description' => 'Manages ad campaigns and sponsored content'],
            'AFFILIATE' => ['label' => 'Affiliate Partner', 'description' => 'Platform affiliate and referral partner'],
            'VERIFIED_USER' => ['label' => 'Verified User', 'description' => 'Identity verified citizen user with blue badge'],
            'ANALYST' => ['label' => 'Data Analyst', 'description' => 'Access to analytics and audit metrics'],
            'USER' => ['label' => 'Standard User', 'description' => 'Regular platform participant'],
        ];

        $createdRoles = [];
        foreach ($roles as $name => $meta) {
            $createdRoles[$name] = Role::firstOrCreate(['name' => $name], $meta);
        }

        // 2. Permissions
        $permissions = [
            // Core Step 13 Management Permissions
            ['name' => 'manage.users', 'group' => 'management', 'label' => 'Manage Users'],
            ['name' => 'manage.posts', 'group' => 'management', 'label' => 'Manage Posts'],
            ['name' => 'manage.comments', 'group' => 'management', 'label' => 'Manage Comments'],
            ['name' => 'manage.reports', 'group' => 'management', 'label' => 'Manage Reports'],
            ['name' => 'manage.ads', 'group' => 'management', 'label' => 'Manage Ads'],
            ['name' => 'manage.wallet', 'group' => 'management', 'label' => 'Manage Wallet'],
            ['name' => 'manage.verification', 'group' => 'management', 'label' => 'Manage Verification'],
            ['name' => 'manage.settings', 'group' => 'management', 'label' => 'Manage Settings'],

            // User management
            ['name' => 'users.view', 'group' => 'users', 'label' => 'View Users'],
            ['name' => 'users.create', 'group' => 'users', 'label' => 'Create Users'],
            ['name' => 'users.edit', 'group' => 'users', 'label' => 'Edit Users'],
            ['name' => 'users.suspend', 'group' => 'users', 'label' => 'Suspend Users'],
            ['name' => 'users.delete', 'group' => 'users', 'label' => 'Delete Users'],
            ['name' => 'users.verify', 'group' => 'users', 'label' => 'Verify User Email/Mobile'],
            ['name' => 'users.unlock', 'group' => 'users', 'label' => 'Unlock Locked Accounts'],
            ['name' => 'users.sessions.manage', 'group' => 'users', 'label' => 'Force Logout & Manage Sessions'],
            ['name' => 'auth.admin', 'group' => 'users', 'label' => 'Authentication Management Console'],

            // Roles & Permissions
            ['name' => 'roles.manage', 'group' => 'rbac', 'label' => 'Manage Roles'],
            ['name' => 'permissions.manage', 'group' => 'rbac', 'label' => 'Manage Permissions'],

            // Content moderation
            ['name' => 'posts.view', 'group' => 'content', 'label' => 'View All Posts'],
            ['name' => 'posts.moderate', 'group' => 'content', 'label' => 'Moderate Posts'],
            ['name' => 'posts.delete', 'group' => 'content', 'label' => 'Delete Posts'],
            ['name' => 'comments.moderate', 'group' => 'content', 'label' => 'Moderate Comments'],

            // Reports
            ['name' => 'reports.manage', 'group' => 'reports', 'label' => 'Manage Reports'],
            ['name' => 'reports.resolve', 'group' => 'reports', 'label' => 'Resolve Reports'],

            // Communities
            ['name' => 'groups.manage', 'group' => 'groups', 'label' => 'Manage Groups'],
            ['name' => 'pages.manage', 'group' => 'pages', 'label' => 'Manage Pages'],

            // System
            ['name' => 'system.settings', 'group' => 'system', 'label' => 'System Settings'],
            ['name' => 'audit.view', 'group' => 'system', 'label' => 'View Audit Logs'],
        ];

        $createdPermissions = [];
        foreach ($permissions as $perm) {
            $createdPermissions[$perm['name']] = Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['label' => $perm['label'], 'group' => $perm['group']]
            );
        }

        // 3. Assign permissions to roles
        // ADMIN permissions (Includes all 8 manage permissions)
        $adminPerms = [
            'manage.users', 'manage.posts', 'manage.comments', 'manage.reports',
            'manage.ads', 'manage.wallet', 'manage.verification', 'manage.settings',
            'users.view', 'users.edit', 'users.suspend', 'users.verify', 'users.unlock',
            'users.sessions.manage', 'auth.admin',
            'posts.view', 'posts.moderate', 'posts.delete', 'comments.moderate',
            'reports.manage', 'reports.resolve',
            'groups.manage', 'pages.manage',
            'audit.view',
        ];
        foreach ($adminPerms as $permName) {
            if (isset($createdPermissions[$permName])) {
                $createdRoles['ADMIN']->givePermissionTo($createdPermissions[$permName]);
            }
        }

        // MODERATOR permissions
        $modPerms = [
            'manage.posts', 'manage.comments', 'manage.reports',
            'users.view', 'posts.view', 'posts.moderate',
            'comments.moderate', 'reports.manage', 'reports.resolve',
        ];
        foreach ($modPerms as $permName) {
            if (isset($createdPermissions[$permName])) {
                $createdRoles['MODERATOR']->givePermissionTo($createdPermissions[$permName]);
            }
        }

        // CREATOR permissions
        $creatorPerms = [
            'manage.posts', 'manage.comments', 'manage.ads',
            'posts.view',
        ];
        foreach ($creatorPerms as $permName) {
            if (isset($createdPermissions[$permName])) {
                $createdRoles['CREATOR']->givePermissionTo($createdPermissions[$permName]);
            }
        }

        // SUPPORT permissions
        $supportPerms = ['users.view', 'reports.manage'];
        foreach ($supportPerms as $permName) {
            if (isset($createdPermissions[$permName])) {
                $createdRoles['SUPPORT']->givePermissionTo($createdPermissions[$permName]);
            }
        }

        // ANALYST permissions
        $analystPerms = ['users.view', 'audit.view'];
        foreach ($analystPerms as $permName) {
            if (isset($createdPermissions[$permName])) {
                $createdRoles['ANALYST']->givePermissionTo($createdPermissions[$permName]);
            }
        }
    }
}
