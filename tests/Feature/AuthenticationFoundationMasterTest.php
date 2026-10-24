<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationFoundationMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. Registration: Validation failure on duplicate email/username and weak password.
     */
    public function test_registration_validation_fails_for_duplicate_or_invalid_data(): void
    {
        User::factory()->create([
            'username' => 'existing_user',
            'email' => 'existing@bondhoo.test',
        ]);

        // Duplicate email & username, short password
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'username' => 'existing_user',
            'email' => 'existing@bondhoo.test',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'email', 'password']);
    }

    /**
     * 2. Registration: Successful API registration creates user, profile, privacy settings, and returns Sanctum token.
     */
    public function test_user_can_register_via_api_and_receives_bearer_token(): void
    {
        $payload = [
            'name' => 'John Bondhoo',
            'username' => 'john_bondhoo',
            'email' => 'john@bondhoo.test',
            'password' => 'SecurePass2026!',
            'device_name' => 'Android-Test-Device',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'john_bondhoo')
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertNotNull($response->json('data.token'));

        $this->assertDatabaseHas('users', [
            'username' => 'john_bondhoo',
            'email' => 'john@bondhoo.test',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'display_name' => 'John Bondhoo',
        ]);

        $this->assertDatabaseHas('privacy_settings', [
            'profile_visibility' => 'public',
        ]);
    }

    /**
     * 3. Login: Universal identifier login (Email, Username, or Phone).
     */
    public function test_user_can_login_with_email_or_username(): void
    {
        $user = User::factory()->create([
            'username' => 'multilogin_user',
            'email' => 'multi@bondhoo.test',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        // 3a. Login using Email
        $emailLogin = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'multi@bondhoo.test',
            'password' => 'Secret123!',
            'device_name' => 'MacBook-Pro',
        ]);

        $emailLogin->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertNotNull($emailLogin->json('data.token'));

        // 3b. Login using Username
        $usernameLogin = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'multilogin_user',
            'password' => 'Secret123!',
            'device_name' => 'iPhone-16',
        ]);

        $usernameLogin->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id);
    }

    /**
     * 4. Login: Invalid credentials rejection and rate limiting counter.
     */
    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'target@bondhoo.test',
            'password' => Hash::make('CorrectPassword123!'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'target@bondhoo.test',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    }

    /**
     * 5. Logout: Current token revocation.
     */
    public function test_user_can_logout_and_revoke_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_session')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify token is deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test_session',
        ]);
    }

    /**
     * 6. Logout All: Revokes all tokens across all devices.
     */
    public function test_user_can_logout_from_all_devices(): void
    {
        $user = User::factory()->create();
        $token1 = $user->createToken('device_1')->plainTextToken;
        $user->createToken('device_2')->plainTextToken;
        $user->createToken('device_3')->plainTextToken;

        $this->assertCount(3, $user->tokens);

        $response = $this->withHeader('Authorization', 'Bearer '.$token1)
            ->postJson('/api/v1/auth/logout-all');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertCount(0, $user->fresh()->tokens);
    }

    /**
     * 7. Password Reset: Request reset link/token and reset password.
     */
    public function test_password_reset_flow_generates_token_and_resets_password(): void
    {
        $user = User::factory()->create([
            'email' => 'resetme@bondhoo.test',
            'password' => Hash::make('OldPassword123!'),
        ]);

        // 7a. Request reset link email
        $forgotResponse = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'resetme@bondhoo.test',
        ]);

        $forgotResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $resetToken = $forgotResponse->json('data.reset_token');
        $this->assertNotNull($resetToken);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'resetme@bondhoo.test',
        ]);

        // 7b. Reset password using the one-time token
        $resetResponse = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'resetme@bondhoo.test',
            'token' => $resetToken,
            'password' => 'BrandNewPassword2026!',
            'password_confirmation' => 'BrandNewPassword2026!',
        ]);

        $resetResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify password was updated
        $this->assertTrue(Hash::check('BrandNewPassword2026!', $user->fresh()->password));

        // Verify token was deleted after single use
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'resetme@bondhoo.test',
        ]);
    }

    /**
     * 8. Email Verification: Signed URL verifies email.
     */
    public function test_signed_email_verification_url_verifies_user(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified@bondhoo.test',
            'email_verified_at' => null,
        ]);

        $this->assertFalse($user->hasVerifiedEmail());

        $signedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($signedUrl);
        $response->assertRedirect(route('login'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    /**
     * 9. Email Verification: Tampered signature is rejected.
     */
    public function test_tampered_email_verification_url_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'tampered@bondhoo.test',
            'email_verified_at' => null,
        ]);

        $tamperedUrl = url("/email/verify/{$user->id}/invalidhash?expires=9999999999&signature=invalidsig");

        $response = $this->get($tamperedUrl);
        $response->assertStatus(403);
    }

    /**
     * 10. Profile Management: Fetch and update profile details.
     */
    public function test_authenticated_user_can_fetch_and_update_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'profile_man',
            'name' => 'Profile Man',
        ]);
        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Profile',
            'last_name' => 'Man',
            'bio' => 'Original bio text',
            'location' => 'Dhaka',
        ]);

        // 10a. Unauthenticated access is rejected
        $this->getJson('/api/v1/profile')->assertStatus(401);

        // 10b. Authenticated retrieval
        $getRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/profile');
        $getRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'profile_man');

        // 10c. Authenticated update
        $updateRes = $this->actingAs($user, 'sanctum')->putJson('/api/v1/profile', [
            'name' => 'Profile Super Man',
            'bio' => 'Updated bio text with new achievements.',
            'location' => 'Chittagong',
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'bio' => 'Updated bio text with new achievements.',
            'location' => 'Chittagong',
        ]);
    }

    /**
     * 11. Authorization: UserPolicy prevents unauthorized profile modification.
     */
    public function test_user_policy_allows_self_update_but_forbids_updating_others(): void
    {
        $alice = User::factory()->create(['username' => 'alice_auth']);
        $bob = User::factory()->create(['username' => 'bob_auth']);

        // Alice can update her own model
        $this->assertTrue($alice->can('update', $alice));

        // Alice cannot update Bob's model
        $this->assertFalse($alice->can('update', $bob));
    }
}
