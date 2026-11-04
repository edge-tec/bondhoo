<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase15CompleteReleaseGateAndSidebarMasterAuditTest extends TestCase
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
        static $counter = 1500;
        $counter++;

        $user = User::factory()->create(array_merge([
            'username' => "phase15_admin_{$counter}",
            'name' => "Bondhoo Master Auditor {$counter}",
            'email' => "phase15_admin_{$counter}@example.com",
            'phone' => "+880176600{$counter}",
            'password' => Hash::make('AdminSecret123!'),
            'status' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ], $attributes));

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => 'Enterprise System Auditor',
                'gender' => 'male',
                'birth_date' => '1990-01-01',
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'who_can_see_posts' => 'public',
                'who_can_send_friend_requests' => 'everyone',
                'dark_mode' => true,
                'language' => 'bn',
            ]
        );

        return $user;
    }

    /**
     * Audit Test 1: Verify all 13 sidebar destinations respond without 404, 500, or dead routes.
     */
    public function test_all_thirteen_sidebar_destinations_are_fully_operational(): void
    {
        $user = $this->createUser();
        $this->actingAs($user, 'web');

        // 1. Messages
        $res = $this->get('/messages');
        $this->assertNotEquals(404, $res->getStatusCode());
        $this->assertNotEquals(500, $res->getStatusCode());

        // 2. Notifications
        $res = $this->get('/notifications');
        $this->assertNotEquals(404, $res->getStatusCode());
        $this->assertNotEquals(500, $res->getStatusCode());

        // 3. Reels
        $res = $this->get('/reels');
        $this->assertNotEquals(404, $res->getStatusCode());
        $this->assertNotEquals(500, $res->getStatusCode());

        // 4. Groups
        $res = $this->get('/groups');
        $this->assertNotEquals(404, $res->getStatusCode());
        $this->assertNotEquals(500, $res->getStatusCode());

        // 5. Pages
        $res = $this->get('/pages');
        $this->assertNotEquals(404, $res->getStatusCode());
        $this->assertNotEquals(500, $res->getStatusCode());

        // 6. Marketplace
        $res = $this->get('/marketplace');
        $this->assertNotEquals(404, $res->getStatusCode());
        $this->assertNotEquals(500, $res->getStatusCode());

        // 7. Live Studio
        $res = $this->get('/live/studio');
        $this->assertNotEquals(404, $res->getStatusCode());
        $this->assertNotEquals(500, $res->getStatusCode());

        // 8. Saved Posts (redirects to /dashboard?tab=saved)
        $res = $this->get('/saved');
        $this->assertTrue(in_array($res->getStatusCode(), [200, 302], true));
        if ($res->isRedirect()) {
            $this->assertStringContainsString('tab=saved', $res->headers->get('Location'));
        }

        // 9. Events (redirects to /dashboard?tab=events)
        $res = $this->get('/events');
        $this->assertTrue(in_array($res->getStatusCode(), [200, 302], true));
        if ($res->isRedirect()) {
            $this->assertStringContainsString('tab=events', $res->headers->get('Location'));
        }

        // 10. Memories (redirects to /dashboard?tab=memories)
        $res = $this->get('/memories');
        $this->assertTrue(in_array($res->getStatusCode(), [200, 302], true));
        if ($res->isRedirect()) {
            $this->assertStringContainsString('tab=memories', $res->headers->get('Location'));
        }

        // 11. Settings & Privacy
        $res = $this->get('/settings');
        $res->assertStatus(200);

        // 12. Help & Guidelines
        $res = $this->get('/community-guidelines');
        $res->assertStatus(200);

        $resHelp = $this->get('/help');
        $resHelp->assertStatus(200);

        // 13. Log Out
        $resLogout = $this->post('/logout');
        $this->assertTrue(in_array($resLogout->getStatusCode(), [200, 302], true));
    }

    /**
     * Audit Test 2: Verify zero dead buttons in SidebarNavigation component source code.
     */
    public function test_sidebar_navigation_component_has_zero_dead_buttons(): void
    {
        $file = resource_path('js/Components/SidebarNavigation.vue');
        $this->assertFileExists($file);
        $content = File::get($file);

        // Header user profile link
        $this->assertStringContainsString("user ? '/profile/' + user.username : '/login'", $content);
        $this->assertStringContainsString('View profile', $content);

        // Theme toggle with real API call
        $this->assertStringContainsString('@click="toggleTheme"', $content);
        $this->assertStringContainsString("fetch('/api/v1/settings/privacy'", $content);
        $this->assertStringContainsString('dark_mode: isDark.value', $content);

        // Close sidebar button
        $this->assertStringContainsString('@click="handleClose"', $content);
        $this->assertStringContainsString("emit('close')", $content);

        // Badges: New on Reels, Live with ping on Live Studio
        $this->assertStringContainsString('Reels', $content);
        $this->assertStringContainsString('New', $content);
        $this->assertStringContainsString('Live Studio', $content);
        $this->assertStringContainsString('animate-ping', $content);

        // Log out button form
        $this->assertStringContainsString('action="/logout"', $content);
        $this->assertStringContainsString('type="submit"', $content);
        $this->assertStringContainsString('Log Out', $content);
    }

    /**
     * Audit Test 3: Verify SidebarLeft (desktop) embeds SidebarNavigation.
     */
    public function test_desktop_sidebar_embeds_sidebar_navigation_correctly(): void
    {
        $desktopSidebar = resource_path('js/Components/SidebarLeft.vue');
        $this->assertFileExists($desktopSidebar);
        $content = File::get($desktopSidebar);

        $this->assertStringContainsString("import SidebarNavigation from './SidebarNavigation.vue'", $content);
        $this->assertStringContainsString('<SidebarNavigation', $content);
        $this->assertStringContainsString(':user="user"', $content);
        $this->assertStringContainsString(':unread-count="unreadCount"', $content);
        $this->assertStringContainsString(':unread-messages-count="unreadMessagesCount"', $content);
    }

    /**
     * Audit Test 4: Verify Navbar (mobile drawer) embeds SidebarNavigation.
     */
    public function test_mobile_navbar_drawer_embeds_sidebar_navigation_correctly(): void
    {
        $navbar = resource_path('js/Components/Navbar.vue');
        $this->assertFileExists($navbar);
        $content = File::get($navbar);

        $this->assertStringContainsString("import SidebarNavigation from './SidebarNavigation.vue'", $content);
        $this->assertStringContainsString('<SidebarNavigation', $content);
        $this->assertStringContainsString(':is-mobile="true"', $content);
        $this->assertStringContainsString('@close="closeDrawer"', $content);
    }

    /**
     * Audit Test 5: End-to-end theme toggle persists to database via settings API.
     */
    public function test_theme_toggle_persists_to_database_via_settings_api(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        // Initially dark_mode is true
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'dark_mode' => 1,
        ]);

        // Toggle to light mode
        $res = $this->putJson('/api/v1/settings/privacy', [
            'dark_mode' => false,
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('data.dark_mode', false);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'dark_mode' => 0,
        ]);

        // Toggle back to dark mode
        $resDark = $this->putJson('/api/v1/settings/privacy', [
            'dark_mode' => true,
        ]);

        $resDark->assertStatus(200)
            ->assertJsonPath('data.dark_mode', true);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'dark_mode' => 1,
        ]);
    }
}
