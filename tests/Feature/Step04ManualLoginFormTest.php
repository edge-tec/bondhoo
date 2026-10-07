<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Step04ManualLoginFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_blade_view_renders_production_ready_form_elements(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);

        // Form elements
        $response->assertSee('id="loginIdentifier"', false);
        $response->assertSee('id="loginPassword"', false);
        $response->assertSee('id="rememberMe"', false);
        $response->assertSee('id="submitBtn"', false);
        $response->assertSee('id="pwdToggleBtn"', false);
        $response->assertSee('id="capsLockAlert"', false);
        $response->assertSee('id="identifierTypeBadge"', false);

        // Verification of detection logic in script
        $response->assertSee('detectIdentifierType', false);
        $response->assertSee('togglePasswordVisibility', false);
        $response->assertSee('CapsLock', false);
        $response->assertSee('isSubmitting', false);
    }

    public function test_server_side_validation_fails_when_required_fields_are_missing(): void
    {
        // API v1
        $resV1 = $this->postJson('/api/v1/auth/login', []);
        $resV1->assertStatus(422)
            ->assertJsonValidationErrors(['identifier', 'password'])
            ->assertJsonFragment([
                'identifier' => ['ইমেইল, ইউজারনেম বা মোবাইল নম্বর প্রদান করা আবশ্যক।'],
            ])
            ->assertJsonFragment([
                'password' => ['পাসওয়ার্ড প্রদান করা আবশ্যক।'],
            ]);

        // API v2
        $resV2 = $this->postJson('/api/v2/auth/login', []);
        $resV2->assertStatus(422)
            ->assertJsonValidationErrors(['identifier', 'password'])
            ->assertJsonFragment([
                'identifier' => ['ইমেইল, ইউজারনেম বা মোবাইল নম্বর প্রদান করা আবশ্যক।'],
            ])
            ->assertJsonFragment([
                'password' => ['পাসওয়ার্ড প্রদান করা আবশ্যক।'],
            ]);

        // Web POST /login
        $resWeb = $this->post('/login', [], ['Accept' => 'application/json']);
        $resWeb->assertStatus(422)
            ->assertJsonValidationErrors(['identifier', 'password']);
    }

    public function test_server_side_validation_enforces_minimum_password_length(): void
    {
        $res = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'testuser',
            'password' => '12345', // only 5 characters
        ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।'],
            ]);
    }

    public function test_remember_me_persists_session_and_sets_remember_token(): void
    {
        $user = User::factory()->create([
            'email' => 'rememberme@jugajug.com',
            'password' => Hash::make('ValidPass@2026'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'identifier' => 'rememberme@jugajug.com',
            'password' => 'ValidPass@2026',
            'remember' => true,
        ]);

        $response->assertRedirect('/feed');
        $this->assertAuthenticatedAs($user, 'web');

        // Refresh user from DB to verify remember_token is populated
        $user->refresh();
        $this->assertNotNull($user->remember_token);
        $this->assertNotEmpty($user->remember_token);
    }

    public function test_login_without_remember_me_does_not_set_remember_cookie(): void
    {
        $user = User::factory()->create([
            'email' => 'noremember@jugajug.com',
            'password' => Hash::make('ValidPass@2026'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'identifier' => 'noremember@jugajug.com',
            'password' => 'ValidPass@2026',
            'remember' => false,
        ]);

        $response->assertRedirect('/feed');
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_user_can_login_with_valid_credentials_via_web_form(): void
    {
        $user = User::factory()->create([
            'email' => 'manualuser@jugajug.com',
            'password' => Hash::make('SecretPass@2026'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'identifier' => 'manualuser@jugajug.com',
            'password' => 'SecretPass@2026',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'লগইন সফল হয়েছে।',
                'redirect' => '/feed',
            ]);

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_admin_login_validation_fails_on_empty_fields_and_short_password(): void
    {
        // Missing fields
        $res = $this->postJson('/api/v1/admin/login', []);
        $res->assertStatus(422)
            ->assertJsonValidationErrors(['identifier', 'password'])
            ->assertJsonFragment([
                'identifier' => ['ইমেইল, ইউজারনেম বা মোবাইল নম্বর প্রদান করা আবশ্যক।'],
            ])
            ->assertJsonFragment([
                'password' => ['পাসওয়ার্ড প্রদান করা আবশ্যক।'],
            ]);

        // Short password (< 6 characters)
        $shortRes = $this->postJson('/api/v1/admin/login', [
            'identifier' => 'admin@jugajug.com',
            'password' => '12345',
        ]);
        $shortRes->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।'],
            ]);
    }

    public function test_admin_remember_me_sets_remember_token_in_database(): void
    {
        $admin = Admin::create([
            'name' => 'Remember Admin',
            'username' => 'remember_admin',
            'email' => 'remadmin@jugajug.com',
            'password' => Hash::make('AdminPass@2026'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $res = $this->post('/admin/login', [
            'identifier' => 'remadmin@jugajug.com',
            'password' => 'AdminPass@2026',
            'remember' => true,
        ]);

        $res->assertRedirect('/admin/auth-management');
        $this->assertAuthenticatedAs($admin, 'admin');

        $admin->refresh();
        $this->assertNotNull($admin->remember_token);
        $this->assertNotEmpty($admin->remember_token);
    }
}
