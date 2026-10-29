<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase7UnifiedResponsiveProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
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
            'location' => 'Dhaka, Bangladesh',
        ]);

        return $user;
    }

    /**
     * Direct GET /u/{username} without Inertia headers renders Vue 3 Inertia Profile by default.
     */
    public function test_direct_get_u_renders_inertia_vue_profile(): void
    {
        $user = $this->makeUser('shakib_dev', 'Shakib Dev');

        $response = $this->get('/u/shakib_dev');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Profile')
            ->where('user.username', 'shakib_dev')
            ->where('user.name', 'Shakib Dev')
        );
    }

    /**
     * Mobile User-Agent on /u/{username} receives modern Inertia Vue 3 interface, not legacy Blade.
     */
    public function test_mobile_user_agent_receives_inertia_vue_profile(): void
    {
        $user = $this->makeUser('tamim_mobile', 'Tamim Mobile');

        $mobileUserAgents = [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
        ];

        foreach ($mobileUserAgents as $ua) {
            $response = $this->withHeaders(['User-Agent' => $ua])->get('/u/tamim_mobile');

            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Profile')
                ->where('user.username', 'tamim_mobile')
            );
        }
    }

    /**
     * All canonical route aliases consistently render Inertia Vue 3 Profile.
     */
    public function test_all_profile_aliases_render_inertia_vue_profile(): void
    {
        $user = $this->makeUser('mushfiq_test', 'Mushfiq Test');

        $aliases = [
            '/u/mushfiq_test',
            '/profile/@mushfiq_test',
            '/user/mushfiq_test',
            '/profile/mushfiq_test',
            '/@mushfiq_test',
        ];

        foreach ($aliases as $route) {
            $response = $this->get($route);
            $response->assertOk();
            $response->assertInertia(fn (Assert $page) => $page
                ->component('Profile')
                ->where('user.username', 'mushfiq_test')
            );
        }
    }

    /**
     * ?blade=1 parameter routes to legacy Blade view only when explicitly requested and preserves auth.
     */
    public function test_blade_parameter_renders_blade_view_without_bypassing_security(): void
    {
        $user = $this->makeUser('mahmud_blade', 'Mahmud Blade');

        $response = $this->get('/u/mahmud_blade?blade=1');

        $response->assertOk();
        $response->assertViewIs('profile.show');
        $response->assertViewHas('user');
        // Unauthenticated guest must NOT be given administrative powers
        $this->assertGuest();
        $response->assertDontSee('সিকিউরিটি কনসোল');
    }

    /**
     * Invalid username returns 404 on both standard and mobile requests.
     */
    public function test_invalid_username_returns_404(): void
    {
        $response = $this->get('/u/nonexistent_user_xyz_123');
        $response->assertNotFound();

        $responseMobile = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
        ])->get('/u/nonexistent_user_xyz_123');
        $responseMobile->assertNotFound();
    }
}
