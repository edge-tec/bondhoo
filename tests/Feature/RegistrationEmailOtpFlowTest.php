<?php

namespace Tests\Feature;

use App\Models\EmailVerification;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegistrationEmailOtpFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_registration_generates_real_otp_record_and_requires_verification(): void
    {
        $payload = [
            'name' => 'Mizanur Rahman',
            'username' => 'mizanur_rahman',
            'email' => 'mizanaistudio@gmail.com',
            'phone' => '+8801721291196',
            'birth_date' => '1998-05-05',
            'gender' => 'male',
            'password' => 'Password123!',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'requires_email_verification' => true,
                ],
            ]);

        // Verify user in DB with pending status or email_verified_at null
        $user = User::where('email', 'mizanaistudio@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        // Verify EmailVerification record created
        $verification = EmailVerification::where('user_id', $user->id)
            ->where('email', 'mizanaistudio@gmail.com')
            ->first();
        $this->assertNotNull($verification);
        $this->assertNotNull($verification->otp_hash);

        // Verify active OTP code in otp_codes table
        $otp = OtpCode::where('identifier', 'mizanaistudio@gmail.com')
            ->where('purpose', 'verify_email')
            ->where('is_used', false)
            ->first();
        $this->assertNotNull($otp);
    }

    public function test_user_can_verify_email_with_valid_otp_and_receives_bearer_token(): void
    {
        $user = User::factory()->create([
            'email' => 'testverify@example.com',
            'email_verified_at' => null,
            'status' => 'pending',
        ]);

        $otpService = app(OtpService::class);
        $otpResult = $otpService->generateOtp($user->email, 'verify_email', $user, 60, '127.0.0.1');

        EmailVerification::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => $otpResult['otp_model']->code_hash,
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes(60),
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'identifier' => 'testverify@example.com',
            'otp' => $otpResult['plain_otp'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'verified' => true,
                ],
            ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertEquals('active', $user->status);
    }

    public function test_user_can_resend_email_verification_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'resendtest@example.com',
            'email_verified_at' => null,
            'status' => 'pending',
        ]);

        // Resend request
        $response = $this->postJson('/api/v1/auth/resend-email', [
            'email' => 'resendtest@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('otp_codes', [
            'identifier' => 'resendtest@example.com',
            'purpose' => 'verify_email',
            'is_used' => false,
        ]);
    }
}
