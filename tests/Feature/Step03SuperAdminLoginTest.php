<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Permission;
use App\Notifications\AdminLoginNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Step03SuperAdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:admin-api', 'admin.permission:manage_users'])->get('/test/admin/users', function () {
            return response()->json(['success' => true, 'message' => 'User management allowed']);
        });

        Route::middleware(['auth:admin-api', 'admin.timeout'])->get('/test/admin/dashboard', function () {
            return response()->json(['success' => true, 'message' => 'Dashboard access allowed']);
        });
    }

    public function test_super_admin_can_login_via_green_button_flow(): void
    {
        Notification::fake();

        $admin = Admin::create([
            'name' => 'Chief Admin',
            'username' => 'chief_admin',
            'email' => 'chief@jugajug.com',
            'password' => Hash::make('SuperSecret@2026'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $res = $this->postJson('/api/v1/admin/login', [
            'identifier' => 'chief_admin',
            'password' => 'SuperSecret@2026',
            'device_name' => 'MacBook Workstation',
        ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'অ্যাডমিন লগইন সফল হয়েছে।',
            ])
            ->assertJsonPath('data.admin.role', 'super_admin')
            ->assertJsonPath('data.admin.is_super_admin', true);

        // Verify session creation
        $this->assertDatabaseHas('admin_sessions', [
            'admin_id' => $admin->id,
            'device_name' => 'MacBook Workstation',
        ]);

        // Verify login history
        $this->assertDatabaseHas('admin_login_histories', [
            'admin_id' => $admin->id,
            'success' => true,
        ]);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => Admin::class,
            'entity_id' => $admin->id,
            'action' => 'ADMIN_LOGIN_SUCCESS',
        ]);

        Notification::assertSentTo($admin, AdminLoginNotification::class);
    }

    public function test_admin_roles_moderator_and_manager_login(): void
    {
        $manager = Admin::create([
            'name' => 'Support Manager',
            'username' => 'support_mgr',
            'email' => 'manager@jugajug.com',
            'password' => Hash::make('MgrPass@2026'),
            'role' => 'manager',
            'status' => 'active',
        ]);

        $moderator = Admin::create([
            'name' => 'Content Mod',
            'username' => 'content_mod',
            'email' => 'mod@jugajug.com',
            'password' => Hash::make('ModPass@2026'),
            'role' => 'moderator',
            'status' => 'active',
        ]);

        // Manager Login
        $this->postJson('/api/v1/admin/login', [
            'identifier' => 'support_mgr',
            'password' => 'MgrPass@2026',
        ])->assertStatus(200)
            ->assertJsonPath('data.admin.role', 'manager');

        // Moderator Login
        $this->postJson('/api/v1/admin/login', [
            'identifier' => 'content_mod',
            'password' => 'ModPass@2026',
        ])->assertStatus(200)
            ->assertJsonPath('data.admin.role', 'moderator');
    }

    public function test_super_admin_has_all_permissions_automatically(): void
    {
        $superAdmin = Admin::create([
            'name' => 'Boss Admin',
            'username' => 'boss_admin',
            'email' => 'boss@jugajug.com',
            'password' => Hash::make('BossPass@2026'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $token = $superAdmin->createToken('test', ['admin'])->plainTextToken;

        // Access route protected by 'manage_users' permission
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/test/admin/users')
            ->assertStatus(200)
            ->assertJsonPath('message', 'User management allowed');
    }

    public function test_admin_permission_validation_blocks_unauthorized_admins(): void
    {
        $mod = Admin::create([
            'name' => 'Junior Mod',
            'username' => 'junior_mod',
            'email' => 'junior@jugajug.com',
            'password' => Hash::make('JuniorPass@2026'),
            'role' => 'moderator',
            'status' => 'active',
        ]);

        $token = $mod->createToken('test', ['admin'])->plainTextToken;

        // Moderator has no permissions initially -> 403
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/test/admin/users')
            ->assertStatus(403);

        // Grant permission
        $permission = Permission::firstOrCreate(['name' => 'manage_users', 'label' => 'Manage Users']);
        $mod->givePermission($permission);

        // Now authorized -> 200
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/test/admin/users')
            ->assertStatus(200);
    }

    public function test_ip_allowlist_blocks_unauthorized_ip_addresses(): void
    {
        Config::set('auth.admin_allowed_ips', '192.168.1.100, 10.0.0.1');

        $admin = Admin::create([
            'name' => 'Locked IP Admin',
            'username' => 'locked_ip_admin',
            'email' => 'lockedip@jugajug.com',
            'password' => Hash::make('Pass@12345'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Request from unauthorized IP (127.0.0.1)
        $res = $this->postJson('/api/v1/admin/login', [
            'identifier' => 'locked_ip_admin',
            'password' => 'Pass@12345',
        ]);

        $res->assertStatus(403)
            ->assertJsonPath('message', 'আপনার আইপি ঠিকানা থেকে অ্যাডমিন প্যানেলে প্রবেশাধিকার নেই।');

        // Request from allowed IP
        Config::set('auth.admin_allowed_ips', '127.0.0.1, 10.0.0.1');

        $successRes = $this->postJson('/api/v1/admin/login', [
            'identifier' => 'locked_ip_admin',
            'password' => 'Pass@12345',
        ]);

        $successRes->assertStatus(200);
    }

    public function test_web_session_based_admin_login(): void
    {
        $admin = Admin::create([
            'name' => 'Web Admin',
            'username' => 'web_admin',
            'email' => 'webadmin@jugajug.com',
            'password' => Hash::make('WebAdminPass@2026'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $res = $this->post('/admin/login', [
            'identifier' => 'web_admin',
            'password' => 'WebAdminPass@2026',
        ]);

        $res->assertRedirect('/admin/auth-management');
        $this->assertAuthenticatedAs($admin, 'admin');

        $this->assertDatabaseHas('admin_sessions', [
            'admin_id' => $admin->id,
            'device_name' => 'Web Browser',
        ]);
    }

    public function test_admin_with_2fa_enabled_receives_challenge_and_can_verify(): void
    {
        $admin = Admin::create([
            'name' => '2FA Admin',
            'username' => 'twofa_admin',
            'email' => 'twofa@jugajug.com',
            'password' => Hash::make('Secret@2026'),
            'role' => 'admin',
            'status' => 'active',
            'two_factor_enabled' => true,
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP', // standard test secret
            'two_factor_recovery_codes' => json_encode(['REC-CODE-1', 'REC-CODE-2']),
        ]);

        // Untrusted device login triggers 2FA
        $res = $this->postJson('/api/v1/admin/login', [
            'identifier' => 'twofa_admin',
            'password' => 'Secret@2026',
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('data.requires_2fa', true);

        $challengeToken = $res->json('data.challenge_token');
        $this->assertNotEmpty($challengeToken);

        // Verify with recovery code
        $verifyRes = $this->postJson('/api/v1/admin/verify-2fa', [
            'challenge_token' => $challengeToken,
            'code' => 'REC-CODE-1',
            'trust_device' => true,
        ]);

        $verifyRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '২-ফ্যাক্টর যাচাইকরণ সফল হয়েছে।',
            ]);

        $this->assertDatabaseHas('admin_trusted_devices', [
            'admin_id' => $admin->id,
        ]);
    }

    public function test_admin_trusted_device_can_bypass_2fa(): void
    {
        $admin = Admin::create([
            'name' => 'Trusted Admin',
            'username' => 'trusted_admin',
            'email' => 'trusted@jugajug.com',
            'password' => Hash::make('Trusted@2026'),
            'role' => 'admin',
            'status' => 'active',
            'two_factor_enabled' => true,
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        ]);

        $fingerprint = 'trusted-device-fingerprint-xyz';
        $admin->trustDevice($fingerprint, ['device_name' => 'Admin Laptop']);

        // Login with trusted device header
        $res = $this->withHeader('X-Device-Fingerprint', $fingerprint)
            ->postJson('/api/v1/admin/login', [
                'identifier' => 'trusted_admin',
                'password' => 'Trusted@2026',
            ]);

        // Bypasses 2FA challenge and logs in directly
        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonMissing(['requires_2fa' => true])
            ->assertJsonPath('message', 'অ্যাডমিন লগইন সফল হয়েছে।');
    }

    public function test_admin_can_list_and_revoke_sessions_and_trusted_devices(): void
    {
        $admin = Admin::create([
            'name' => 'Session Admin',
            'username' => 'session_admin',
            'email' => 'session@jugajug.com',
            'password' => Hash::make('Session@2026'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $token = $admin->createToken('admin-token', ['admin'])->plainTextToken;

        // Create session
        $session = $admin->sessions()->create([
            'id' => 'test-session-uuid-1',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'device_name' => 'Test Unit',
            'last_activity' => time(),
        ]);

        // Create trusted device
        $device = $admin->trustDevice('fp-test-123', ['device_name' => 'Office Workstation']);

        // List sessions
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/sessions')
            ->assertStatus(200)
            ->assertJsonPath('data.0.id', $session->id);

        // List trusted devices
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/trusted-devices')
            ->assertStatus(200)
            ->assertJsonPath('data.0.device_fingerprint', 'fp-test-123');

        // Revoke session
        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/admin/session/{$session->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('admin_sessions', ['id' => $session->id]);

        // Revoke trusted device
        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/admin/trusted-device/{$device->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('admin_trusted_devices', ['id' => $device->id]);
    }

    public function test_admin_session_timeout_middleware_enforces_expiry(): void
    {
        $admin = Admin::create([
            'name' => 'Timeout Admin',
            'username' => 'timeout_admin',
            'email' => 'timeout@jugajug.com',
            'password' => Hash::make('Timeout@2026'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $token = $admin->createToken('test', ['admin'])->plainTextToken;

        // Session created 31 minutes ago (exceeding 1800s timeout)
        $admin->sessions()->create([
            'id' => 'expired-session-uuid',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'device_name' => 'Old Session',
            'last_activity' => time() - 2000,
        ]);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/test/admin/dashboard');

        $res->assertStatus(401)
            ->assertJsonPath('message', 'নিষ্ক্রিয়তার কারণে অ্যাডমিন সেশনের মেয়াদ শেষ হয়ে গেছে। অনুগ্রহ করে পুনরায় লগইন করুন।');
    }
}
