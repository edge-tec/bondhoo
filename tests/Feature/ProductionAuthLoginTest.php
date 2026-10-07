<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionAuthLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'email' => 'login_user@jugajug.com',
            'username' => 'loginuser',
            'phone' => '+8801700112233',
            'password' => 'CorrectPassword@123',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'login_user@jugajug.com',
            'password' => 'CorrectPassword@123',
            'device_name' => 'Chrome on Windows 11',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'লগইন সফল হয়েছে।',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'username', 'email'],
                    'token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('user_sessions', [
            'user_id' => $user->id,
            'device_name' => 'Chrome on Windows 11',
            'is_current' => true,
        ]);
    }

    public function test_user_can_login_with_username_and_phone(): void
    {
        User::factory()->create([
            'username' => 'citizen_one',
            'phone' => '+8801999887766',
            'password' => 'CorrectPassword@123',
            'status' => 'active',
        ]);

        // Login with username
        $res1 = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'citizen_one',
            'password' => 'CorrectPassword@123',
        ]);
        $res1->assertStatus(200);

        // Login with phone
        $res2 = $this->postJson('/api/v2/auth/login', [
            'identifier' => '+8801999887766',
            'password' => 'CorrectPassword@123',
        ]);
        $res2->assertStatus(200);
    }

    public function test_failed_login_increments_attempts_and_records_audit(): void
    {
        $user = User::factory()->create([
            'username' => 'target_user',
            'password' => 'CorrectPassword@123',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'target_user',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(422);

        $this->assertEquals(1, $user->fresh()->failed_login_attempts);

        $this->assertDatabaseHas('failed_logins', [
            'identifier' => 'target_user',
            'reason' => 'invalid_credentials',
        ]);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'status' => 'failed',
        ]);
    }

    public function test_account_auto_locks_after_five_failed_attempts(): void
    {
        $user = User::factory()->create([
            'username' => 'brute_force_target',
            'password' => 'ValidPassword@123',
            'status' => 'active',
        ]);

        // 4 failed attempts
        for ($i = 0; $i < 4; $i++) {
            $res = $this->postJson('/api/v2/auth/login', [
                'identifier' => 'brute_force_target',
                'password' => 'BadPass_'.$i,
            ]);
            $res->assertStatus(422);
        }

        $this->assertFalse($user->fresh()->isLocked());

        // 5th failed attempt -> locks account
        $res5 = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'brute_force_target',
            'password' => 'BadPass_5',
        ]);
        $res5->assertStatus(422);

        $user->refresh();
        $this->assertTrue($user->isLocked());
        $this->assertEquals(5, $user->failed_login_attempts);

        // Attempting with correct password while locked must be rejected
        $lockedResponse = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'brute_force_target',
            'password' => 'ValidPassword@123',
        ]);
        $lockedResponse->assertStatus(422);
    }

    public function test_remember_me_marks_device_trusted(): void
    {
        $user = User::factory()->create([
            'username' => 'remember_user',
            'password' => 'ValidPass@123',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'remember_user',
            'password' => 'ValidPass@123',
            'remember' => true,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('trusted_devices', [
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_logout_and_revoke_all_sessions(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('active-device')->plainTextToken;

        // Logout
        $logoutRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v2/auth/logout');
        $logoutRes->assertStatus(200);

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
