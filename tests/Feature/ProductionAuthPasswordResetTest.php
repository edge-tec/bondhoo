<?php

namespace Tests\Feature;

use App\Models\PasswordHistory;
use App\Models\User;
use App\Services\OtpService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionAuthPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_forgot_password_initiates_otp_dispatch(): void
    {
        $user = User::factory()->create([
            'email' => 'recover@example.com',
            'phone' => '+8801700998877',
        ]);

        $response = $this->postJson('/api/v2/auth/forgot-password', [
            'identifier' => 'recover@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('otp_codes', [
            'identifier' => 'recover@example.com',
            'purpose' => 'password_reset',
        ]);
    }

    public function test_user_can_reset_password_with_valid_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'reset_user@example.com',
            'password' => Hash::make('OldPassword@123'),
        ]);

        $otpService = app(OtpService::class);
        $otp = $otpService->generateOtp($user->email, 'password_reset', $user);

        $response = $this->postJson('/api/v2/auth/reset-password', [
            'identifier' => $user->email,
            'otp' => $otp['plain_otp'],
            'password' => 'BrandNewPassword@2026',
            'password_confirmation' => 'BrandNewPassword@2026',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertTrue(Hash::check('BrandNewPassword@2026', $user->fresh()->password));
    }

    public function test_password_reset_blocks_reusing_recent_passwords(): void
    {
        $user = User::factory()->create([
            'email' => 'reuse_block@example.com',
            'password' => Hash::make('OldActivePassword@123'),
        ]);

        // Record a historical password
        PasswordHistory::create([
            'user_id' => $user->id,
            'password_hash' => Hash::make('PreviouslyUsedPassword@2025'),
            'created_at' => now()->subDays(5),
        ]);

        $otpService = app(OtpService::class);
        $otp = $otpService->generateOtp($user->email, 'password_reset', $user);

        // Attempting to reuse PreviouslyUsedPassword@2025 must be blocked
        $response = $this->postJson('/api/v2/auth/reset-password', [
            'identifier' => $user->email,
            'otp' => $otp['plain_otp'],
            'password' => 'PreviouslyUsedPassword@2025',
            'password_confirmation' => 'PreviouslyUsedPassword@2025',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_reset_revokes_all_active_tokens(): void
    {
        $user = User::factory()->create(['email' => 'session_kill@example.com']);
        $token1 = $user->createToken('device1')->plainTextToken;
        $token2 = $user->createToken('device2')->plainTextToken;

        $this->assertCount(2, $user->tokens);

        $otpService = app(OtpService::class);
        $otp = $otpService->generateOtp($user->email, 'password_reset', $user);

        $this->postJson('/api/v2/auth/reset-password', [
            'identifier' => $user->email,
            'otp' => $otp['plain_otp'],
            'password' => 'FreshSecuredPass@2026!',
            'password_confirmation' => 'FreshSecuredPass@2026!',
        ]);

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
