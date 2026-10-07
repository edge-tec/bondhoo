<?php

namespace Tests\Feature;

use App\Models\EmailVerification;
use App\Models\FailedLoginAttempt;
use App\Models\LoginHistory;
use App\Models\OtpCode;
use App\Models\PasswordHistory;
use App\Models\User;
use App\Models\UserSession;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Step14AdminAuthDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create([
            'name' => 'Admin Boss',
            'username' => 'admin_boss',
            'email' => 'admin@jugajug.test',
        ]);
        $this->admin->assignRole('ADMIN');
        $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;
    }

    /**
     * Test all 8 dashboard widgets in /api/v2/admin/auth/stats.
     */
    public function test_admin_can_view_all_eight_dashboard_widgets(): void
    {
        // 1. Online Users
        User::factory()->create(['last_login_at' => now()->subMinutes(5)]);
        User::factory()->create(['last_login_at' => now()->subHours(2)]); // offline

        // 2. Failed Logins
        FailedLoginAttempt::create([
            'identifier' => 'test@jugajug.test',
            'ip_address' => '127.0.0.1',
            'failure_reason' => 'Invalid credentials',
            'attempted_at' => now()->subMinutes(10),
        ]);

        // 3. Locked Accounts
        User::factory()->create(['locked_until' => now()->addMinutes(15)]);

        // 4. Active Sessions
        $user = User::factory()->create();
        UserSession::create([
            'user_id' => $user->id,
            'device_name' => 'iPhone 15 Pro',
            'browser' => 'Mobile Safari',
            'os' => 'iOS',
            'ip_address' => '103.205.71.1',
            'is_current' => true,
            'last_active_at' => now(),
        ]);

        // 5. OTP Logs
        OtpCode::create([
            'identifier' => '01711000000',
            'purpose' => 'verify_phone',
            'code_hash' => hash('sha256', '123456'),
            'expires_at' => now()->addMinutes(5),
            'is_used' => true,
        ]);

        // 6. Email Verification Logs
        EmailVerification::create([
            'user_id' => $user->id,
            'email' => 'user@jugajug.test',
            'otp_hash' => hash('sha256', '654321'),
            'token' => 'secure-token-1234567890',
            'expires_at' => now()->addMinutes(60),
            'verified_at' => now(),
        ]);

        // 7. Password Reset Logs
        PasswordHistory::create([
            'user_id' => $user->id,
            'password_hash' => 'dummy-hash',
            'created_at' => now(),
        ]);

        // 8. Login Analytics
        LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => '103.205.71.1',
            'browser' => 'Chrome',
            'os' => 'macOS',
            'device_type' => 'desktop',
            'country' => 'BD',
            'city' => 'Dhaka',
            'status' => 'success',
            'created_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v2/admin/auth/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'online_users',
                    'failed_logins' => ['last_24h', 'total', 'recent'],
                    'locked_accounts',
                    'active_sessions',
                    'otp_logs' => ['total', 'used', 'expired'],
                    'email_verification_logs' => ['total', 'verified', 'pending'],
                    'password_reset_logs' => ['total', 'password_changes'],
                    'login_analytics' => [
                        'total',
                        'by_status',
                        'by_browser',
                        'by_os',
                        'by_device',
                        'by_country',
                        'last_7_days',
                    ],
                    'total_users',
                    'verified_users',
                    'failed_logins_24h',
                ],
            ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, $data['online_users']);
        $this->assertGreaterThanOrEqual(1, $data['failed_logins']['total']);
        $this->assertGreaterThanOrEqual(1, $data['locked_accounts']);
        $this->assertGreaterThanOrEqual(1, $data['active_sessions']);
        $this->assertGreaterThanOrEqual(1, $data['otp_logs']['total']);
        $this->assertGreaterThanOrEqual(1, $data['email_verification_logs']['total']);
        $this->assertGreaterThanOrEqual(1, $data['password_reset_logs']['total']);
        $this->assertGreaterThanOrEqual(1, $data['login_analytics']['total']);
    }

    /**
     * Test search and filtering in users API.
     */
    public function test_admin_can_search_and_filter_users(): void
    {
        User::factory()->create([
            'name' => 'Rahim Uddin',
            'username' => 'rahim_uddin',
            'email' => 'rahim@jugajug.test',
            'status' => 'active',
        ]);

        User::factory()->create([
            'name' => 'Karim Chowdhury',
            'username' => 'karim_bd',
            'email' => 'karim@jugajug.test',
            'status' => 'suspended',
            'locked_until' => now()->addHours(2),
        ]);

        // Search by name
        $searchRes = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v2/admin/auth/users?search=Rahim');

        $searchRes->assertStatus(200);
        $this->assertCount(1, $searchRes->json('data.data'));
        $this->assertEquals('rahim_uddin', $searchRes->json('data.data.0.username'));

        // Filter by status suspended
        $filterRes = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v2/admin/auth/users?status=suspended');

        $filterRes->assertStatus(200);
        $this->assertCount(1, $filterRes->json('data.data'));
        $this->assertEquals('karim_bd', $filterRes->json('data.data.0.username'));

        // Filter by locked only
        $lockedRes = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v2/admin/auth/users?locked_only=true');

        $lockedRes->assertStatus(200);
        $this->assertCount(1, $lockedRes->json('data.data'));
        $this->assertEquals('karim_bd', $lockedRes->json('data.data.0.username'));
    }

    /**
     * Test listing, searching, and filtering active sessions.
     */
    public function test_admin_can_view_and_filter_active_sessions(): void
    {
        $user1 = User::factory()->create(['name' => 'Tanvir Hasan']);
        $user2 = User::factory()->create(['name' => 'Sadia Afrin']);

        UserSession::create([
            'user_id' => $user1->id,
            'device_name' => 'MacBook Pro M3',
            'browser' => 'Chrome',
            'os' => 'macOS',
            'ip_address' => '103.100.20.5',
            'is_current' => true,
            'last_active_at' => now(),
        ]);

        UserSession::create([
            'user_id' => $user2->id,
            'device_name' => 'Samsung Galaxy S24',
            'browser' => 'Chrome Mobile',
            'os' => 'Android',
            'ip_address' => '103.100.20.9',
            'is_current' => false,
            'last_active_at' => now()->subDay(),
        ]);

        // Search by device name
        $searchRes = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v2/admin/auth/sessions?search=MacBook');

        $searchRes->assertStatus(200);
        $this->assertCount(1, $searchRes->json('data.data'));
        $this->assertEquals('MacBook Pro M3', $searchRes->json('data.data.0.device_name'));

        // Filter by OS
        $osRes = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v2/admin/auth/sessions?os=Android');

        $osRes->assertStatus(200);
        $this->assertCount(1, $osRes->json('data.data'));
        $this->assertEquals('Samsung Galaxy S24', $osRes->json('data.data.0.device_name'));
    }

    /**
     * Test revoking a user session.
     */
    public function test_admin_can_revoke_user_session(): void
    {
        $targetUser = User::factory()->create();
        $token = $targetUser->createToken('target-device');

        $session = UserSession::create([
            'user_id' => $targetUser->id,
            'token_id' => $token->accessToken->id,
            'device_name' => 'iPad Air',
            'browser' => 'Safari',
            'os' => 'iOS',
            'ip_address' => '127.0.0.1',
            'is_current' => true,
            'last_active_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->deleteJson("/api/v2/admin/auth/sessions/{$session->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['revoked' => true],
            ]);

        $this->assertDatabaseMissing('user_sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.revoke_session',
            'user_id' => $this->admin->id,
        ]);
    }

    /**
     * Test email verifications, password resets, and OTP logs endpoints.
     */
    public function test_admin_can_view_logs_with_search_and_filters(): void
    {
        $user = User::factory()->create(['name' => 'Farhan Ahmed']);

        EmailVerification::create([
            'user_id' => $user->id,
            'email' => 'farhan@jugajug.test',
            'otp_hash' => hash('sha256', '111222'),
            'token' => 'unique-token-999',
            'expires_at' => now()->addMinutes(30),
            'verified_at' => now(),
        ]);

        PasswordHistory::create([
            'user_id' => $user->id,
            'password_hash' => 'hash123',
            'created_at' => now(),
        ]);

        OtpCode::create([
            'user_id' => $user->id,
            'identifier' => 'farhan@jugajug.test',
            'purpose' => 'login_2fa',
            'code_hash' => hash('sha256', '999888'),
            'expires_at' => now()->addMinutes(5),
            'is_used' => true,
        ]);

        // Email Verifications endpoint
        $emailRes = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v2/admin/auth/email-verifications?search=farhan');
        $emailRes->assertStatus(200);
        $this->assertCount(1, $emailRes->json('data.data'));

        // Password Resets endpoint
        $passRes = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v2/admin/auth/password-resets?search=Farhan');
        $passRes->assertStatus(200);
        $this->assertCount(1, $passRes->json('data.data'));

        // OTP Logs endpoint
        $otpRes = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v2/admin/auth/otp-logs?purpose=login_2fa');
        $otpRes->assertStatus(200);
        $this->assertCount(1, $otpRes->json('data.data'));
    }

    /**
     * Test CSV export for users, sessions, and login logs.
     */
    public function test_csv_export_features(): void
    {
        User::factory()->create(['name' => 'CSV Export User', 'email' => 'csvexport@jugajug.test']);

        // 1. Export Users
        $usersExport = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->get('/api/v2/admin/auth/export?type=users');

        $usersExport->assertStatus(200);
        $this->assertStringContainsString('text/csv', $usersExport->headers->get('Content-Type'));
        $this->assertStringContainsString('csvexport@jugajug.test', $usersExport->streamedContent());

        // 2. Export Sessions
        $sessionUser = User::factory()->create();
        UserSession::create([
            'user_id' => $sessionUser->id,
            'device_name' => 'CSV Device Pixel 8',
            'browser' => 'Chrome',
            'os' => 'Android',
            'ip_address' => '10.0.0.99',
            'is_current' => true,
            'last_active_at' => now(),
        ]);

        $sessionsExport = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->get('/api/v2/admin/auth/export?type=sessions');

        $sessionsExport->assertStatus(200);
        $this->assertStringContainsString('text/csv', $sessionsExport->headers->get('Content-Type'));
        $this->assertStringContainsString('CSV Device Pixel 8', $sessionsExport->streamedContent());

        // 3. Export Logins
        LoginHistory::create([
            'user_id' => $sessionUser->id,
            'ip_address' => '192.168.1.50',
            'browser' => 'Firefox',
            'os' => 'Linux',
            'device_type' => 'desktop',
            'country' => 'BD',
            'city' => 'Chittagong',
            'status' => 'success',
            'created_at' => now(),
        ]);

        $loginsExport = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->get('/api/v2/admin/auth/export?type=logins');

        $loginsExport->assertStatus(200);
        $this->assertStringContainsString('text/csv', $loginsExport->headers->get('Content-Type'));
        $this->assertStringContainsString('192.168.1.50', $loginsExport->streamedContent());
    }

    /**
     * Test permission enforcement: 401 unauthenticated, 403 unauthorized, 200 authorized.
     */
    public function test_permission_enforcement_on_admin_auth_endpoints(): void
    {
        // 1. Unauthenticated request -> 401
        $unauth = $this->getJson('/api/v2/admin/auth/stats');
        $unauth->assertStatus(401);

        // 2. Regular User without manage.users permission -> 403
        $regularUser = User::factory()->create();
        $regularUser->assignRole('USER');
        $userToken = $regularUser->createToken('user-token')->plainTextToken;

        auth()->forgetGuards();

        $forbidden = $this->withHeader('Authorization', "Bearer {$userToken}")
            ->getJson('/api/v2/admin/auth/stats');

        $forbidden->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'এই অ্যাকশনটি সম্পাদন করার পর্যাপ্ত পারমিশন আপনার নেই।',
            ]);

        // 3. Admin user with manage.users permission -> 200
        auth()->forgetGuards();

        $adminRes = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v2/admin/auth/stats');

        $adminRes->assertStatus(200);

        // 4. Super Admin -> 200
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('SUPER_ADMIN');
        $superAdminToken = $superAdmin->createToken('super-admin-token')->plainTextToken;

        auth()->forgetGuards();

        $superRes = $this->withHeader('Authorization', "Bearer {$superAdminToken}")
            ->getJson('/api/v2/admin/auth/stats');

        $superRes->assertStatus(200);
    }

    /**
     * Test admin auth management dashboard view renders successfully.
     */
    public function test_admin_auth_management_dashboard_view_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/admin/auth-management');

        $response->assertStatus(200);
        $response->assertSee('অথেন্টিকেশন ও সিকিউরিটি ম্যানেজমেন্ট');
        $response->assertSee('Online Users');
        $response->assertSee('Active Sessions');
        $response->assertSee('Failed Logins');
        $response->assertSee('OTP Logs');
    }
}
