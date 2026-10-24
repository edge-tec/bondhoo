<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);

        return $user;
    }

    public function test_user_policy_allows_self_update_and_admin_update(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        $admin = $this->createUser();
        $admin->assignRole('ADMIN');

        // User can update self
        $this->assertTrue(Gate::forUser($user)->allows('update', $user));

        // User cannot update another user
        $this->assertFalse(Gate::forUser($user)->allows('update', $otherUser));

        // Admin can update another user
        $this->assertTrue(Gate::forUser($admin)->allows('update', $otherUser));
    }

    public function test_user_policy_allows_admin_to_manage_and_suspend_users(): void
    {
        $superAdmin = $this->createUser();
        $superAdmin->assignRole('SUPER_ADMIN');

        $regularUser = $this->createUser();
        $targetUser = $this->createUser();

        // Regular user cannot suspend or delete others
        $this->assertFalse(Gate::forUser($regularUser)->allows('suspend', $targetUser));
        $this->assertFalse(Gate::forUser($regularUser)->allows('delete', $targetUser));

        // SuperAdmin can suspend and delete target user
        $this->assertTrue(Gate::forUser($superAdmin)->allows('suspend', $targetUser));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('delete', $targetUser));

        // SuperAdmin cannot delete self
        $this->assertFalse(Gate::forUser($superAdmin)->allows('delete', $superAdmin));
    }
}
