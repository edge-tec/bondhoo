<?php

namespace Tests\Feature;

use App\Models\RecoveryCode;
use App\Models\User;
use App\Services\TwoFactorService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Step11TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Compute current TOTP code for testing.
     */
    protected function computeTotp(TwoFactorService $service, string $secret, int $offset = 0): string
    {
        $timeSlice = (int) floor(time() / 30) + $offset;
        $ref = new \ReflectionClass($service);
        $calcMethod = $ref->getMethod('calculateTotp');
        $calcMethod->setAccessible(true);

        return $calcMethod->invoke($service, $secret, $timeSlice);
    }

    public function test_enable_two_factor_authenticator_stores_in_database_and_activates(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
            'two_factor_enabled' => false,
        ]);

        /** @var TwoFactorService $twoFactorService */
        $twoFactorService = app(TwoFactorService::class);

        // 1. Setup 2FA
        $setup = $twoFactorService->setupTotp($user);
        $this->assertNotEmpty($setup['secret']);
        $this->assertNotEmpty($setup['qr_uri']);
        $this->assertCount(8, $setup['recovery_codes']);

        // Verify database records created
        $this->assertDatabaseHas('user_two_factor', [
            'user_id' => $user->id,
            'is_enabled' => false,
        ]);
        $this->assertEquals(8, RecoveryCode::where('user_id', $user->id)->count());

        // 2. Enable with valid TOTP code
        $validCode = $this->computeTotp($twoFactorService, $setup['secret']);
        $enabled = $twoFactorService->enable($user, $validCode, 'app');

        $this->assertTrue($enabled);

        // Verify user_two_factor is enabled
        $this->assertDatabaseHas('user_two_factor', [
            'user_id' => $user->id,
            'is_enabled' => true,
        ]);

        $user->refresh();
        $this->assertTrue($user->two_factor_enabled);
        $this->assertEquals('app', $user->two_factor_type);
        $this->assertNotNull($user->two_factor_confirmed_at);
    }

    public function test_disable_two_factor_with_password_clears_records(): void
    {
        $password = 'SecretPass@2026';
        $user = User::factory()->create([
            'password' => Hash::make($password),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        /** @var TwoFactorService $twoFactorService */
        $twoFactorService = app(TwoFactorService::class);
        $setup = $twoFactorService->setupTotp($user);
        $code = $this->computeTotp($twoFactorService, $setup['secret']);
        $twoFactorService->enable($user, $code, 'app');

        $this->assertTrue($user->fresh()->two_factor_enabled);
        $this->assertEquals(8, RecoveryCode::where('user_id', $user->id)->count());

        // Disable with password
        $disabled = $twoFactorService->disable($user, $password);
        $this->assertTrue($disabled);

        // Verify user_two_factor is disabled
        $this->assertDatabaseHas('user_two_factor', [
            'user_id' => $user->id,
            'is_enabled' => false,
        ]);

        // Verify recovery codes are deleted
        $this->assertEquals(0, RecoveryCode::where('user_id', $user->id)->count());

        $this->assertFalse($user->fresh()->two_factor_enabled);
    }

    public function test_recovery_code_can_solve_challenge_and_cannot_be_reused(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        /** @var TwoFactorService $twoFactorService */
        $twoFactorService = app(TwoFactorService::class);
        $setup = $twoFactorService->setupTotp($user);
        $code = $this->computeTotp($twoFactorService, $setup['secret']);
        $twoFactorService->enable($user, $code, 'app');

        $recoveryCode = $setup['recovery_codes'][0];

        // 1. Solve challenge with recovery code
        $solved = $twoFactorService->verifyChallenge($user, $recoveryCode);
        $this->assertTrue($solved);

        // Verify recovery code marked as used in database
        $usedCount = RecoveryCode::where('user_id', $user->id)->whereNotNull('used_at')->count();
        $this->assertEquals(1, $usedCount);

        // 2. Attempt reusing the same recovery code should fail
        $this->expectException(ValidationException::class);
        $twoFactorService->verifyChallenge($user, $recoveryCode);
    }

    public function test_otp_validation_accepts_valid_totp_and_rejects_invalid(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        /** @var TwoFactorService $twoFactorService */
        $twoFactorService = app(TwoFactorService::class);
        $setup = $twoFactorService->setupTotp($user);
        $code = $this->computeTotp($twoFactorService, $setup['secret']);
        $twoFactorService->enable($user, $code, 'app');

        // 1. Valid TOTP passes
        $this->assertTrue($twoFactorService->verifyChallenge($user, $code));

        // 2. Invalid TOTP throws validation exception
        $this->expectException(ValidationException::class);
        $twoFactorService->verifyChallenge($user, '999999');
    }

    public function test_google_and_microsoft_authenticator_uri_format(): void
    {
        $user = User::factory()->create([
            'email' => 'authenticator@jugajug.com',
            'status' => 'active',
        ]);

        /** @var TwoFactorService $twoFactorService */
        $twoFactorService = app(TwoFactorService::class);
        $setup = $twoFactorService->setupTotp($user);

        $uri = $setup['qr_uri'];
        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret='.$setup['secret'], $uri);
        $this->assertStringContainsString('issuer=', $uri);
        $this->assertStringContainsString('digits=6', $uri);
        $this->assertStringContainsString('period=30', $uri);
        $this->assertNotEmpty($setup['qr_code_url']);
    }

    public function test_web_two_factor_settings_page_and_flows(): void
    {
        $password = 'SecretPass@2026';
        $user = User::factory()->create([
            'password' => Hash::make($password),
            'status' => 'active',
            'email_verified_at' => now(),
            'two_factor_enabled' => false,
        ]);

        $this->actingAs($user, 'web');

        // 1. Visit 2FA settings page
        $pageResponse = $this->get('/settings/two-factor');
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('টু-ফ্যাক্টর অথেনটিকেশন');
        $pageResponse->assertSee('নিষ্ক্রিয় (Disabled)');

        // 2. Initiate setup
        $setupResponse = $this->post('/settings/two-factor/setup');
        $setupResponse->assertRedirect();
        $setupResponse->assertSessionHas('setupData');

        $setupData = session('setupData');
        $this->assertNotEmpty($setupData['secret']);

        // 3. Enable 2FA via web route
        /** @var TwoFactorService $twoFactorService */
        $twoFactorService = app(TwoFactorService::class);
        $validCode = $this->computeTotp($twoFactorService, $setupData['secret']);

        $enableResponse = $this->post('/settings/two-factor/enable', [
            'code' => $validCode,
        ]);
        $enableResponse->assertRedirect(route('settings.two-factor'));
        $enableResponse->assertSessionHas('success');

        $this->assertTrue($user->fresh()->two_factor_enabled);

        // 4. Disable 2FA via web route
        $disableResponse = $this->post('/settings/two-factor/disable', [
            'password' => $password,
        ]);
        $disableResponse->assertRedirect(route('settings.two-factor'));
        $disableResponse->assertSessionHas('success');

        $this->assertFalse($user->fresh()->two_factor_enabled);
    }
}
