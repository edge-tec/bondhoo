<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OtpService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ConnectlyPhase3SanctumAuthenticationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    /**
     * 1. Test Registration endpoint with Sanctum Bearer token generation.
     */
    public function test_user_registration_generates_sanctum_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Afrin Akter',
            'username' => 'afrin_akter_2026',
            'email' => 'afrin@connectly.app',
            'password' => 'Password123!',
            'device_name' => 'Pixel 9 Pro (Android)',
        ]);

        $response->assertStatus(201);
        $token = $response->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertEquals('afrin_akter_2026', $response->json('data.user.username'));

        $this->assertDatabaseHas('users', [
            'email' => 'afrin@connectly.app',
            'username' => 'afrin_akter_2026',
        ]);
    }

    /**
     * 2. Test Email and Username Login.
     */
    public function test_email_and_username_login(): void
    {
        $user = User::factory()->create([
            'name' => 'Sadia Islam',
            'username' => 'sadia_islam',
            'email' => 'sadia@connectly.app',
            'password' => Hash::make('SecretPass123!'),
        ]);

        // Login via email
        $emailLogin = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'sadia@connectly.app',
            'password' => 'SecretPass123!',
            'device_name' => 'Samsung S24 (Android)',
        ]);
        $emailLogin->assertStatus(200);
        $this->assertNotEmpty($emailLogin->json('data.token'));

        // Login via username
        $userLogin = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'sadia_islam',
            'password' => 'SecretPass123!',
            'device_name' => 'Samsung S24 (Android)',
        ]);
        $userLogin->assertStatus(200);
        $this->assertNotEmpty($userLogin->json('data.token'));
    }

    /**
     * 3. Test Phone Number Login.
     */
    public function test_phone_number_login(): void
    {
        $user = User::factory()->create([
            'phone' => '+8801712345678',
            'password' => Hash::make('PhonePass123!'),
        ]);

        $loginRes = $this->postJson('/api/v1/auth/login', [
            'identifier' => '+8801712345678',
            'password' => 'PhonePass123!',
            'device_name' => 'Android Device',
        ]);

        $loginRes->assertStatus(200);
        $this->assertNotEmpty($loginRes->json('data.token'));
    }

    /**
     * 4. Test Phone OTP Send and Verify.
     */
    public function test_phone_otp_dispatch_and_verification(): void
    {
        $phoneA = '+8801799887766';
        $phoneB = '+8801811223344';

        // 1. Send OTP endpoint
        $sendRes = $this->postJson('/api/v1/auth/send-phone-otp', [
            'phone' => $phoneA,
            'purpose' => 'phone_verification',
        ]);
        $sendRes->assertSuccessful();

        // 2. Verify with valid OTP for phoneB
        $otpService = app(OtpService::class);
        $otpResult = $otpService->generateOtp($phoneB, 'phone_verification', null, 10);
        $plainOtp = $otpResult['plain_otp'];

        $verifyRes = $this->postJson('/api/v1/auth/verify-phone-otp', [
            'phone' => $phoneB,
            'otp' => $plainOtp,
            'purpose' => 'phone_verification',
        ]);
        $verifyRes->assertSuccessful();
        $this->assertTrue($verifyRes->json('data.verified'));
    }

    /**
     * 5. Test Password Recovery link request.
     */
    public function test_password_recovery_request(): void
    {
        $user = User::factory()->create(['email' => 'recover@connectly.app']);

        $res = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'recover@connectly.app',
        ]);

        $res->assertStatus(200);
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'recover@connectly.app',
        ]);
    }

    /**
     * 6. Test Current User Profile (GET /api/v1/auth/me) and Profile Edit (PUT /api/v1/auth/profile).
     */
    public function test_current_user_profile_retrieval_and_editing(): void
    {
        $user = User::factory()->create([
            'name' => 'Rahat Khan',
            'username' => 'rahat_k',
            'email' => 'rahat@connectly.app',
        ]);

        // GET /api/v1/auth/me
        $meRes = $this->actingAs($user)->getJson('/api/v1/auth/me');
        $meRes->assertStatus(200);
        $this->assertEquals('rahat_k', $meRes->json('data.user.username'));

        // PUT /api/v1/auth/profile
        $editRes = $this->actingAs($user)->putJson('/api/v1/auth/profile', [
            'name' => 'Rahat Khan Updated',
            'bio' => 'Dream Big • Work Hard • Be Happy',
            'headline' => 'Lead Mobile Engineer',
            'location' => 'Dhaka, Bangladesh',
        ]);
        $editRes->assertSuccessful();
        $this->assertEquals('Rahat Khan Updated', $user->fresh()->name);
        $this->assertEquals('Dream Big • Work Hard • Be Happy', $user->fresh()->profile->bio);
    }

    /**
     * 7. Test Avatar and Cover Photo Uploads.
     */
    public function test_avatar_and_cover_image_uploads(): void
    {
        $user = User::factory()->create();

        $avatarFile = UploadedFile::fake()->image('avatar.jpg', 400, 400);
        $avatarRes = $this->actingAs($user)->postJson('/api/v1/profile/avatar', [
            'file' => $avatarFile,
        ]);
        $avatarRes->assertSuccessful();
        $this->assertNotNull($user->fresh()->profile?->avatar_url);

        $coverFile = UploadedFile::fake()->image('cover.jpg', 1200, 400);
        $coverRes = $this->actingAs($user)->postJson('/api/v1/profile/cover', [
            'file' => $coverFile,
        ]);
        $coverRes->assertSuccessful();
        $this->assertNotNull($user->fresh()->profile?->cover_url);
    }

    /**
     * 8. Test Friends, Follow, Unfollow, and Friend Request.
     */
    public function test_friends_and_following_interactions(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Follow user
        $followRes = $this->actingAs($userA)->postJson("/api/v1/friends/{$userB->id}/follow");
        $followRes->assertSuccessful();

        // Unfollow user
        $unfollowRes = $this->actingAs($userA)->deleteJson("/api/v1/friends/{$userB->id}/follow");
        $unfollowRes->assertSuccessful();

        // Friend Request Send
        $reqRes = $this->actingAs($userA)->postJson("/api/v1/friends/{$userB->id}/request");
        $reqRes->assertSuccessful();

        // Friend Request Accept
        $accRes = $this->actingAs($userB)->postJson("/api/v1/friends/{$userA->id}/accept");
        $accRes->assertSuccessful();

        // Unfriend
        $unfriendRes = $this->actingAs($userA)->deleteJson("/api/v1/friends/{$userB->id}");
        $unfriendRes->assertSuccessful();
    }

    /**
     * 9. Test Privacy Settings Update.
     */
    public function test_privacy_settings_update(): void
    {
        $user = User::factory()->create();

        $privacyRes = $this->actingAs($user)->putJson('/api/v1/profile/privacy', [
            'profile_visibility' => 'friends',
            'post_default_privacy' => 'friends',
        ]);
        $privacyRes->assertSuccessful();
    }

    /**
     * 10. Test Logout and Token Revocation with Session Expiration handling.
     */
    public function test_logout_revokes_sanctum_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Android Test Token')->plainTextToken;

        // Verify token works
        $testRes = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/auth/me');
        $testRes->assertStatus(200);

        // Perform logout
        $logoutRes = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/auth/logout');
        $logoutRes->assertSuccessful();

        // Assert token is revoked in database
        $tokenId = explode('|', $token)[0] ?? null;
        if ($tokenId) {
            $this->assertNull(PersonalAccessToken::find($tokenId));
        }

        // Subsequent call with revoked token returns 401 Unauthorized
        $unauthorizedRes = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/auth/me');
        $unauthorizedRes->assertStatus(401);
    }
}
