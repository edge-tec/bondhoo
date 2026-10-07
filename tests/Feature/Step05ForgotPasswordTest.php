<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class Step05ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_and_reset_page_render_successfully(): void
    {
        $forgotRes = $this->get('/forgot-password');
        $forgotRes->assertStatus(200)
            ->assertSee('আপনার অ্যাকাউন্ট খুঁজুন', false)
            ->assertSee('id="recoverIdentifier"', false);

        $resetRes = $this->get('/reset-password?token=testtoken123&email=test@example.com');
        $resetRes->assertStatus(200)
            ->assertSee('নতুন পাসওয়ার্ড নির্ধারণ করুন', false)
            ->assertSee('id="newPassword"', false)
            ->assertSee('id="resetToken"', false);
    }

    public function test_forgot_password_generates_secure_token_and_dispatches_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'name' => 'Tanvir Ahmed',
            'email' => 'tanvir@jugajug.com',
            'status' => 'active',
        ]);

        $res = $this->postJson('/api/v2/auth/forgot-password', [
            'identifier' => 'tanvir@jugajug.com',
        ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'success',
                    'channel' => 'email',
                ],
            ]);

        // 1. Verify token in password_reset_tokens
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'tanvir@jugajug.com',
        ]);

        // 2. Verify otp_logs
        $this->assertDatabaseHas('otp_logs', [
            'identifier' => 'tanvir@jugajug.com',
            'type' => 'password_reset',
        ]);

        // 3. Verify notification queued/sent
        Notification::assertSentTo($user, PasswordResetOtpNotification::class);
    }

    public function test_user_can_reset_password_with_valid_secure_token(): void
    {
        $user = User::factory()->create([
            'email' => 'resetuser@jugajug.com',
            'password' => Hash::make('OldPass@12345'),
            'status' => 'active',
        ]);

        // Create an active token for this user
        $rawToken = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => 'resetuser@jugajug.com'],
            [
                'token' => Hash::make($rawToken),
                'created_at' => now(),
            ]
        );

        // Also create a personal access token to verify it gets revoked
        $user->createToken('active-device');
        $this->assertCount(1, $user->tokens);

        // Reset password via API using the secure token
        $res = $this->postJson('/api/v2/auth/reset-password', [
            'identifier' => 'resetuser@jugajug.com',
            'token' => $rawToken,
            'password' => 'NewSecurePass@2026',
            'password_confirmation' => 'NewSecurePass@2026',
        ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'আপনার পাসওয়ার্ড সফলভাবে রিসেট হয়েছে। নতুন পাসওয়ার্ড দিয়ে লগইন করুন।',
            ]);

        // Password updated
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePass@2026', $user->password));

        // One-time use: token is consumed and deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'resetuser@jugajug.com',
        ]);

        // Existing sessions/tokens revoked
        $this->assertCount(0, $user->fresh()->tokens);

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.password_reset_success',
        ]);
    }

    public function test_user_cannot_reset_password_with_expired_token(): void
    {
        $user = User::factory()->create([
            'email' => 'expiredtoken@jugajug.com',
            'status' => 'active',
        ]);

        $rawToken = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => 'expiredtoken@jugajug.com'],
            [
                'token' => Hash::make($rawToken),
                'created_at' => now()->subMinutes(65), // Expired (> 60 minutes)
            ]
        );

        $res = $this->postJson('/api/v2/auth/reset-password', [
            'identifier' => 'expiredtoken@jugajug.com',
            'token' => $rawToken,
            'password' => 'NewSecurePass@2026',
            'password_confirmation' => 'NewSecurePass@2026',
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['token'])
            ->assertJsonFragment([
                'token' => ['পাসওয়ার্ড রিসেট টোকেনের মেয়াদ শেষ হয়ে গেছে। পুনরায় অনুরোধ করুন।'],
            ]);
    }

    public function test_user_cannot_reset_password_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'invalidtoken@jugajug.com',
            'status' => 'active',
        ]);

        $realToken = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => 'invalidtoken@jugajug.com'],
            [
                'token' => Hash::make($realToken),
                'created_at' => now(),
            ]
        );

        $res = $this->postJson('/api/v2/auth/reset-password', [
            'identifier' => 'invalidtoken@jugajug.com',
            'token' => 'completely-wrong-token-value',
            'password' => 'NewSecurePass@2026',
            'password_confirmation' => 'NewSecurePass@2026',
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['token'])
            ->assertJsonFragment([
                'token' => ['অবৈধ বা মেয়াদোত্তীর্ণ পাসওয়ার্ড রিসেট টোকেন।'],
            ]);
    }

    public function test_api_v1_forgot_password_and_token_reset_flow(): void
    {
        $user = User::factory()->create([
            'email' => 'v1user@jugajug.com',
            'status' => 'active',
        ]);

        // Request reset
        $reqRes = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'v1user@jugajug.com',
        ]);

        $reqRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $token = $reqRes->json('data.reset_token');
        $this->assertNotEmpty($token);

        // Reset password
        $resetRes = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'v1user@jugajug.com',
            'token' => $token,
            'password' => 'BrandNewPass@2026',
            'password_confirmation' => 'BrandNewPass@2026',
        ]);

        $resetRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('BrandNewPass@2026', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'v1user@jugajug.com']);
    }
}
