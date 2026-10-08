<?php

namespace Tests\Feature;

use App\Mail\PasswordChangedMail;
use App\Mail\PasswordResetMail;
use App\Mail\VerifyEmailMail;
use App\Mail\WelcomeMail;
use App\Models\EmailLog;
use App\Models\EmailVerification;
use App\Models\SmtpSetting;
use App\Models\User;
use App\Services\Email\EmailService;
use App\Services\Email\SmtpConfigService;
use App\Services\OtpService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class MasterTransactionalEmailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        SmtpSetting::firstOrCreate([
            'mail_mailer' => 'smtp',
            'mail_host' => 'mail.bondhoo.com',
            'mail_port' => 587,
            'mail_username' => 'no_reply@bondhoo.com',
            'mail_password' => 'secret_test',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'no_reply@bondhoo.com',
            'mail_from_name' => 'Bondhoo',
            'is_active' => true,
            'is_enabled' => true,
        ]);
        Cache::forget(SmtpConfigService::CACHE_KEY);
    }

    /**
     * TEST 1: Registration sends immediate synchronous VerifyEmailMail through EmailService
     */
    public function test_registration_flow_dispatches_verification_email_immediately(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v2/auth/register', [
            'first_name' => 'Khadija',
            'last_name' => 'Akter',
            'username' => 'khadija_akter',
            'email' => 'khadija@bondhoo.com',
            'password' => 'Pass@12345678',
            'password_confirmation' => 'Pass@12345678',
            'birth_date' => '2000-01-01',
            'gender' => 'female',
            'country' => 'BD',
            'terms' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'email_sent' => true,
            ]);

        $user = User::where('email', 'khadija@bondhoo.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('pending', $user->status);
        $this->assertNull($user->email_verified_at);

        // Verify token in database
        $verification = EmailVerification::where('user_id', $user->id)->first();
        $this->assertNotNull($verification);
        $this->assertNotNull($verification->token);
        $this->assertNotNull($verification->otp_hash);

        // Verify Mail was sent via VerifyEmailMail
        Mail::assertSent(VerifyEmailMail::class, function ($mail) {
            return $mail->hasTo('khadija@bondhoo.com')
                && ! empty($mail->otp)
                && str_contains($mail->verificationUrl, '/email/verify/');
        });

        // Verify EmailLog created
        $this->assertDatabaseHas('email_logs', [
            'recipient' => 'khadija@bondhoo.com',
            'email_type' => 'verify_email',
        ]);
    }

    /**
     * TEST 2: Registration does NOT report fake success when email delivery is disabled/fails
     */
    public function test_registration_does_not_falsely_claim_email_sent_when_delivery_disabled(): void
    {
        // Disable email system
        SmtpSetting::first()->update(['is_enabled' => false]);
        Cache::forget(SmtpConfigService::CACHE_KEY);

        $response = $this->postJson('/api/v2/auth/register', [
            'first_name' => 'Mizan',
            'last_name' => 'Rahman',
            'username' => 'mizan_test_user',
            'email' => 'mizan_disabled@bondhoo.com',
            'password' => 'Pass@12345678',
            'password_confirmation' => 'Pass@12345678',
            'birth_date' => '1995-05-05',
            'gender' => 'male',
            'country' => 'BD',
            'terms' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'email_sent' => false,
            ]);

        $this->assertStringContainsString('ভেরিফিকেশন ইমেইল পাঠানো সম্ভব হয়নি', $response->json('message'));
        $this->assertFalse($response->json('data.email_delivery.sent'));
    }

    /**
     * TEST 3: Resend verification code rate limiting and secure single-use invalidation
     */
    public function test_resend_verification_enforces_cooldown_and_invalidates_older_tokens(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'resend_target@bondhoo.com',
            'status' => 'pending',
        ]);

        $oldToken = Str::random(64);
        EmailVerification::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => Hash::make('111111'),
            'token' => $oldToken,
            'expires_at' => now()->addMinutes(60),
        ]);

        // First resend attempt
        $response1 = $this->postJson('/api/v2/auth/resend-email', [
            'email' => $user->email,
        ]);

        $response1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'sent' => true,
                ],
            ]);

        // Old token must have been expired
        $oldRecord = EmailVerification::where('token', $oldToken)->first();
        $this->assertTrue($oldRecord->expires_at->lte(now()));

        // Immediate second attempt within 60s cooldown must fail with 422
        $response2 = $this->postJson('/api/v2/auth/resend-email', [
            'email' => $user->email,
        ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['otp']);
    }

    /**
     * TEST 4: Resend verification prevents account enumeration for unknown emails
     */
    public function test_resend_verification_prevents_account_enumeration(): void
    {
        $response = $this->postJson('/api/v2/auth/resend-email', [
            'email' => 'completely_unknown_user_123456@bondhoo.com',
        ]);

        // Returns generic 200 without exposing user non-existence
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * TEST 5: Email verification via OTP activates user and marks tokens verified
     */
    public function test_verify_email_via_otp_activates_account_and_sends_welcome_email(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'otp_verify_user@bondhoo.com',
            'status' => 'pending',
        ]);

        $otpService = app(OtpService::class);
        $otp = $otpService->generateOtp($user->email, 'verify_email', $user);

        EmailVerification::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => $otp['otp_model']->code_hash,
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes(60),
        ]);

        $response = $this->postJson('/api/v2/auth/verify-email', [
            'identifier' => $user->email,
            'otp' => $otp['plain_otp'],
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertEquals('active', $user->status);

        // Welcome mail must be dispatched
        Mail::assertQueued(WelcomeMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    /**
     * TEST 6: Forgot password sends PasswordResetMail through EmailService and resets safely
     */
    public function test_forgot_password_sends_email_and_reset_sends_password_changed_notification(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'forgot_user@bondhoo.com',
            'password' => Hash::make('OldPassword123!@#'),
        ]);

        $response = $this->postJson('/api/v2/auth/forgot-password', [
            'identifier' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        Mail::assertSent(PasswordResetMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && ! empty($mail->otp);
        });

        $resetToken = $response->json('reset_token');

        // Complete password reset
        $resetResponse = $this->postJson('/api/v2/auth/reset-password', [
            'identifier' => $user->email,
            'token' => $resetToken,
            'password' => 'NewSecurePassword123!@#',
            'password_confirmation' => 'NewSecurePassword123!@#',
        ]);

        $resetResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertTrue(Hash::check('NewSecurePassword123!@#', $user->fresh()->password));

        // Password changed notification email dispatched
        Mail::assertSent(PasswordChangedMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    /**
     * TEST 7: Security emails bypass ordinary notification opt-outs
     */
    public function test_security_critical_emails_cannot_be_disabled_by_preferences(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'optout_user@bondhoo.com']);
        $user->notificationSettings()->updateOrCreate(
            ['user_id' => $user->id],
            ['email_notifications' => false]
        );

        $emailService = app(EmailService::class);

        // Security email MUST be sent even if master email_notifications is false
        $sent = $emailService->send(
            to: $user->email,
            mailable: new VerifyEmailMail($user, '123456', url('/test')),
            emailType: 'verify_email',
            user: $user,
            forceSync: true
        );

        $this->assertTrue($sent);
        Mail::assertSent(VerifyEmailMail::class);
    }
}
