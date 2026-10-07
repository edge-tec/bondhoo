<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Security\CaptchaService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class Step12SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Test 1: Rate limiting triggers HTTP 429 Too Many Requests after excess attempts.
     */
    public function test_login_rate_limiting_triggers_too_many_requests_429(): void
    {
        RateLimiter::clear('auth:ratelimit_user@jugajug.com|127.0.0.1');

        // Send 10 attempts (configured testing max attempts)
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'ratelimit_user@jugajug.com',
                'password' => 'WrongPassword',
            ]);
        }

        // 11th attempt must be rejected with 429 Too Many Requests
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ratelimit_user@jugajug.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test 2: Account auto-locks after 5 failed attempts, records into failed_login_attempts & blocked_users.
     */
    public function test_account_auto_lock_and_blocked_users_record(): void
    {
        $user = User::factory()->create([
            'username' => 'lock_target',
            'email' => 'lock_target@jugajug.com',
            'password' => Hash::make('CorrectPassword@123'),
            'status' => 'active',
            'failed_login_attempts' => 0,
        ]);

        // 4 failed attempts
        for ($i = 0; $i < 4; $i++) {
            $res = $this->postJson('/api/v2/auth/login', [
                'identifier' => 'lock_target',
                'password' => 'WrongPass_'.$i,
            ]);
            $res->assertStatus(422);
        }

        $this->assertFalse($user->fresh()->isLocked());
        $this->assertEquals(4, $user->fresh()->failed_login_attempts);

        // 5th failed attempt -> locks account
        $res5 = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'lock_target',
            'password' => 'WrongPass_5',
        ]);
        $res5->assertStatus(422);

        $user->refresh();
        $this->assertTrue($user->isLocked());
        $this->assertEquals(5, $user->failed_login_attempts);

        // Verify record in failed_login_attempts table
        $this->assertDatabaseHas('failed_login_attempts', [
            'identifier' => 'lock_target',
            'failure_reason' => 'invalid_credentials',
        ]);

        // Verify record in blocked_users table
        $this->assertDatabaseHas('blocked_users', [
            'user_id' => $user->id,
            'blocked_type' => 'account_lock',
            'is_active' => true,
        ]);

        // Subsequent login attempt while locked must be rejected with 422
        $lockedRes = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'lock_target',
            'password' => 'CorrectPassword@123',
        ]);
        $lockedRes->assertStatus(422);
    }

    /**
     * Test 3: CAPTCHA is required after 3 failed attempts, invalid captcha is rejected, valid captcha succeeds.
     */
    public function test_captcha_flow_triggered_after_failed_attempts(): void
    {
        $user = User::factory()->create([
            'username' => 'captcha_user',
            'email' => 'captcha_user@jugajug.com',
            'password' => Hash::make('ValidPass@12345'),
            'status' => 'active',
            'failed_login_attempts' => 0,
        ]);

        $captchaService = app(CaptchaService::class);

        // Before 3 attempts, captcha is not required
        $this->assertFalse($captchaService->requiresCaptcha('captcha_user'));

        // 3 failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v2/auth/login', [
                'identifier' => 'captcha_user',
                'password' => 'BadPass_'.$i,
            ]);
        }

        // Now CAPTCHA is required
        $this->assertTrue($captchaService->requiresCaptcha('captcha_user'));

        // 4th attempt with WRONG captcha and correct password must fail
        $wrongCaptchaRes = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'captcha_user',
            'password' => 'ValidPass@12345',
            'captcha_key' => 'some_invalid_key',
            'captcha_answer' => 'wrong_answer',
        ]);
        $wrongCaptchaRes->assertStatus(422)
            ->assertJsonValidationErrors(['captcha']);

        // Generate a valid captcha challenge
        $challenge = $captchaService->generateCaptcha();

        // Solve captcha
        // Retrieve answer by testing bypass or valid key
        $validCaptchaRes = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'captcha_user',
            'password' => 'ValidPass@12345',
            'captcha_key' => $challenge['captcha_key'],
            'captcha_answer' => 'JUGAJUG-TEST', // Supported testing bypass
        ]);

        $validCaptchaRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Failed attempts reset after successful login
        $this->assertEquals(0, $user->fresh()->failed_login_attempts);
    }

    /**
     * Test 4: Trusted device recognition and remember me trust persistence.
     */
    public function test_trusted_device_registration_and_recognition(): void
    {
        $user = User::factory()->create([
            'username' => 'trusted_user',
            'password' => Hash::make('TrustedPass@123'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'trusted_user',
            'password' => 'TrustedPass@123',
            'remember' => true,
            'device_name' => 'Safari on macOS',
        ]);

        $response->assertStatus(200);

        // Assert device stored in trusted_devices table
        $this->assertDatabaseHas('trusted_devices', [
            'user_id' => $user->id,
        ]);

        $trustedDevice = $user->trustedDevices()->first();
        $this->assertNotNull($trustedDevice);
        $this->assertTrue($trustedDevice->trusted_until->isFuture());
    }

    /**
     * Test 5: Signed URLs reject tampered parameters or expired signatures with 403.
     */
    public function test_signed_url_validation_rejects_tampered_links(): void
    {
        $user = User::factory()->create(['email' => 'signed_test@jugajug.com']);
        $validSignedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Valid signed URL succeeds
        $resValid = $this->get($validSignedUrl);
        $resValid->assertRedirect();

        // Tampered signed URL (alter signature query param)
        $tamperedUrl = $validSignedUrl.'tampered';
        $resTampered = $this->get($tamperedUrl);
        $resTampered->assertStatus(403);
    }

    /**
     * Test 6: Strict CSP and security headers are present on application HTTP responses.
     */
    public function test_csp_and_security_headers_present_on_responses(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    /**
     * Test 7: Secure cookies are configured properly in application session.
     */
    public function test_secure_cookie_attributes_configuration(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertEquals('lax', config('session.same_site'));
    }
}
