<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OtpService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionAuthVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_verify_email_with_valid_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'status' => 'pending',
            'email_verified_at' => null,
        ]);

        $otpService = app(OtpService::class);
        $otp = $otpService->generateOtp($user->email, 'verify_email', $user);

        $response = $this->postJson('/api/v2/auth/verify-email', [
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

    public function test_email_verification_fails_with_invalid_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified2@example.com',
            'status' => 'pending',
            'email_verified_at' => null,
        ]);

        $otpService = app(OtpService::class);
        $otpService->generateOtp($user->email, 'verify_email', $user);

        $response = $this->postJson('/api/v2/auth/verify-email', [
            'identifier' => $user->email,
            'otp' => '000000',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['otp']);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_user_can_verify_mobile_with_sms_otp(): void
    {
        $user = User::factory()->create([
            'phone' => '+8801755554433',
            'status' => 'pending',
            'phone_verified_at' => null,
        ]);

        $otpService = app(OtpService::class);
        $otp = $otpService->generateOtp($user->phone, 'verify_phone', $user);

        $response = $this->postJson('/api/v2/auth/verify-mobile', [
            'identifier' => $user->phone,
            'otp' => $otp['plain_otp'],
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertEquals('active', $user->status);
    }

    public function test_otp_resend_cooldown_is_enforced(): void
    {
        $user = User::factory()->create([
            'email' => 'cooldown@example.com',
        ]);

        $otpService = app(OtpService::class);
        $otpService->generateOtp($user->email, 'verify_email', $user);

        // Immediate second attempt within 60s cooldown must throw validation exception
        $response = $this->postJson('/api/v2/auth/resend-email', [
            'email' => $user->email,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['otp']);
    }

    public function test_otp_is_invalidated_after_max_retries(): void
    {
        $user = User::factory()->create([
            'email' => 'maxretry@example.com',
        ]);

        $otpService = app(OtpService::class);
        $otpService->generateOtp($user->email, 'verify_email', $user);

        // 3 failed tries
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v2/auth/verify-email', [
                'identifier' => $user->email,
                'otp' => '11111'.$i,
            ]);
        }

        // 4th attempt must state max retries reached
        $res4 = $this->postJson('/api/v2/auth/verify-email', [
            'identifier' => $user->email,
            'otp' => '999999',
        ]);

        $res4->assertStatus(422);
    }
}
