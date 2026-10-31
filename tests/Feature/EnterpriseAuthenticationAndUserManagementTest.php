<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterpriseAuthenticationAndUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. Test User Registration with validation and Sanctum token generation.
     */
    public function test_user_registration_creates_account_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Connectly User',
            'username' => 'connectly_user_1',
            'email' => 'connectly1@example.com',
            'password' => 'SecurePass@123',
            'device_name' => 'Pixel 9 Pro',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'user' => ['id', 'username', 'email'],
                'token',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'connectly_user_1',
            'email' => 'connectly1@example.com',
        ]);
    }

    /**
     * 2. Test Login with email, username, or phone and secure token issuance.
     */
    public function test_login_with_email_and_username_and_phone(): void
    {
        $user = User::factory()->create([
            'name' => 'Sadia Islam',
            'username' => 'sadia_islam',
            'email' => 'sadia@connectly.com',
            'phone' => '+8801711223344',
            'password' => 'Password@123',
            'status' => 'active',
        ]);

        // Login via Email
        $emailRes = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'sadia@connectly.com',
            'password' => 'Password@123',
            'device_name' => 'Android Phone',
        ]);
        $emailRes->assertStatus(200);
        $this->assertNotEmpty($emailRes->json('data.token'));

        // Login via Username
        $userRes = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'sadia_islam',
            'password' => 'Password@123',
            'device_name' => 'Android Tablet',
        ]);
        $userRes->assertStatus(200);

        // Login via Phone
        $phoneRes = $this->postJson('/api/v1/auth/login', [
            'identifier' => '+8801711223344',
            'password' => 'Password@123',
            'device_name' => 'Android Mobile',
        ]);
        $phoneRes->assertStatus(200);
    }

    /**
     * 3. Test OTP generation and verification for mobile login.
     */
    public function test_otp_generation_and_verification(): void
    {
        $phone = '+8801712345678';
        $user = User::factory()->create(['phone' => $phone]);

        // Send OTP
        $sendRes = $this->postJson('/api/v1/auth/send-phone-otp', [
            'phone' => $phone,
        ]);
        $sendRes->assertStatus(200);

        // Retrieve OTP code from database via identifier
        $otp = OtpCode::where('identifier', $phone)->latest()->first();
        $this->assertNotNull($otp);

        // Update with known code for verification test
        $knownCode = '123456';
        $key = config('app.key');
        $hash = hash_hmac('sha256', $knownCode, $key);
        $otp->update(['code_hash' => $hash]);

        // Verify OTP
        $verifyRes = $this->postJson('/api/v1/auth/verify-phone-otp', [
            'phone' => $phone,
            'otp' => '123456',
            'purpose' => 'phone_verification',
        ]);
        $verifyRes->assertStatus(200);
        $this->assertTrue($verifyRes->json('data.verified'));
    }

    /**
     * 4. Test Password Reset request and token management.
     */
    public function test_password_reset_request(): void
    {
        $user = User::factory()->create(['email' => 'reset_me@connectly.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'reset_me@connectly.com',
        ]);

        $this->assertContains($response->status(), [200, 202]);
    }

    /**
     * 5. Test Current User Profile, Session management, and Secure Logout.
     */
    public function test_profile_me_device_sessions_and_logout(): void
    {
        $user = User::factory()->create(['username' => 'active_user']);

        // Check /auth/me
        $meRes = $this->actingAs($user)->getJson('/api/v1/auth/me');
        $meRes->assertStatus(200);
        $this->assertEquals('active_user', $meRes->json('data.user.username') ?? $meRes->json('data.username'));

        // Check device sessions
        $deviceRes = $this->actingAs($user)->getJson('/api/v1/auth/devices');
        $deviceRes->assertStatus(200);

        // Secure Logout
        $logoutRes = $this->actingAs($user)->postJson('/api/v1/auth/logout');
        $logoutRes->assertStatus(200);
    }

    /**
     * 6. Test Profile Bio, Personal Info, and Photo Upload.
     */
    public function test_profile_update_and_media_upload(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['username' => 'mizanur_rahman']);

        // Update bio and personal info
        $updateRes = $this->actingAs($user)->putJson('/api/v1/auth/profile', [
            'bio' => 'Dream Big • Work Hard • Be Happy',
            'occupation' => 'Software Engineer',
            'location' => 'Dhaka, Bangladesh',
        ]);
        $updateRes->assertStatus(200);

        // Upload Profile Avatar
        $avatarFile = UploadedFile::fake()->image('avatar.jpg', 400, 400);
        $avatarRes = $this->actingAs($user)->postJson('/api/v1/profile/avatar', [
            'file' => $avatarFile,
        ]);
        $this->assertContains($avatarRes->status(), [200, 201]);

        // Upload Cover Photo
        $coverFile = UploadedFile::fake()->image('cover.jpg', 1200, 400);
        $coverRes = $this->actingAs($user)->postJson('/api/v1/profile/cover', [
            'file' => $coverFile,
        ]);
        $this->assertContains($coverRes->status(), [200, 201]);
    }

    /**
     * 7. Test Friend Requests, Follow/Unfollow, and Block/Unblock.
     */
    public function test_friends_follow_and_block_system(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        // Send Friend Request
        $requestRes = $this->actingAs($userA)->postJson("/api/v1/friends/{$userB->id}/request");
        $this->assertContains($requestRes->status(), [200, 201]);

        // Accept Friend Request
        $acceptRes = $this->actingAs($userB)->postJson("/api/v1/friends/{$userA->id}/accept");
        $acceptRes->assertStatus(200);

        // Follow User
        $followRes = $this->actingAs($userA)->postJson("/api/v1/friends/{$userB->id}/follow");
        $this->assertContains($followRes->status(), [200, 201]);

        // Unfollow User
        $unfollowRes = $this->actingAs($userA)->deleteJson("/api/v1/friends/{$userB->id}/follow");
        $unfollowRes->assertStatus(200);

        // Block User
        $blockRes = $this->actingAs($userA)->postJson("/api/v1/friends/{$userB->id}/block");
        $this->assertContains($blockRes->status(), [200, 201]);

        // Unblock User
        $unblockRes = $this->actingAs($userA)->deleteJson("/api/v1/friends/{$userB->id}/block");
        $unblockRes->assertStatus(200);
    }

    /**
     * 8. Test Profile Privacy Settings.
     */
    public function test_profile_privacy_settings(): void
    {
        $user = User::factory()->create();

        // Get Privacy Settings
        $getRes = $this->actingAs($user)->getJson('/api/v2/profile/privacy/settings');
        $getRes->assertStatus(200);

        // Update Privacy Settings
        $updateRes = $this->actingAs($user)->putJson('/api/v2/profile/privacy/settings', [
            'profile_visibility' => 'friends',
            'avatar_privacy' => 'public',
            'bio_privacy' => 'friends',
        ]);
        $updateRes->assertStatus(200);
    }
}
