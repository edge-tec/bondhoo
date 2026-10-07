<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_has_full_permissions(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SUPER_ADMIN');

        $this->assertTrue($superAdmin->hasRole('SUPER_ADMIN'));
        $this->assertTrue($superAdmin->hasPermission('users.view'));
        $this->assertTrue($superAdmin->hasPermission('any.random.custom.permission'));
    }

    public function test_admin_has_assigned_permissions_only(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');

        $this->assertTrue($admin->hasRole('ADMIN'));
        $this->assertTrue($admin->hasPermission('users.view'));
        $this->assertTrue($admin->hasPermission('posts.moderate'));
        $this->assertFalse($admin->hasPermission('roles.manage'));
    }

    public function test_regular_user_cannot_access_user_management_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole('USER');
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_user_management_api(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_assign_and_remove_role_to_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');
        $token = $admin->createToken('test')->plainTextToken;

        $targetUser = User::factory()->create();

        // Assign MODERATOR role
        $assignResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/admin/users/{$targetUser->id}/roles", [
                'role' => 'MODERATOR',
            ]);

        $assignResponse->assertStatus(200);
        $this->assertTrue($targetUser->fresh()->hasRole('MODERATOR'));

        // Remove MODERATOR role
        $removeResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/admin/users/{$targetUser->id}/roles", [
                'role' => 'MODERATOR',
            ]);

        $removeResponse->assertStatus(200);
        $this->assertFalse($targetUser->fresh()->hasRole('MODERATOR'));
    }
}
