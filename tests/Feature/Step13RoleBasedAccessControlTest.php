<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Step13RoleBasedAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Test 1: Verify all 6 required roles exist in the system.
     */
    public function test_all_six_required_roles_exist(): void
    {
        $requiredRoles = [
            'USER',
            'VERIFIED_USER',
            'CREATOR',
            'MODERATOR',
            'ADMIN',
            'SUPER_ADMIN',
        ];

        foreach ($requiredRoles as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
        }
    }

    /**
     * Test 2: Verify all 8 management permissions exist in the system.
     */
    public function test_all_eight_management_permissions_exist(): void
    {
        $requiredPermissions = [
            'manage.users',
            'manage.posts',
            'manage.comments',
            'manage.reports',
            'manage.ads',
            'manage.wallet',
            'manage.verification',
            'manage.settings',
        ];

        foreach ($requiredPermissions as $permName) {
            $this->assertDatabaseHas('permissions', ['name' => $permName]);
        }
    }

    /**
     * Test 3: Permission denied (403) when user lacks required permission.
     */
    public function test_permission_denied_returns_403_for_unauthorized_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('USER');
        $token = $user->createToken('test-token')->plainTextToken;

        // Regular USER lacks 'manage.users' permission
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v2/rbac/manage-users');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'এই অ্যাকশনটি সম্পাদন করার পর্যাপ্ত পারমিশন আপনার নেই।',
            ]);
    }

    /**
     * Test 4: Permission granted (200) when user has role with required permission.
     */
    public function test_permission_granted_allows_access(): void
    {
        // 1. Moderator has 'manage.posts'
        $mod = User::factory()->create();
        $mod->assignRole('MODERATOR');
        $modToken = $mod->createToken('mod-token')->plainTextToken;

        $modRes = $this->withHeader('Authorization', "Bearer {$modToken}")
            ->getJson('/api/v2/rbac/manage-posts');

        $modRes->assertStatus(200)
            ->assertJsonPath('success', true);

        auth()->forgetGuards();

        // 2. Admin has 'manage.users' and 'manage.settings'
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $adminToken = $admin->createToken('admin-token')->plainTextToken;

        $adminRes = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->getJson('/api/v2/rbac/manage-users');
        $adminRes->assertStatus(200);

        auth()->forgetGuards();

        // 3. Super Admin bypasses all individual permission checks
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SUPER_ADMIN');
        $superToken = $superAdmin->createToken('super-token')->plainTextToken;

        $superRes = $this->withHeader('Authorization', "Bearer {$superToken}")
            ->getJson('/api/v2/rbac/manage-settings');
        $superRes->assertStatus(200);
    }

    /**
     * Test 5: Role middleware denies unauthorized roles and grants authorized roles.
     */
    public function test_role_middleware_properly_enforces_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('USER');
        $userToken = $user->createToken('user-token')->plainTextToken;

        // Regular user denied Creator Studio
        $deniedRes = $this->withHeader('Authorization', "Bearer {$userToken}")
            ->getJson('/api/v2/rbac/creator-studio');
        $deniedRes->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'এই অ্যাকশনটিতে প্রবেশ করার অনুমতি আপনার নেই।',
            ]);

        auth()->forgetGuards();

        // Creator granted Creator Studio
        $creator = User::factory()->create();
        $creator->assignRole('CREATOR');
        $creatorToken = $creator->createToken('creator-token')->plainTextToken;

        $grantedRes = $this->withHeader('Authorization', "Bearer {$creatorToken}")
            ->getJson('/api/v2/rbac/creator-studio');
        $grantedRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test 6: Dynamic role assignment and removal updates access immediately.
     */
    public function test_dynamic_role_assignment_and_removal(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Initially no role -> denied moderator panel
        $res1 = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v2/rbac/moderator-panel');
        $res1->assertStatus(403);

        // Assign MODERATOR role dynamically (case-insensitive)
        $user->assignRole('moderator');
        $this->assertTrue($user->fresh()->hasRole('MODERATOR'));
        $this->assertTrue($user->fresh()->hasRole('moderator'));

        // Now granted
        $res2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v2/rbac/moderator-panel');
        $res2->assertStatus(200);

        // Remove role
        $user->removeRole('MODERATOR');
        $this->assertFalse($user->fresh()->hasRole('MODERATOR'));

        // Denied again
        $res3 = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v2/rbac/moderator-panel');
        $res3->assertStatus(403);
    }

    /**
     * Test 7: Verified User and Creator roles assignment and verification.
     */
    public function test_verified_user_and_creator_roles(): void
    {
        $user = User::factory()->create();

        $user->assignRole('Verified User');
        $this->assertTrue($user->fresh()->hasRole('VERIFIED_USER'));

        $user->assignRole('CREATOR');
        $this->assertTrue($user->fresh()->hasRole('CREATOR'));
        $this->assertTrue($user->fresh()->hasPermission('manage.ads'));
        $this->assertTrue($user->fresh()->hasPermission('manage.posts'));
    }
}
