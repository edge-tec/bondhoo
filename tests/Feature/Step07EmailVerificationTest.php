<?php

namespace Tests\Feature;

use App\Models\EmailVerification;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\WelcomeEmailNotification;
use App\Services\OtpService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class Step07EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_verify_email_via_secure_signed_url(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'user_signed@jugajug.com',
            'email_verified_at' => null,
            'status' => 'pending',
        ]);

        $token = Str::random(64);
        EmailVerification::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => hash('sha256', '123456'),
            'token' => $token,
            'expires_at' => now()->addMinutes(60),
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
                'token' => $token,
            ]
        );

        $response = $this->get($signedUrl);

        $response->assertRedirect('/login')
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertEquals('active', $user->status);

        $verification = EmailVerification::where('user_id', $user->id)->first();
        $this->assertNotNull($verification->verified_at);

        Notification::assertSentTo($user, WelcomeEmailNotification::class);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.email_verified',
        ]);
    }

    public function test_signed_url_fails_when_expired(): void
    {
        $user = User::factory()->create([
            'email' => 'expired_user@jugajug.com',
            'email_verified_at' => null,
            'status' => 'pending',
        ]);

        // Generate URL that expired 5 minutes ago
        $expiredUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(5),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->get($expiredUrl);

        $response->assertStatus(403);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
        $this->assertEquals('pending', $user->status);
    }

    public function test_signed_url_fails_with_invalid_hash(): void
    {
        $user = User::factory()->create([
            'email' => 'tampered_user@jugajug.com',
            'email_verified_at' => null,
            'status' => 'pending',
        ]);

        $tamperedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => 'invalid_sha1_hash_tampered',
            ]
        );

        $response = $this->get($tamperedUrl);

        $response->assertStatus(403);
    }

    public function test_user_can_verify_email_via_api_otp(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'otp_user@jugajug.com',
            'email_verified_at' => null,
            'status' => 'pending',
        ]);

        $otpService = app(OtpService::class);
        $otpData = $otpService->generateOtp($user->email, 'verify_email', $user, 15);
        $plainOtp = $otpData['plain_otp'];

        EmailVerification::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => $otpData['otp_model']->code_hash,
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes(60),
        ]);

        $response = $this->postJson('/api/v2/auth/verify-email', [
            'identifier' => $user->email,
            'otp' => $plainOtp,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['verified' => true],
            ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertEquals('active', $user->status);

        Notification::assertSentTo($user, WelcomeEmailNotification::class);
    }

    public function test_user_can_verify_email_via_api_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'token_user@jugajug.com',
            'email_verified_at' => null,
            'status' => 'pending',
        ]);

        $token = Str::random(64);

        EmailVerification::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => hash('sha256', '112233'),
            'token' => $token,
            'expires_at' => now()->addMinutes(60),
        ]);

        $response = $this->postJson('/api/v2/auth/verify-email', [
            'identifier' => $user->email,
            'token' => $token,
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

    public function test_verification_fails_with_invalid_otp_and_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid_otp_user@jugajug.com',
            'email_verified_at' => null,
            'status' => 'pending',
        ]);

        // Wrong OTP
        $response = $this->postJson('/api/v2/auth/verify-email', [
            'identifier' => $user->email,
            'otp' => '000000',
        ]);
        $response->assertStatus(422);

        // Wrong Token
        $response2 = $this->postJson('/api/v2/auth/verify-email', [
            'identifier' => $user->email,
            'token' => 'invalid_nonexistent_token_string',
        ]);
        $response2->assertStatus(422);
    }

    public function test_resend_verification_email_creates_new_token_and_dispatches_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'resend_target@jugajug.com',
            'email_verified_at' => null,
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/v2/auth/resend-email', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'sent' => true,
                ],
            ]);

        Notification::assertSentTo($user, VerifyEmailNotification::class);

        $this->assertDatabaseHas('email_verifications', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function test_verification_status_endpoint_reports_correct_state(): void
    {
        $user = User::factory()->create([
            'email' => 'status_check@jugajug.com',
            'email_verified_at' => null,
            'status' => 'pending',
        ]);

        // Before verification
        $res1 = $this->getJson('/api/v2/auth/verification-status?email='.urlencode($user->email));
        $res1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'exists' => true,
                    'is_verified' => false,
                    'status' => 'pending',
                ],
            ]);

        // Verify user
        $user->markEmailAsVerified();
        $user->update(['status' => 'active']);

        // After verification
        $res2 = $this->getJson('/api/v2/auth/verification-status?email='.urlencode($user->email));
        $res2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'exists' => true,
                    'is_verified' => true,
                    'status' => 'active',
                ],
            ]);
    }
}
