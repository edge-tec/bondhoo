<?php

namespace Tests\Feature;

use App\Models\PhoneVerification;
use App\Models\User;
use App\Services\OtpService;
use App\Services\Sms\Drivers\BulkSmsBdDriver;
use App\Services\Sms\Drivers\LogSmsDriver;
use App\Services\Sms\Drivers\SslWirelessDriver;
use App\Services\Sms\Drivers\TwilioDriver;
use App\Services\Sms\SmsGatewayManager;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Step08PhoneOtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_phone_otp_verification_succeeds_with_valid_otp(): void
    {
        $user = User::factory()->create([
            'phone' => '+8801712345678',
            'phone_verified_at' => null,
            'status' => 'pending',
        ]);

        $otpService = app(OtpService::class);
        $otpData = $otpService->generateOtp($user->phone, 'verify_phone', $user, 10);
        $plainOtp = $otpData['plain_otp'];

        PhoneVerification::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'otp_hash' => $otpData['otp_model']->code_hash,
            'gateway' => 'log',
            'attempts' => 0,
            'max_attempts' => 3,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v2/auth/verify-mobile', [
            'identifier' => $user->phone,
            'otp' => $plainOtp,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['verified' => true],
            ]);

        $user->refresh();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertEquals('active', $user->status);

        // Verify phone_verifications table
        $verification = PhoneVerification::where('phone', $user->phone)->first();
        $this->assertNotNull($verification);
        $this->assertNotNull($verification->verified_at);

        // Verify otp_logs table
        $this->assertDatabaseHas('otp_logs', [
            'identifier' => $user->phone,
            'type' => 'verify_phone',
        ]);

        // Verify audit_logs table
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.phone_verified',
        ]);
    }

    public function test_phone_otp_verification_fails_when_expired(): void
    {
        $user = User::factory()->create([
            'phone' => '+8801799998888',
            'phone_verified_at' => null,
            'status' => 'pending',
        ]);

        $otpService = app(OtpService::class);
        $otpData = $otpService->generateOtp($user->phone, 'verify_phone', $user, 10);
        $plainOtp = $otpData['plain_otp'];

        // Force expired timestamp in database
        $otpData['otp_model']->update([
            'expires_at' => now()->subMinutes(5),
        ]);

        $response = $this->postJson('/api/v2/auth/verify-mobile', [
            'identifier' => $user->phone,
            'otp' => $plainOtp,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['otp']);

        $user->refresh();
        $this->assertNull($user->phone_verified_at);
        $this->assertEquals('pending', $user->status);
    }

    public function test_phone_otp_verification_fails_and_locks_after_3_retry_attempts(): void
    {
        $user = User::factory()->create([
            'phone' => '+8801811223344',
            'phone_verified_at' => null,
            'status' => 'pending',
        ]);

        $otpService = app(OtpService::class);
        $otpService->generateOtp($user->phone, 'verify_phone', $user, 10);

        // Attempt 1: Wrong OTP (2 tries left)
        $res1 = $this->postJson('/api/v2/auth/verify-mobile', [
            'identifier' => $user->phone,
            'otp' => '000001',
        ]);
        $res1->assertStatus(422)
            ->assertJsonValidationErrors(['otp']);

        // Attempt 2: Wrong OTP (1 try left)
        $res2 = $this->postJson('/api/v2/auth/verify-mobile', [
            'identifier' => $user->phone,
            'otp' => '000002',
        ]);
        $res2->assertStatus(422)
            ->assertJsonValidationErrors(['otp']);

        // Attempt 3: Wrong OTP (lockout)
        $res3 = $this->postJson('/api/v2/auth/verify-mobile', [
            'identifier' => $user->phone,
            'otp' => '000003',
        ]);
        $res3->assertStatus(422)
            ->assertJsonValidationErrors(['otp']);

        // Attempt 4: Even with correct OTP, it is now locked/used
        $res4 = $this->postJson('/api/v2/auth/verify-mobile', [
            'identifier' => $user->phone,
            'otp' => '000004',
        ]);
        $res4->assertStatus(422);

        $user->refresh();
        $this->assertNull($user->phone_verified_at);
    }

    public function test_phone_otp_enforces_60_second_resend_cooldown(): void
    {
        $user = User::factory()->create([
            'phone' => '+8801955667788',
        ]);

        // First send request
        $res1 = $this->postJson('/api/v2/auth/resend-otp', [
            'phone' => $user->phone,
        ]);
        $res1->assertStatus(200);

        // Immediate second attempt within 60s cooldown
        $res2 = $this->postJson('/api/v2/auth/resend-otp', [
            'phone' => $user->phone,
        ]);
        $res2->assertStatus(422)
            ->assertJsonValidationErrors(['otp']);
    }

    public function test_sms_provider_abstraction_supports_ssl_wireless_twilio_and_bulksmsbd(): void
    {
        $smsManager = app(SmsGatewayManager::class);

        $this->assertInstanceOf(SslWirelessDriver::class, $smsManager->driver('ssl_wireless'));
        $this->assertInstanceOf(BulkSmsBdDriver::class, $smsManager->driver('bulksmsbd'));
        $this->assertInstanceOf(TwilioDriver::class, $smsManager->driver('twilio'));
        $this->assertInstanceOf(LogSmsDriver::class, $smsManager->driver('log'));

        // Test sending via log driver
        $result = $smsManager->send('+8801700000000', 'Test message', 'log');
        $this->assertTrue($result['success']);
        $this->assertEquals('log', $result['gateway']);
    }
}
