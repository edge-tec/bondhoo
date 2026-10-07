<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionAuthTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_setup_and_enable_totp(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // 1. Setup TOTP
        $setupRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v2/auth/2fa/setup');

        $setupRes->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'secret',
                    'qr_uri',
                    'recovery_codes',
                ],
            ]);

        $secret = $setupRes->json('data.secret');
        $this->assertNotEmpty($secret);
        $this->assertCount(8, $setupRes->json('data.recovery_codes'));

        // 2. Generate valid TOTP code
        $twoFactorService = app(TwoFactorService::class);
        $timeSlice = (int) floor(time() / 30);
        $validCode = (function () use ($twoFactorService, $secret, $timeSlice) {
            $ref = new \ReflectionClass($twoFactorService);
            $method = $ref->getMethod('calculateTotp');
            $method->setAccessible(true);

            return $method->invoke($twoFactorService, $secret, $timeSlice);
        })();

        // 3. Enable 2FA
        $enableRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v2/auth/enable-2fa', [
                'code' => $validCode,
                'type' => 'app',
            ]);

        $enableRes->assertStatus(200);
        $this->assertTrue($user->fresh()->two_factor_enabled);
    }

    public function test_login_with_2fa_returns_challenge_and_verifies_successfully(): void
    {
        $twoFactorService = app(TwoFactorService::class);
        $user = User::factory()->create([
            'username' => 'twofa_user',
            'password' => Hash::make('Password@12345'),
            'two_factor_enabled' => true,
            'two_factor_type' => 'app',
            'status' => 'active',
        ]);

        $setup = $twoFactorService->setupTotp($user);
        $secret = $setup['secret'];

        // 1. Attempt login
        $loginRes = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'twofa_user',
            'password' => 'Password@12345',
        ]);

        $loginRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'requires_2fa' => true,
                ],
            ]);

        $challengeToken = $loginRes->json('data.challenge_token');
        $this->assertNotEmpty($challengeToken);

        // 2. Compute current TOTP code
        $timeSlice = (int) floor(time() / 30);
        $ref = new \ReflectionClass($twoFactorService);
        $calcMethod = $ref->getMethod('calculateTotp');
        $calcMethod->setAccessible(true);
        $code = $calcMethod->invoke($twoFactorService, $secret, $timeSlice);

        // 3. Complete challenge
        $challengeRes = $this->postJson('/api/v2/auth/2fa/challenge', [
            'challenge_token' => $challengeToken,
            'code' => $code,
        ]);

        $challengeRes->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['user', 'token'],
            ]);
    }

    public function test_2fa_challenge_can_be_solved_with_recovery_code(): void
    {
        $twoFactorService = app(TwoFactorService::class);
        $user = User::factory()->create([
            'username' => 'recovery_user',
            'password' => Hash::make('Password@12345'),
            'two_factor_enabled' => true,
            'two_factor_type' => 'app',
            'status' => 'active',
        ]);

        $setup = $twoFactorService->setupTotp($user);
        $recoveryCode = $setup['recovery_codes'][0];

        $loginRes = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'recovery_user',
            'password' => 'Password@12345',
        ]);

        $challengeToken = $loginRes->json('data.challenge_token');

        // Solve with recovery code
        $res = $this->postJson('/api/v2/auth/2fa/challenge', [
            'challenge_token' => $challengeToken,
            'code' => $recoveryCode,
        ]);

        $res->assertStatus(200);

        // Consumed recovery code cannot be reused
        $user->refresh();
        $storedCodes = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true);
        $this->assertCount(7, $storedCodes); // 8 - 1 = 7
    }
}
