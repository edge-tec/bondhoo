<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionAdminAuthManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('ADMIN');
        $this->adminToken = $this->admin->createToken('admin-test')->plainTextToken;
    }

    public function test_admin_can_view_auth_dashboard_stats(): void
    {
        User::factory()->count(5)->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v2/admin/auth/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_users',
                    'verified_users',
                    'pending_verification',
                    'suspended_users',
                    'blocked_users',
                    'active_sessions',
                    'failed_logins_24h',
                    'locked_accounts',
                    'online_users',
                ],
            ]);
    }

    public function test_admin_can_manually_verify_user_email_and_mobile(): void
    {
        $target = User::factory()->create([
            'email_verified_at' => null,
            'phone_verified_at' => null,
            'status' => 'pending',
        ]);

        // Verify Email
        $resEmail = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/v2/admin/auth/users/{$target->id}/verify-email");
        $resEmail->assertStatus(200);
        $this->assertNotNull($target->fresh()->email_verified_at);

        // Verify Mobile
        $resMobile = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/v2/admin/auth/users/{$target->id}/verify-mobile");
        $resMobile->assertStatus(200);
        $this->assertNotNull($target->fresh()->phone_verified_at);
        $this->assertEquals('active', $target->fresh()->status);
    }

    public function test_admin_can_suspend_ban_and_unlock_user(): void
    {
        $target = User::factory()->create(['status' => 'active']);

        // Suspend
        $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/v2/admin/auth/users/{$target->id}/suspend")
            ->assertStatus(200);
        $this->assertEquals('suspended', $target->fresh()->status);

        // Ban
        $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/v2/admin/auth/users/{$target->id}/ban")
            ->assertStatus(200);
        $this->assertEquals('banned', $target->fresh()->status);

        // Lock & Unlock
        $target->lockAccount(15);
        $this->assertTrue($target->fresh()->isLocked());

        $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/v2/admin/auth/users/{$target->id}/unlock")
            ->assertStatus(200);
        $this->assertFalse($target->fresh()->isLocked());
    }

    public function test_admin_can_force_logout_user(): void
    {
        $target = User::factory()->create();
        $target->createToken('session1');
        $target->createToken('session2');

        $this->assertCount(2, $target->tokens);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/v2/admin/auth/users/{$target->id}/force-logout");

        $response->assertStatus(200);
        $this->assertCount(0, $target->fresh()->tokens);
    }

    public function test_admin_can_reset_user_password(): void
    {
        $target = User::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/v2/admin/auth/users/{$target->id}/reset-password", [
                'password' => 'NewAdminAssignedPass@123',
            ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('NewAdminAssignedPass@123', $target->fresh()->password));
    }

    public function test_admin_can_export_users_csv(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->get('/api/v2/admin/auth/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
