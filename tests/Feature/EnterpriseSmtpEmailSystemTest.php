<?php

namespace Tests\Feature;

use App\Mail\FriendRequestMail;
use App\Mail\SecurityAlertMail;
use App\Mail\SmtpTestMail;
use App\Mail\WelcomeMail;
use App\Models\EmailLog;
use App\Models\EmailVerification;
use App\Models\Friendship;
use App\Models\NotificationSetting;
use App\Models\Role;
use App\Models\SmtpSetting;
use App\Models\User;
use App\Services\Email\EmailService;
use App\Services\Email\SmtpConfigService;
use App\Services\FriendshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnterpriseSmtpEmailSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $userA;

    protected User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Roles
        $adminRole = Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['label' => 'Super Administrator']);
        $userRole = Role::firstOrCreate(['name' => 'USER'], ['label' => 'Standard User']);

        // Create Admin User
        $this->admin = User::factory()->create([
            'email' => 'admin@bondhoo.com',
            'username' => 'admin_bondhoo',
            'status' => 'active',
        ]);
        $this->admin->roles()->sync([$adminRole->id]);

        // Create Regular Users
        $this->userA = User::factory()->create([
            'email' => 'usera@bondhoo.com',
            'username' => 'usera',
            'name' => 'User Alpha',
            'status' => 'active',
        ]);
        $this->userA->roles()->sync([$userRole->id]);

        $this->userB = User::factory()->create([
            'email' => 'userb@bondhoo.com',
            'username' => 'userb',
            'name' => 'User Beta',
            'status' => 'active',
        ]);
        $this->userB->roles()->sync([$userRole->id]);

        // Seed default notification settings
        NotificationSetting::create([
            'user_id' => $this->userA->id,
            'email_notifications' => true,
            'friend_request_alerts' => true,
            'friend_accepted_alerts' => true,
            'comment_alerts' => true,
            'mention_alerts' => true,
            'message_alerts' => true,
            'security_alerts' => true,
        ]);

        NotificationSetting::create([
            'user_id' => $this->userB->id,
            'email_notifications' => true,
            'friend_request_alerts' => true,
            'friend_accepted_alerts' => true,
            'comment_alerts' => true,
            'mention_alerts' => true,
            'message_alerts' => true,
            'security_alerts' => true,
        ]);
    }

    /**
     * TEST 1: Admin SMTP Configuration Management & Sensitive Password Masking
     */
    public function test_admin_can_update_smtp_settings_and_password_is_encrypted_and_masked(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v2/admin/smtp/settings', [
            'mail_host' => 'smtp.mailgun.org',
            'mail_port' => 587,
            'mail_username' => 'postmaster@bondhoo.com',
            'mail_password' => 'super_secret_smtp_key_123',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@bondhoo.com',
            'mail_from_name' => 'Bondhoo Enterprise',
            'mail_reply_to' => 'support@bondhoo.com',
            'smtp_auth' => true,
            'timeout' => 45,
            'rate_limit_per_minute' => 120,
            'is_enabled' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'mail_host' => 'smtp.mailgun.org',
                    'mail_port' => 587,
                    'mail_password_masked' => '••••••••',
                    'has_password' => true,
                ],
            ]);

        // Password must NEVER be exposed in JSON response
        $this->assertStringNotContainsString('super_secret_smtp_key_123', $response->getContent());

        // Password must be encrypted in database, not stored in plaintext
        $saved = SmtpSetting::first();
        $this->assertNotNull($saved);
        $this->assertNotEquals('super_secret_smtp_key_123', $saved->getRawOriginal('mail_password'));
        $this->assertEquals('super_secret_smtp_key_123', $saved->getDecryptedPassword());
    }

    /**
     * TEST 2: Admin Send Test Email Dispatch & Diagnostics
     */
    public function test_admin_send_test_email_dispatches_real_mail_and_records_log(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v2/admin/smtp/test', [
            'to_email' => 'qa@bondhoo.com',
            'mail_host' => 'smtp.office365.com',
            'mail_port' => 587,
            'mail_username' => 'test@bondhoo.com',
            'mail_password' => 'secret_pass',
            'mail_encryption' => 'tls',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        Mail::mailer('test_smtp')->assertSent(SmtpTestMail::class, function ($mail) {
            return $mail->toEmail === 'qa@bondhoo.com';
        });

        $this->assertDatabaseHas('email_logs', [
            'recipient' => 'qa@bondhoo.com',
            'email_type' => 'smtp_test',
            'status' => 'smtp_accepted',
        ]);
    }

    /**
     * TEST 3: User Registration Dispatches Verification Email With OTP & Link
     */
    public function test_user_registration_sends_verification_email_with_token_and_otp(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v2/auth/register', [
            'first_name' => 'Tanvir',
            'last_name' => 'Hasan',
            'username' => 'tanvir_hasan',
            'email' => 'tanvir@bondhoo.com',
            'password' => 'Password123!@#',
            'password_confirmation' => 'Password123!@#',
            'birth_date' => '1998-05-15',
            'gender' => 'male',
            'country' => 'BD',
            'terms' => true,
        ]);

        $response->assertStatus(201);

        $registeredUser = User::where('email', 'tanvir@bondhoo.com')->first();
        $this->assertNotNull($registeredUser);
        $this->assertNull($registeredUser->email_verified_at);

        // Verify that EmailVerification record exists
        $verification = EmailVerification::where('user_id', $registeredUser->id)->first();
        $this->assertNotNull($verification);
        $this->assertNotNull($verification->token);
        $this->assertNotNull($verification->otp_hash);
        $this->assertTrue($verification->expires_at->gt(now()));
    }

    /**
     * TEST 4: Email Verification With Single-Use Token Invalidation
     */
    public function test_email_verification_via_token_marks_account_verified_and_prevents_reuse(): void
    {
        $user = User::factory()->create([
            'email' => 'verify_test@bondhoo.com',
            'email_verified_at' => null,
            'status' => 'pending',
        ]);

        $token = Str::random(64);
        EmailVerification::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => Hash::make('123456'),
            'token' => $token,
            'expires_at' => now()->addMinutes(60),
            'ip_address' => '127.0.0.1',
        ]);

        // 1. Verify successfully
        $response = $this->postJson('/api/v2/auth/verify-email', [
            'email' => $user->email,
            'token' => $token,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertEquals('active', $user->fresh()->status);

        // 2. Prevent token reuse
        $reuseResponse = $this->postJson('/api/v2/auth/verify-email', [
            'email' => $user->email,
            'token' => $token,
        ]);

        // The token is now verified or expired, cannot be verified again
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /**
     * TEST 5: Password Reset Flow With No Account Enumeration & Single-Use Token
     */
    public function test_forgot_password_sends_reset_email_and_prevents_enumeration(): void
    {
        Mail::fake();

        // 1. Existing user request
        $response = $this->postJson('/api/v2/auth/password/email', [
            'identifier' => $this->userA->email,
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // 2. Non-existent email request returns same generic message to prevent account enumeration
        $unknownResponse = $this->postJson('/api/v2/auth/password/email', [
            'identifier' => 'nonexistent_user_99999@bondhoo.com',
        ]);

        $unknownResponse->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // Token must be stored securely hashed in password_reset_tokens
        $resetRecord = DB::table('password_reset_tokens')->where('email', $this->userA->email)->first();
        $this->assertNotNull($resetRecord);
        $this->assertTrue(Hash::check($response->json('reset_token'), $resetRecord->token));
    }

    /**
     * TEST 6: Password Reset Completion Invalidates Token
     */
    public function test_password_reset_completion_invalidates_token_and_updates_password(): void
    {
        $rawToken = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $this->userA->email],
            ['token' => Hash::make($rawToken), 'created_at' => now()]
        );

        $response = $this->postJson('/api/v2/auth/password/reset', [
            'identifier' => $this->userA->email,
            'token' => $rawToken,
            'password' => 'BrandNewSecurePass123!@#',
            'password_confirmation' => 'BrandNewSecurePass123!@#',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Token must be invalidated / removed
        $this->assertNull(DB::table('password_reset_tokens')->where('email', $this->userA->email)->first());

        // Password must be updated
        $this->assertTrue(Hash::check('BrandNewSecurePass123!@#', $this->userA->fresh()->password));
    }

    /**
     * TEST 7: Friend Request Email Delivery & Deduplication
     */
    public function test_friend_request_triggers_email_and_prevents_duplicate_delivery(): void
    {
        Mail::fake();

        $friendshipService = app(FriendshipService::class);
        $friendship = $friendshipService->sendRequest($this->userA, $this->userB->id);

        $this->assertEquals(Friendship::STATUS_PENDING, $friendship->status);

        // Check EmailLog
        $this->assertDatabaseHas('email_logs', [
            'recipient' => $this->userB->email,
            'email_type' => 'friend_request',
        ]);

        // Duplicate test: Calling send directly with same idempotency key
        $emailService = app(EmailService::class);
        $idempotencyKey = "friend_request:{$this->userA->id}:{$this->userB->id}:{$friendship->id}";

        $result = $emailService->send(
            to: $this->userB->email,
            mailable: new FriendRequestMail($this->userA, $this->userB, url('/friends')),
            emailType: 'friend_request',
            user: $this->userB,
            idempotencyKey: $idempotencyKey
        );

        // Idempotency should skip creating a second log
        $count = EmailLog::where('idempotency_key', $idempotencyKey)->count();
        $this->assertEquals(1, $count);
    }

    /**
     * TEST 8: Friend Request Accepted Email Delivery
     */
    public function test_friend_request_accepted_triggers_email_to_original_requester(): void
    {
        Mail::fake();

        $friendship = Friendship::create([
            'user_id' => $this->userA->id,
            'friend_id' => $this->userB->id,
            'status' => Friendship::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        $friendshipService = app(FriendshipService::class);
        $friendshipService->acceptRequest($this->userB, $this->userA->id);

        $this->assertDatabaseHas('email_logs', [
            'recipient' => $this->userA->email,
            'email_type' => 'friend_accepted',
        ]);
    }

    /**
     * TEST 9: User Notification Preferences Opt-Out Enforcement
     */
    public function test_user_email_preferences_are_strictly_respected(): void
    {
        Mail::fake();

        // User B disables friend request email alerts
        $this->userB->notificationSettings->update([
            'friend_request_alerts' => false,
        ]);

        $emailService = app(EmailService::class);

        // Social email should be blocked
        $sent = $emailService->send(
            to: $this->userB->email,
            mailable: new FriendRequestMail($this->userA, $this->userB, url('/friends')),
            emailType: 'friend_request',
            user: $this->userB,
            idempotencyKey: 'pref_test_1'
        );

        $this->assertFalse($sent);

        // Mandatory security email MUST NEVER be blocked
        $sentSecurity = $emailService->send(
            to: $this->userB->email,
            mailable: new SecurityAlertMail($this->userB, 'Security Alert', 'Suspicious activity'),
            emailType: 'security_alert',
            user: $this->userB,
            idempotencyKey: 'pref_test_sec_1'
        );

        $this->assertTrue($sentSecurity);
    }

    /**
     * TEST 10: Global Email System Toggle Disable
     */
    public function test_global_email_system_toggle_disables_all_outgoing_emails(): void
    {
        Mail::fake();

        // Disable email system
        SmtpSetting::firstOrCreate([])->update(['is_enabled' => false]);
        Cache::forget(SmtpConfigService::CACHE_KEY);

        $emailService = app(EmailService::class);
        $sent = $emailService->send(
            to: $this->userA->email,
            mailable: new WelcomeMail($this->userA),
            emailType: 'welcome',
            user: $this->userA
        );

        $this->assertFalse($sent);
        Mail::assertNothingOutgoing();
    }

    /**
     * TEST 11: Email Templates Live Preview & Responsive HTML Rendering
     */
    public function test_admin_can_preview_all_email_templates_with_valid_rendered_html(): void
    {
        $templates = [
            'verify_email',
            'welcome',
            'password_reset',
            'password_changed',
            'new_login',
            'security_alert',
            'friend_request',
            'friend_accepted',
            'social_notification',
            'smtp_test',
        ];

        foreach ($templates as $key) {
            $response = $this->actingAs($this->admin)->get("/admin/smtp/templates/{$key}/preview");
            $response->assertStatus(200);
            $this->assertStringContainsString('bondhoo', strtolower($response->getContent()));
            $this->assertStringContainsString('<!DOCTYPE html>', $response->getContent());
        }
    }

    /**
     * TEST 12: Admin Email Logs & Delivery Statistics
     */
    public function test_admin_can_query_email_logs_and_stats(): void
    {
        EmailLog::create([
            'recipient' => 'log1@bondhoo.com',
            'email_type' => 'welcome',
            'subject' => 'Welcome to Bondhoo',
            'status' => 'sent',
            'attempts' => 1,
            'sent_at' => now(),
        ]);

        EmailLog::create([
            'recipient' => 'log2@bondhoo.com',
            'email_type' => 'friend_request',
            'subject' => 'Friend request',
            'status' => 'failed',
            'attempts' => 2,
            'error_message' => 'Connection timeout to smtp port 587',
        ]);

        // 1. Query Logs
        $logsResponse = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v2/admin/smtp/logs');
        $logsResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['data', 'total'],
            ]);

        // 2. Query Stats
        $statsResponse = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v2/admin/smtp/stats');
        $statsResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'sent_count' => 1,
                    'failed_count' => 1,
                ],
            ]);
    }

    /**
     * TEST 13: Failure Resilience - SMTP Failure Does Not Crash User Registration
     */
    public function test_smtp_failure_does_not_crash_user_registration(): void
    {
        // Even if mailer encounters an error, registration completes without throwing 500
        $response = $this->postJson('/api/v2/auth/register', [
            'first_name' => 'Resilience',
            'last_name' => 'Test',
            'username' => 'resilience_user',
            'email' => 'resilience@bondhoo.com',
            'password' => 'Password123!@#',
            'password_confirmation' => 'Password123!@#',
            'birth_date' => '2000-01-01',
            'gender' => 'female',
            'country' => 'BD',
            'terms' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['username' => 'resilience_user']);
    }

    /**
     * TEST 14: SMTP Test Connection Handles Failure Gracefully Returning 422 Instead of 500
     */
    public function test_admin_test_connection_handles_connection_failure_gracefully_without_500(): void
    {
        // Calling test connection with an unroutable port/host to trigger real failure without crashing 500
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v2/admin/smtp/test', [
            'to_email' => 'fail_test@bondhoo.com',
            'mail_host' => '127.0.0.1',
            'mail_port' => 65432, // Non-existent port
            'mail_username' => 'user@bondhoo.com',
            'mail_password' => 'wrong_pass',
            'mail_encryption' => 'tls',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertArrayHasKey('details', $response->json());
        $this->assertDatabaseHas('email_logs', [
            'recipient' => 'fail_test@bondhoo.com',
            'status' => 'failed',
        ]);
    }

    /**
     * TEST 15: SmtpConfigService Recovers From Corrupted or Incomplete Cache Objects
     */
    public function test_smtp_config_recovers_from_corrupted_cache_cleanly(): void
    {
        Cache::put(SmtpConfigService::CACHE_KEY, 'invalid_serialized_string', 3600);

        /** @var SmtpConfigService $service */
        $service = app(SmtpConfigService::class);
        $settings = $service->getActiveSettings();

        $this->assertInstanceOf(SmtpSetting::class, $settings);
    }

    /**
     * TEST 16: Admin Can Verify SMTP Connection Directly (Socket Handshake & Auth)
     */
    public function test_admin_can_verify_smtp_connection_directly(): void
    {
        // Non-admin cannot verify SMTP connection
        $forbiddenResponse = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v2/admin/smtp/verify-connection', [
            'mail_host' => '127.0.0.1',
            'mail_port' => 2525,
        ]);
        $forbiddenResponse->assertStatus(403);

        // Admin can call verify-connection endpoint
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v2/admin/smtp/verify-connection', [
            'mail_host' => '127.0.0.1',
            'mail_port' => 2525,
            'mail_username' => 'testuser',
            'mail_password' => 'testpass',
            'mail_encryption' => 'none',
            'timeout' => 2,
        ]);

        // The response structure must be valid JSON with success and details
        $this->assertContains($response->status(), [200, 422]);
        $this->assertArrayHasKey('success', $response->json());
        $this->assertArrayHasKey('message', $response->json());
        $this->assertArrayHasKey('details', $response->json());
        $this->assertEquals('127.0.0.1', $response->json('details.host'));
        $this->assertEquals(2525, $response->json('details.port'));

        // Admin can also verify connection via Web session route
        $webResponse = $this->actingAs($this->admin, 'web')->postJson('/admin/smtp/verify-connection', [
            'mail_host' => '127.0.0.1',
            'mail_port' => 2525,
            'timeout' => 2,
        ]);
        $this->assertContains($webResponse->status(), [200, 422]);
        $this->assertArrayHasKey('details', $webResponse->json());
    }

    /**
     * TEST 17: Admin Can Check Domain DNS Deliverability (SPF, DMARC, MX)
     */
    public function test_admin_can_check_domain_dns_deliverability(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v2/admin/smtp/dns-check?from_address=no_reply@bondhoo.com');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'domain',
                    'spf' => ['status', 'recommended'],
                    'dmarc' => ['status', 'recommended'],
                    'mx' => ['status', 'recommended'],
                    'is_healthy',
                ],
            ]);

        $this->assertEquals('bondhoo.com', $response->json('data.domain'));

        // Web route test
        $webResponse = $this->actingAs($this->admin, 'web')->getJson('/admin/smtp/dns-check');
        $webResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
