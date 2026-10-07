<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_register_with_email(): void
    {
        $payload = [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'Secret1234!',
            'device_name' => 'test-device',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'username', 'email'],
                    'token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'display_name' => 'John Doe',
        ]);

        $this->assertDatabaseHas('user_settings', [
            'who_can_see_posts' => 'public',
        ]);
    }

    public function test_user_can_register_with_phone_without_email(): void
    {
        $payload = [
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'phone' => '+8801999999999',
            'password' => 'Secret1234!',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'username' => 'janedoe',
            'phone' => '+8801999999999',
        ]);
    }

    public function test_registration_validation_enforces_unique_constraints(): void
    {
        User::factory()->create([
            'username' => 'existinguser',
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'existinguser',
            'email' => 'existing@example.com',
            'password' => 'Secret1234!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'email']);
    }

    public function test_user_can_login_with_email_or_username_or_phone(): void
    {
        $user = User::factory()->create([
            'username' => 'playerone',
            'email' => 'player@example.com',
            'phone' => '+8801888888888',
            'password' => 'SecurePass123',
            'status' => 'active',
        ]);

        // Login with email
        $res1 = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'player@example.com',
            'password' => 'SecurePass123',
        ]);
        $res1->assertStatus(200)->assertJson(['success' => true]);

        // Login with username
        $res2 = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'playerone',
            'password' => 'SecurePass123',
        ]);
        $res2->assertStatus(200)->assertJson(['success' => true]);

        // Login with phone
        $res3 = $this->postJson('/api/v1/auth/login', [
            'identifier' => '+8801888888888',
            'password' => 'SecurePass123',
        ]);
        $res3->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_login_fails_with_incorrect_password(): void
    {
        User::factory()->create([
            'username' => 'alice',
            'password' => 'CorrectPassword',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'alice',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    }

    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $user = User::factory()->create(['username' => 'bob']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => 'bob',
                    ],
                ],
            ]);
    }

    public function test_user_can_logout_from_current_device(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_user_can_logout_from_all_devices(): void
    {
        $user = User::factory()->create();
        $token1 = $user->createToken('device-1')->plainTextToken;
        $user->createToken('device-2');
        $user->createToken('device-3');

        $this->assertCount(3, $user->tokens);

        $response = $this->withHeader('Authorization', "Bearer {$token1}")
            ->postJson('/api/v1/auth/logout-all');

        $response->assertStatus(200);
        $this->assertCount(0, $user->fresh()->tokens);
    }
}
