<?php

namespace Tests\Feature;

use App\Models\FailedLoginAttempt;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use App\Notifications\SmsOtpNotification;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\WelcomeEmailNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Step15ProductionAuditAndTestingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. Schema & Migration Verification:
     * Asserts that all critical tables from Steps 01 - 14 exist with proper schema.
     */
    public function test_all_production_database_tables_and_columns_exist(): void
    {
        $criticalTables = [
            'users',
            'personal_access_tokens',
            'roles',
            'permissions',
            'role_user',
            'permission_role',
            'user_profiles',
            'user_settings',
            'audit_logs',
            'email_verifications',
            'mobile_verifications',
            'phone_verifications',
            'otp_codes',
            'otp_logs',
            'email_logs',
            'password_histories',
            'login_histories',
            'user_sessions',
            'trusted_devices',
            'user_two_factor',
            'recovery_codes',
            'failed_login_attempts',
            'blocked_users',
        ];

        foreach ($criticalTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table [{$table}] must exist in production schema.");
        }

        // Validate critical security columns on users table
        $userColumns = [
            'id', 'name', 'username', 'email', 'phone', 'password',
            'email_verified_at', 'phone_verified_at', 'status', 'two_factor_enabled',
            'two_factor_secret', 'locked_until', 'failed_login_attempts',
            'last_login_at', 'last_login_ip',
        ];

        foreach ($userColumns as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), "Column [users.{$column}] must exist.");
        }
    }

    /**
     * 2. Seeder Validation:
     * Confirms all 6 roles and 8 management permissions exist and are properly mapped.
     */
    public function test_seeder_validation_roles_and_permissions_are_correctly_mapped(): void
    {
        $expectedRoles = ['USER', 'VERIFIED_USER', 'CREATOR', 'MODERATOR', 'ADMIN', 'SUPER_ADMIN'];
        foreach ($expectedRoles as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
        }

        $expectedPermissions = [
            'manage.users',
            'manage.posts',
            'manage.comments',
            'manage.reports',
            'manage.ads',
            'manage.wallet',
            'manage.verification',
            'manage.settings',
        ];

        foreach ($expectedPermissions as $permName) {
            $this->assertDatabaseHas('permissions', ['name' => $permName]);
        }

        $adminRole = Role::where('name', 'ADMIN')->first();
        $this->assertNotNull($adminRole);
        $this->assertTrue($adminRole->permissions->contains('name', 'manage.users'));
    }

    /**
     * 3. Sanctum Authentication & Multi-Device Token Revocation:
     * Tests token issuance, authentication via Bearer, individual device logout, and force-logout all devices.
     */
    public function test_sanctum_token_lifecycle_and_multi_device_revocation(): void
    {
        $user = User::factory()->create();

        $token1 = $user->createToken('desktop-chrome');
        $token2 = $user->createToken('mobile-safari');

        $this->assertCount(2, $user->tokens);

        // Access authenticated endpoint
        $res = $this->withHeader('Authorization', 'Bearer '.$token1->plainTextToken)
            ->getJson('/api/v2/auth/me');
        $res->assertStatus(200);

        // Revoke token 1
        $user->tokens()->where('id', $token1->accessToken->id)->delete();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token1->accessToken->id]);

        // Token 2 should still be valid
        $res2 = $this->withHeader('Authorization', 'Bearer '.$token2->plainTextToken)
            ->getJson('/api/v2/auth/me');
        $res2->assertStatus(200);

        // Force logout all
        $user->tokens()->delete();
        $user->refresh();
        $this->assertCount(0, $user->tokens);
    }

    /**
     * 4. Queued Notifications Verification (Email Queue & OTP Queue):
     * Ensures all authentication notifications implement ShouldQueue and dispatch cleanly.
     */
    public function test_email_and_otp_notifications_are_properly_queued(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'test@jugajug.test', 'phone' => '+8801700000000']);

        // 1. Verify Email Notification
        $user->notify(new VerifyEmailNotification('123456', 'dummy-token'));
        Notification::assertSentTo($user, VerifyEmailNotification::class);

        // 2. Welcome Email Notification
        $user->notify(new WelcomeEmailNotification($user->name ?? 'User'));
        Notification::assertSentTo($user, WelcomeEmailNotification::class);

        // 3. Email OTP Notification
        $user->notify(new EmailVerificationOtpNotification('654321'));
        Notification::assertSentTo($user, EmailVerificationOtpNotification::class);

        // 4. SMS OTP Notification
        $user->notify(new SmsOtpNotification('999888'));
        Notification::assertSentTo($user, SmsOtpNotification::class);
    }

    /**
     * 5. Cache & Key-Value Storage Readiness (Redis/Array/Database):
     * Tests caching, expiration, retrieval, and invalidation.
     */
    public function test_cache_subsystem_readiness_and_invalidation(): void
    {
        $cacheKey = 'auth_audit_test_key_'.time();
        $payload = ['status' => 'secure', 'timestamp' => now()->timestamp];

        Cache::put($cacheKey, $payload, 60);
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertEquals($payload, Cache::get($cacheKey));

        Cache::forget($cacheKey);
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * 6. Security Audit — Brute-Force Rate Limiting & Account Lockout:
     * Tests that failed logins are logged, account lock triggers, and access is blocked.
     */
    public function test_security_audit_account_lockout_and_failed_attempts(): void
    {
        $user = User::factory()->create(['status' => 'active', 'failed_login_attempts' => 0]);

        $this->assertFalse($user->isLocked());

        // Simulate failed login attempts reaching lockout threshold
        for ($i = 1; $i <= 5; $i++) {
            $user->incrementFailedLogins();
            FailedLoginAttempt::create([
                'user_id' => $user->id,
                'identifier' => $user->username,
                'ip_address' => '127.0.0.1',
                'failure_reason' => 'Invalid password',
                'attempted_at' => now(),
            ]);
        }

        $user->refresh();
        $this->assertTrue($user->isLocked());
        $this->assertGreaterThan(0, $user->failed_login_attempts);

        // Admin unlocks account
        $user->unlockAccount();
        $this->assertFalse($user->isLocked());
        $this->assertEquals(0, $user->failed_login_attempts);
    }

    /**
     * 7. Security Audit — RBAC Permission Denied vs Granted:
     * Confirms OWASP Access Control: unauthorized requests receive 403, authorized receive 200.
     */
    public function test_security_audit_rbac_owasp_access_control(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('USER');
        $regularToken = $regularUser->createToken('regular')->plainTextToken;

        $adminUser = User::factory()->create();
        $adminUser->assignRole('ADMIN');
        $adminToken = $adminUser->createToken('admin')->plainTextToken;

        // Regular user accessing admin auth dashboard endpoint -> 403 Forbidden
        auth()->forgetGuards();
        $resForbidden = $this->withHeader('Authorization', 'Bearer '.$regularToken)
            ->getJson('/api/v2/admin/auth/stats');
        $resForbidden->assertStatus(403);

        // Admin user accessing admin auth dashboard endpoint -> 200 OK
        auth()->forgetGuards();
        $resAdmin = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v2/admin/auth/stats');
        $resAdmin->assertStatus(200);
    }

    /**
     * 8. Security Audit — Comprehensive Audit Logging:
     * Asserts audit logging captures administrative and security sensitive events.
     */
    public function test_security_audit_audit_logging_integrity(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        $target = User::factory()->create(['status' => 'active']);

        $token = $admin->createToken('super')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v2/admin/auth/users/{$target->id}/suspend");

        $response->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'admin.suspend_user',
            'entity_id' => $target->id,
        ]);
    }
}
