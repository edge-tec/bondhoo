<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase14CompleteLogOutAndSessionManagementSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['label' => 'Super Admin', 'description' => 'Full access']);
        Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Admin', 'description' => 'Staff administrator']);
        Role::firstOrCreate(['name' => 'USER'], ['label' => 'Regular User', 'description' => 'Platform user']);
        Role::firstOrCreate(['name' => 'VERIFIED_USER'], ['label' => 'Verified User', 'description' => 'Identity verified citizen']);
    }

    protected function createUser(array $attributes = []): User
    {
        static $counter = 1400;
        $counter++;

        $user = User::factory()->create(array_merge([
            'username' => "phase14_user_{$counter}",
            'name' => "Phase 14 User {$counter}",
            'email' => "phase14_user_{$counter}@example.com",
            'phone' => "+880175500{$counter}",
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ], $attributes));

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => 'Phase 14 Session bio test',
                'gender' => 'male',
                'birth_date' => '1994-07-10',
            ]
        );

        return $user;
    }

    /**
     * Test web logout form submission terminates session, clears cookies, and redirects to login.
     */
    public function test_web_logout_form_terminates_session_clears_cookies_and_redirects_to_login(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'web')
            ->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest('web');

        // Verify no-cache security headers
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $response->assertHeader('Pragma', 'no-cache');
    }

    /**
     * Test web logout with JSON request returns success payload and terminates auth.
     */
    public function test_web_logout_json_request_terminates_session_and_returns_success_json(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'web')
            ->postJson('/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'সফলভাবে লগআউট সম্পন্ন হয়েছে।');

        $this->assertGuest('web');
    }

    /**
     * Test API logout revokes current Sanctum token and terminates device session.
     */
    public function test_api_logout_revokes_current_sanctum_token(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('android-device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify the token was revoked in database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'android-device',
        ]);
    }

    /**
     * Test API logout-all revokes all tokens across multiple devices.
     */
    public function test_api_logout_all_revokes_all_tokens_across_devices(): void
    {
        $user = $this->createUser();
        $token1 = $user->createToken('desktop-browser')->plainTextToken;
        $user->createToken('mobile-app');
        $user->createToken('tablet-browser');

        $this->assertDatabaseCount('personal_access_tokens', 3);

        $response = $this->withHeader('Authorization', 'Bearer '.$token1)
            ->postJson('/api/v1/auth/logout-all');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify all tokens for this user are revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * Test web logout-all endpoint terminates all sessions.
     */
    public function test_web_logout_all_terminates_all_active_sessions_for_user(): void
    {
        $user = $this->createUser();
        $user->createToken('pat-session-1');
        $user->createToken('pat-session-2');

        $response = $this->actingAs($user, 'web')
            ->postJson('/logout/all');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'সবগুলো ডিভাইস থেকে সফলভাবে লগআউট সম্পন্ন হয়েছে।');

        $this->assertGuest('web');
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    /**
     * Test sidebar navigation component contains functional red-styled Log Out button matching reference design.
     */
    public function test_sidebar_navigation_renders_logout_button_for_authenticated_users(): void
    {
        $sidebarPath = resource_path('js/Components/SidebarNavigation.vue');
        $this->assertFileExists($sidebarPath);

        $content = File::get($sidebarPath);

        // 1. Form POST /logout with csrf
        $this->assertStringContainsString('method="POST"', $content);
        $this->assertStringContainsString('action="/logout"', $content);
        $this->assertStringContainsString('name="_token"', $content);

        // 2. Exact red styling and label from reference UI
        $this->assertStringContainsString('Log Out', $content);
        $this->assertStringContainsString('bg-[#3B1219]', $content);
        $this->assertStringContainsString('text-[#EF4444]', $content);

        // 3. Fallback sign-in link when user is null
        $this->assertStringContainsString('href="/login"', $content);
        $this->assertStringContainsString('Sign In', $content);
    }

    /**
     * Test logged out user is rejected from protected endpoints.
     */
    public function test_logged_out_user_cannot_access_protected_endpoints(): void
    {
        $user = $this->createUser();

        // 1. Log out the user
        $this->actingAs($user, 'web')->post('/logout');

        // 2. Attempt to access protected settings overview API
        $response = $this->getJson('/api/v1/settings/overview');
        $response->assertStatus(401);
    }
}
