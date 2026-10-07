<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminSession;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\UserSession;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnterpriseCompleteAuthSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_login_with_email_username_or_phone(): void
    {
        $user = User::factory()->create([
            'name' => 'Test Citizen',
            'username' => 'citizen100',
            'email' => 'citizen100@jugajug.com',
            'phone' => '+8801755667788',
            'password' => 'CitizenPass@123',
            'status' => 'active',
        ]);

        // 1. Login with email
        $resEmail = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'citizen100@jugajug.com',
            'password' => 'CitizenPass@123',
            'device_name' => 'Chrome Windows',
        ]);
        $resEmail->assertStatus(200)->assertJson(['success' => true]);

        // 2. Login with username
        $resUser = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'citizen100',
            'password' => 'CitizenPass@123',
            'device_name' => 'Firefox Linux',
        ]);
        $resUser->assertStatus(200)->assertJson(['success' => true]);

        // 3. Login with phone
        $resPhone = $this->postJson('/api/v1/auth/login', [
            'identifier' => '+8801755667788',
            'password' => 'CitizenPass@123',
            'device_name' => 'Safari iOS',
        ]);
        $resPhone->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login',
        ]);
    }

    public function test_super_admin_can_login_via_separate_admin_guard(): void
    {
        $admin = Admin::create([
            'name' => 'Super Administrator',
            'username' => 'superadmin',
            'email' => 'superadmin@jugajug.com',
            'phone' => '+8801700999999',
            'password' => Hash::make('AdminSecret@999'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/admin/login', [
            'identifier' => 'superadmin@jugajug.com',
            'password' => 'AdminSecret@999',
            'device_name' => 'Admin Workstation PRO',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'অ্যাডমিন লগইন সফল হয়েছে।',
                'data' => [
                    'admin' => [
                        'username' => 'superadmin',
                        'role' => 'super_admin',
                        'is_super_admin' => true,
                    ],
                    'token_type' => 'Bearer',
                ],
            ]);

        $this->assertDatabaseHas('admin_login_histories', [
            'admin_id' => $admin->id,
            'success' => true,
        ]);

        $this->assertDatabaseHas('admin_sessions', [
            'admin_id' => $admin->id,
            'device_name' => 'Admin Workstation PRO',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => Admin::class,
            'entity_id' => $admin->id,
            'action' => 'ADMIN_LOGIN_SUCCESS',
        ]);
    }

    public function test_admin_login_fails_with_invalid_credentials_and_locks_after_repeated_attempts(): void
    {
        $admin = Admin::create([
            'name' => 'Staff Admin',
            'username' => 'staffadmin',
            'email' => 'staff@jugajug.com',
            'password' => Hash::make('CorrectPassword@123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Attempt 5 incorrect logins
        for ($i = 0; $i < 5; $i++) {
            $res = $this->postJson('/api/v1/admin/login', [
                'identifier' => 'staffadmin',
                'password' => 'WrongPassword',
            ]);
            $res->assertStatus(401);
        }

        // 6th attempt should be locked (423)
        $lockedResponse = $this->postJson('/api/v1/admin/login', [
            'identifier' => 'staffadmin',
            'password' => 'WrongPassword',
        ]);

        $lockedResponse->assertStatus(423);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => Admin::class,
            'entity_id' => $admin->id,
            'action' => 'ADMIN_ACCOUNT_LOCKED',
        ]);
    }

    public function test_admin_can_logout_and_session_is_terminated(): void
    {
        $admin = Admin::create([
            'name' => 'Ops Admin',
            'username' => 'opsadmin',
            'email' => 'ops@jugajug.com',
            'password' => Hash::make('OpsPass@123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $loginRes = $this->postJson('/api/v1/admin/login', [
            'identifier' => 'opsadmin',
            'password' => 'OpsPass@123',
        ]);

        $token = $loginRes->json('data.token');

        $logoutRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/logout');

        $logoutRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'সফলভাবে অ্যাডমিন লগআউট সম্পন্ন হয়েছে।',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => Admin::class,
            'entity_id' => $admin->id,
            'action' => 'ADMIN_LOGOUT',
        ]);
    }

    public function test_admin_can_list_and_revoke_sessions(): void
    {
        $admin = Admin::create([
            'name' => 'Security Admin',
            'username' => 'secadmin',
            'email' => 'sec@jugajug.com',
            'password' => Hash::make('SecPass@123'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $session1 = AdminSession::create([
            'id' => Str::uuid()->toString(),
            'admin_id' => $admin->id,
            'ip_address' => '127.0.0.1',
            'device_name' => 'MacBook Pro M3',
            'last_activity' => time(),
        ]);

        $session2 = AdminSession::create([
            'id' => Str::uuid()->toString(),
            'admin_id' => $admin->id,
            'ip_address' => '192.168.1.50',
            'device_name' => 'iPhone 16 Pro',
            'last_activity' => time(),
        ]);

        $token = $admin->createToken('test-token', ['admin'])->plainTextToken;

        // List sessions
        $listRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/sessions');

        $listRes->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Revoke one session
        $deleteRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/admin/session/{$session1->id}");

        $deleteRes->assertStatus(200);

        $this->assertDatabaseMissing('admin_sessions', ['id' => $session1->id]);
        $this->assertDatabaseHas('admin_sessions', ['id' => $session2->id]);
    }

    public function test_user_can_send_and_verify_phone_otp(): void
    {
        $user = User::factory()->create([
            'phone' => '+8801799887766',
            'phone_verified_at' => null,
        ]);

        // 1. Send OTP
        $sendRes = $this->postJson('/api/v1/auth/send-phone-otp', [
            'phone' => '+8801799887766',
            'purpose' => 'phone_verification',
        ]);

        $sendRes->assertStatus(200)
            ->assertJson(['success' => true]);

        // Extract latest generated OTP code from database
        $otp = OtpCode::where('identifier', '+8801799887766')->latest()->first();
        $this->assertNotNull($otp);

        // 2. Verify with invalid OTP fails
        $invalidRes = $this->postJson('/api/v1/auth/verify-phone-otp', [
            'phone' => '+8801799887766',
            'otp' => '000000',
            'purpose' => 'phone_verification',
        ]);
        $invalidRes->assertStatus(422);

        // 3. For testing, calculate correct plain code matching hash or simulate
        // We know hash matches plain code via OtpService hashOtp (sha256 + app.key)
        // Let's create a known OTP
        $knownCode = '123456';
        $key = config('app.key') ?: 'base64:randomkeyfortestingpurpose123456789=';
        $hash = hash_hmac('sha256', $knownCode, $key);
        $otp->update(['code_hash' => $hash]);

        $validRes = $this->postJson('/api/v1/auth/verify-phone-otp', [
            'phone' => '+8801799887766',
            'otp' => '123456',
            'purpose' => 'phone_verification',
        ]);

        $validRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'verified' => true,
                ],
            ]);

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_user_can_update_password_with_history_prevention(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword@123'),
        ]);

        $token = $user->createToken('test')->plainTextToken;

        // 1. Cannot reuse same current password
        $reuseRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/auth/password', [
                'current_password' => 'OldPassword@123',
                'password' => 'OldPassword@123',
                'password_confirmation' => 'OldPassword@123',
            ]);
        $reuseRes->assertStatus(422);

        // 2. Successfully update with brand new compliant password
        $successRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/auth/password', [
                'current_password' => 'OldPassword@123',
                'password' => 'NewSecurePassword@2026',
                'password_confirmation' => 'NewSecurePassword@2026',
            ]);

        $successRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertTrue(Hash::check('NewSecurePassword@2026', $user->fresh()->password));

        // 3. Password history table records the new password
        $this->assertDatabaseHas('password_histories', [
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_list_and_revoke_devices(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('active-token')->plainTextToken;

        $session1 = UserSession::create([
            'user_id' => $user->id,
            'device_name' => 'Chrome on Windows 11',
            'ip_address' => '103.205.71.1',
            'last_active_at' => now(),
        ]);

        $session2 = UserSession::create([
            'user_id' => $user->id,
            'device_name' => 'Safari on iPhone 15',
            'ip_address' => '103.205.71.2',
            'last_active_at' => now(),
        ]);

        // List devices
        $listRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/devices');

        $listRes->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Revoke one device
        $deleteRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/auth/device/{$session1->id}");

        $deleteRes->assertStatus(200);

        $this->assertDatabaseMissing('user_sessions', ['id' => $session1->id]);
        $this->assertDatabaseHas('user_sessions', ['id' => $session2->id]);
    }

    public function test_webauthn_options_endpoint_generates_challenge(): void
    {
        $response = $this->getJson('/api/v1/auth/webauthn/options');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'challenge',
                    'rp' => ['name', 'id'],
                    'user' => ['id', 'name', 'displayName'],
                    'pubKeyCredParams',
                    'timeout',
                ],
            ]);
    }

    public function test_login_page_renders_with_user_and_super_admin_options(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('লগইন করুন')
            ->assertDontSee('সুপার অ্যাডমিন')
            ->assertSee('নতুন অ্যাকাউন্ট তৈরি করুন')
            ->assertSee('ক্যাপস লক');

        $adminResponse = $this->get('/admin/login');
        $adminResponse->assertStatus(200)
            ->assertSee('অ্যাডমিন সিকিউরিটি কনসোল');
    }
}
