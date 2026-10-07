<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Step02UserLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_using_email_and_updates_database_records(): void
    {
        $user = User::factory()->create([
            'name' => 'Masud Rana',
            'email' => 'rana@jugajug.com',
            'username' => 'masud_rana',
            'phone' => '+8801711223344',
            'password' => Hash::make('StrongPass@2026'),
            'status' => 'active',
            'email_verified_at' => now(),
            'failed_login_attempts' => 2,
        ]);

        $res = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'rana@jugajug.com',
            'password' => 'StrongPass@2026',
            'remember' => true,
        ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'লগইন সফল হয়েছে।',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'token',
                    'token_type',
                    'email_verified',
                ],
            ]);

        // Verify database updates
        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertEquals(0, $user->failed_login_attempts);

        // Verify insert into login_histories
        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'status' => 'success',
        ]);

        // Verify insert into audit_logs
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login_success',
            'entity_type' => User::class,
            'entity_id' => $user->id,
        ]);
    }

    public function test_user_can_login_using_username(): void
    {
        $user = User::factory()->create([
            'username' => 'bangla_hero',
            'email' => 'hero@jugajug.com',
            'password' => Hash::make('HeroPass@2026'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $res = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'bangla_hero',
            'password' => 'HeroPass@2026',
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'bangla_hero');

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'status' => 'success',
        ]);
    }

    public function test_user_can_login_using_phone_number(): void
    {
        $user = User::factory()->create([
            'phone' => '+8801812345678',
            'username' => 'phone_user',
            'password' => Hash::make('PhonePass@2026'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $res = $this->postJson('/api/v2/auth/login', [
            'identifier' => '+8801812345678',
            'password' => 'PhonePass@2026',
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.phone', '+8801812345678');
    }

    public function test_web_session_login_with_all_identifiers(): void
    {
        $user = User::factory()->create([
            'email' => 'websession@jugajug.com',
            'username' => 'websession_user',
            'phone' => '+8801999888777',
            'password' => Hash::make('WebSessionPass@123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Login via username on web
        $res = $this->post('/login', [
            'identifier' => 'websession_user',
            'password' => 'WebSessionPass@123',
            'remember' => true,
        ]);

        $res->assertRedirect('/feed');
        $this->assertAuthenticatedAs($user, 'web');

        // Check login_histories and audit_logs
        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'USER_LOGIN_SUCCESS',
        ]);
    }

    public function test_login_validation_required_fields(): void
    {
        $res = $this->postJson('/api/v2/auth/login', []);
        $res->assertStatus(422)
            ->assertJsonValidationErrors(['identifier', 'password']);
    }

    public function test_invalid_login_credentials_handling_and_failed_attempts_counter(): void
    {
        $user = User::factory()->create([
            'username' => 'valid_user',
            'password' => Hash::make('CorrectPassword@123'),
            'failed_login_attempts' => 0,
        ]);

        $res = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'valid_user',
            'password' => 'WrongPassword',
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);

        $user->refresh();
        $this->assertEquals(1, $user->failed_login_attempts);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'status' => 'failed',
        ]);
    }

    public function test_suspended_account_handling(): void
    {
        User::factory()->create([
            'username' => 'suspended_guy',
            'password' => Hash::make('Pass@12345'),
            'status' => 'suspended',
        ]);

        $res = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'suspended_guy',
            'password' => 'Pass@12345',
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    }

    public function test_inactive_account_handling(): void
    {
        User::factory()->create([
            'username' => 'inactive_guy',
            'password' => Hash::make('Pass@12345'),
            'status' => 'inactive',
        ]);

        $res = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'inactive_guy',
            'password' => 'Pass@12345',
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    }

    public function test_deleted_account_handling(): void
    {
        $user = User::factory()->create([
            'username' => 'deleted_guy',
            'password' => Hash::make('Pass@12345'),
            'status' => 'active',
        ]);
        $user->delete(); // soft delete

        $res = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'deleted_guy',
            'password' => 'Pass@12345',
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    }

    public function test_email_not_verified_warning_on_successful_login(): void
    {
        $user = User::factory()->create([
            'username' => 'unverified_user',
            'password' => Hash::make('Secret@123'),
            'status' => 'active',
            'email_verified_at' => null,
        ]);

        $res = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'unverified_user',
            'password' => 'Secret@123',
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('data.email_verified', false)
            ->assertJsonPath('data.warning', 'আপনার ইমেইল ঠিকানা এখনো যাচাই করা হয়নি। অনুগ্রহ করে আপনার ইনবক্স চেক করে ইমেইল যাচাই করুন।');
    }
}
