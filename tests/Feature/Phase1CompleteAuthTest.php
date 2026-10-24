<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OtpService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1CompleteAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Requirement 1.1: User Registration via /api/v1/auth/register
     */
    public function test_phase1_user_can_register_with_sanctum_token(): void
    {
        $payload = [
            'name' => 'Sarah Connor',
            'username' => 'sarah_c',
            'email' => 'sarah@skynet.test',
            'password' => 'CyberDyne2026!',
            'device_name' => 'iPhone-Client',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'username', 'email'],
                    'token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'username' => 'sarah_c',
            'email' => 'sarah@skynet.test',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'display_name' => 'Sarah Connor',
        ]);

        $this->assertDatabaseHas('privacy_settings', [
            'profile_visibility' => 'public',
        ]);
    }

    /**
     * Requirement 1.2: User Login via /api/v1/auth/login
     */
    public function test_phase1_user_can_login_and_access_protected_endpoint(): void
    {
        $user = User::factory()->create([
            'username' => 'john_doe',
            'email' => 'john@test.com',
            'password' => 'Password123!',
            'status' => 'active',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'john@test.com',
            'password' => 'Password123!',
            'device_name' => 'Android-Client',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'username', 'email'],
                ],
            ]);

        $token = $loginResponse->json('data.token');

        // Access protected /me route
        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => 'john_doe',
                    ],
                ],
            ]);
    }

    /**
     * Requirement 1.3: User Logout & Logout All via /api/v1/auth/logout & /api/v1/auth/logout-all
     */
    public function test_phase1_user_can_logout_and_revoke_tokens(): void
    {
        $user = User::factory()->create();
        $token1 = $user->createToken('device-1')->plainTextToken;
        $token2 = $user->createToken('device-2')->plainTextToken;

        $this->assertCount(2, $user->tokens);

        // Logout from device 1
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token1}")
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200);

        // Token 1 should be revoked, Token 2 remains
        $this->assertCount(1, $user->fresh()->tokens);

        // Device 2 performs logout-all
        $logoutAllResponse = $this->withHeader('Authorization', "Bearer {$token2}")
            ->postJson('/api/v1/auth/logout-all');

        $logoutAllResponse->assertStatus(200);
        $this->assertCount(0, $user->fresh()->tokens);
    }

    /**
     * Requirement 1.4: Email Verification via /api/v1/auth/verify-email
     */
    public function test_phase1_user_can_verify_email_via_v1_endpoint(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified@network.test',
            'status' => 'pending',
            'email_verified_at' => null,
        ]);

        $otpService = app(OtpService::class);
        $otp = $otpService->generateOtp($user->email, 'verify_email', $user);

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'identifier' => $user->email,
            'otp' => $otp['plain_otp'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['verified' => true],
            ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertEquals('active', $user->status);
    }

    /**
     * Requirement 1.5: Password Reset via /api/v1/auth/forgot-password and /api/v1/auth/reset-password
     */
    public function test_phase1_user_can_reset_password_with_token(): void
    {
        $user = User::factory()->create([
            'email' => 'resetme@network.test',
            'password' => 'OldPassword123!',
        ]);

        // 1. Request reset link / token
        $forgotResponse = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $forgotResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $token = $forgotResponse->json('data.reset_token');
        $this->assertNotEmpty($token);

        // 2. Reset password
        $resetResponse = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'BrandNewPassword2026!',
            'password_confirmation' => 'BrandNewPassword2026!',
        ]);

        $resetResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // 3. Verify old password fails and new password succeeds
        $oldLoginResponse = $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->email,
            'password' => 'OldPassword123!',
        ]);
        $oldLoginResponse->assertStatus(422);

        $newLoginResponse = $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->email,
            'password' => 'BrandNewPassword2026!',
        ]);
        $newLoginResponse->assertStatus(200);
    }
}
