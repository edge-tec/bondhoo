<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UsernameHistory;
use App\Models\UserProfile;
use App\Support\ProfileResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileIdentityRoutingEnterpriseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * REGRESSION: a stale web session for User B must never override the real login token of User A.
     */
    public function test_stale_web_session_does_not_override_token_identity(): void
    {
        $rahim = $this->makeUser('rahim_real', 'Abdur Rahim');
        $nusrat = $this->makeUser('nusrat_stale', 'Nusrat Jahan');
        $rahimToken = $rahim->createToken('web-login')->plainTextToken;

        // Stale session authenticated as Nusrat, but the client is really logged in as Rahim.
        $response = $this->actingAs($nusrat, 'web')
            ->withUnencryptedCookie('jugajug_token', $rahimToken)
            ->get('/u/nusrat_stale');

        $response->assertOk();
        $response->assertViewHas('isOwner', false);
        $response->assertViewHas('viewer', fn ($viewer) => $viewer->id === $rahim->id);
        $response->assertDontSee('আপনার মনে কি চলছে, Nusrat?');
        $this->assertAuthenticatedAs($rahim, 'web');

        // /profile resolves to the real logged-in user, not the stale session user.
        $this->withUnencryptedCookie('jugajug_token', $rahimToken)
            ->get('/profile')
            ->assertRedirect('/u/rahim_real');
    }

    protected function makeUser(string $username, ?string $name = null): User
    {
        $user = User::factory()->create([
            'username' => $username,
            'name' => $name ?? ucfirst($username),
            'email' => "{$username}@example.com",
            'phone' => '+88017'.rand(10000000, 99999999),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'bio' => "Bio of {$username}",
            'about' => "About {$username}",
            'location' => 'Dhaka, Bangladesh',
        ]);

        return $user;
    }

    /**
     * TEST 1: User A visits User A profile -> isOwner is true, shows User A.
     */
    public function test_user_a_views_own_profile(): void
    {
        $userA = $this->makeUser('user_alpha', 'Alpha Rahim');

        $response = $this->actingAs($userA)
            ->get(route('profile.u', ['username' => 'user_alpha']));

        $response->assertOk();
        $response->assertViewHas('isOwner', true);
        $response->assertViewHas('user', function ($target) use ($userA) {
            return $target->id === $userA->id && $target->username === 'user_alpha';
        });
        $response->assertSee('Alpha Rahim');
        $this->assertAuthenticatedAs($userA);
    }

    /**
     * TEST 2: User A visits User B profile -> isOwner is false, shows User B, auth remains User A.
     */
    public function test_user_a_views_user_b_profile_without_identity_leak(): void
    {
        $userA = $this->makeUser('user_alpha', 'Alpha Rahim');
        $userB = $this->makeUser('user_beta', 'Beta Karim');

        $response = $this->actingAs($userA)
            ->get(route('profile.u', ['username' => 'user_beta']));

        $response->assertOk();
        $response->assertViewHas('isOwner', false);
        $response->assertViewHas('user', function ($target) use ($userB) {
            return $target->id === $userB->id && $target->username === 'user_beta';
        });
        $response->assertSee('Beta Karim');

        // CRITICAL ASSERTION: User A session must NOT be hijacked by User B!
        $this->assertAuthenticatedAs($userA);
    }

    /**
     * TEST 3: User A visits User C profile -> isOwner is false, shows User C, auth remains User A.
     */
    public function test_user_a_views_user_c_profile_deterministically(): void
    {
        $userA = $this->makeUser('user_alpha', 'Alpha Rahim');
        $userC = $this->makeUser('user_gamma', 'Gamma Hasan');

        $response = $this->actingAs($userA)
            ->get(route('profile.u', ['username' => 'user_gamma']));

        $response->assertOk();
        $response->assertViewHas('isOwner', false);
        $response->assertViewHas('user', function ($target) use ($userC) {
            return $target->id === $userC->id && $target->username === 'user_gamma';
        });
        $response->assertSee('Gamma Hasan');
        $this->assertAuthenticatedAs($userA);
    }

    /**
     * TEST 4: Navigation sequence A -> B -> C leaves authentication intact as User A.
     */
    public function test_navigation_sequence_maintains_viewer_identity(): void
    {
        $userA = $this->makeUser('user_alpha', 'Alpha Rahim');
        $userB = $this->makeUser('user_beta', 'Beta Karim');
        $userC = $this->makeUser('user_gamma', 'Gamma Hasan');

        $this->actingAs($userA);

        $resB = $this->get(route('profile.u', ['username' => 'user_beta']));
        $resB->assertOk();
        $resB->assertViewHas('isOwner', false);
        $this->assertAuthenticatedAs($userA);

        $resC = $this->get(route('profile.u', ['username' => 'user_gamma']));
        $resC->assertOk();
        $resC->assertViewHas('isOwner', false);
        $this->assertAuthenticatedAs($userA);

        $resA = $this->get(route('profile.u', ['username' => 'user_alpha']));
        $resA->assertOk();
        $resA->assertViewHas('isOwner', true);
        $this->assertAuthenticatedAs($userA);
    }

    /**
     * TEST 5 & 6: Guest viewing profile B never receives an auth token or cookie.
     */
    public function test_guest_viewing_profile_does_not_auto_login_or_receive_token(): void
    {
        $userB = $this->makeUser('user_beta', 'Beta Karim');

        $response = $this->get(route('profile.u', ['username' => 'user_beta']));

        $response->assertOk();
        $response->assertViewHas('isOwner', false);
        $response->assertViewHas('viewer', null);
        $response->assertViewHas('authToken', null);

        // Must not issue a jugajug_token cookie to guests
        $this->assertGuest();
        $response->assertCookieMissing('jugajug_token');
    }

    /**
     * TEST 7: Direct URL /u/{username} and /profile/@{username} route resolution.
     */
    public function test_canonical_routes_resolve_correct_profile(): void
    {
        $user = $this->makeUser('tariq_dev', 'Tariq Dev');

        $resU = $this->get('/u/tariq_dev');
        $resU->assertOk();
        $resU->assertSee('Tariq Dev');

        $resAt = $this->get('/profile/@tariq_dev');
        $resAt->assertOk();
        $resAt->assertSee('Tariq Dev');
    }

    /**
     * TEST 8: Numeric ID route /user/{id} 301 redirects to canonical /u/{username}.
     * Internal database IDs are never exposed or retained in the address bar.
     */
    public function test_numeric_id_route_redirects_to_canonical_username_url(): void
    {
        $user = $this->makeUser('canonical_user', 'Canonical User');

        $response = $this->get("/user/{$user->id}");
        $response->assertRedirect('/u/canonical_user');
        $response->assertStatus(301);
    }

    /**
     * TEST 9: Renamed username redirects 301 to new canonical /u/{username}.
     */
    public function test_old_username_history_redirects_to_canonical_username(): void
    {
        $user = $this->makeUser('new_handle', 'New Handle User');

        UsernameHistory::create([
            'user_id' => $user->id,
            'username' => 'old_handle',
        ]);

        $response = $this->get('/u/old_handle');
        $response->assertRedirect('/u/new_handle');
        $response->assertStatus(301);
    }

    /**
     * TEST 10: Centralized ProfileResolver always creates canonical URL without raw DB ID.
     */
    public function test_centralized_profile_resolver_helper(): void
    {
        $user = $this->makeUser('salman_khan', 'Salman Khan');

        // From User model
        $this->assertEquals('/u/salman_khan', getUserProfileUrl($user));
        $this->assertEquals('/u/salman_khan', ProfileResolver::url($user));

        // From array
        $this->assertEquals('/u/salman_khan', getUserProfileUrl(['username' => 'salman_khan']));

        // From string with @
        $this->assertEquals('/u/salman_khan', getUserProfileUrl('@salman_khan'));

        // Never reveals raw ID
        $this->assertStringNotContainsString((string) $user->id, getUserProfileUrl($user));

        // Resolve by username
        $resolved = resolveUserProfile('salman_khan');
        $this->assertNotNull($resolved);
        $this->assertEquals($user->id, $resolved->id);

        // Resolve with leading @
        $resolvedWithAt = resolveUserProfile('@salman_khan');
        $this->assertNotNull($resolvedWithAt);
        $this->assertEquals($user->id, $resolvedWithAt->id);
    }

    /**
     * TEST 11: /profile route redirects authenticated viewer to their own canonical profile.
     */
    public function test_profile_me_route_redirects_to_own_canonical_profile(): void
    {
        $user = $this->makeUser('nusrat_jahan', 'Nusrat Jahan');

        $response = $this->actingAs($user)->get('/profile');
        $response->assertRedirect('/u/nusrat_jahan');
    }

    /**
     * TEST 12: API profile endpoint returns correct requested profile.
     */
    public function test_api_profile_endpoint_returns_requested_identity(): void
    {
        $userA = $this->makeUser('api_user_a', 'API User A');
        $userB = $this->makeUser('api_user_b', 'API User B');

        $tokenA = $userA->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->getJson('/api/v1/users/api_user_b');

        $response->assertOk();
        $response->assertJsonPath('data.user.username', 'api_user_b');
        $response->assertJsonPath('data.user.id', $userB->id);
        $response->assertJsonPath('data.is_owner', false);

        // Owner-only sensitive fields must never leak to other viewers
        $response->assertJsonMissingPath('data.user.email');
        $response->assertJsonMissingPath('data.user.phone');

        // Requesting with a leading @ must resolve the same identity
        $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->getJson('/api/v1/users/@api_user_b')
            ->assertOk()
            ->assertJsonPath('data.user.username', 'api_user_b');

        // Own profile shows is_owner = true
        $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->getJson('/api/v1/users/api_user_a')
            ->assertOk()
            ->assertJsonPath('data.user.username', 'api_user_a')
            ->assertJsonPath('data.is_owner', true);
    }
}
