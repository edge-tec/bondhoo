<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1SidebarNavigationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(): User
    {
        $user = User::factory()->create([
            'name' => 'Bondhoo System Admin',
            'username' => 'admin',
            'email' => 'admin@bondhoo.com',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80',
                'bio' => 'Bondhoo Platform Administrator',
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'dark_mode' => true,
            ]
        );

        return $user;
    }

    public function test_sidebar_navigation_vue_component_matches_screenshot_specification(): void
    {
        $sidebarPath = resource_path('js/Components/SidebarNavigation.vue');
        $this->assertFileExists($sidebarPath);

        $content = file_get_contents($sidebarPath);

        // 1. Header Section Verification
        $this->assertStringContainsString('Bondhoo System Admin', $content);
        $this->assertStringContainsString('@{{ user?.username || \'admin\' }}', $content);
        $this->assertStringContainsString('View profile', $content);
        $this->assertStringContainsString('isOnline', $content);
        $this->assertStringContainsString('bg-emerald-500', $content); // Green online dot
        $this->assertStringContainsString('toggleTheme', $content); // Theme toggle
        $this->assertStringContainsString('handleClose', $content); // Close button

        // 2. Main Navigation Menu: 10 Items Verification
        $this->assertStringContainsString('href="/messages"', $content);
        $this->assertStringContainsString('Messages', $content);

        $this->assertStringContainsString('href="/notifications"', $content);
        $this->assertStringContainsString('Notifications', $content);

        $this->assertStringContainsString('href="/reels"', $content);
        $this->assertStringContainsString('Reels', $content);
        $this->assertStringContainsString('New', $content); // New Badge on Reels

        $this->assertStringContainsString('href="/groups"', $content);
        $this->assertStringContainsString('Groups', $content);

        $this->assertStringContainsString('href="/pages"', $content);
        $this->assertStringContainsString('Pages', $content);

        $this->assertStringContainsString('href="/marketplace"', $content);
        $this->assertStringContainsString('Marketplace', $content);

        $this->assertStringContainsString('href="/live/studio"', $content);
        $this->assertStringContainsString('Live Studio', $content);
        $this->assertStringContainsString('Live', $content); // Live Badge on Live Studio

        $this->assertStringContainsString('href="/saved"', $content);
        $this->assertStringContainsString('Saved Posts', $content);

        $this->assertStringContainsString('href="/events"', $content);
        $this->assertStringContainsString('Events', $content);

        $this->assertStringContainsString('href="/memories"', $content);
        $this->assertStringContainsString('Memories', $content);

        // 3. Settings & Safety Section
        $this->assertStringContainsString('Settings & Safety', $content);
        $this->assertStringContainsString('href="/settings"', $content);
        $this->assertStringContainsString('Settings & Privacy', $content);
        $this->assertStringContainsString('href="/community-guidelines"', $content);
        $this->assertStringContainsString('Help & Guidelines', $content);

        // 4. Footer Section
        $this->assertStringContainsString('action="/logout"', $content);
        $this->assertStringContainsString('Log Out', $content);
        $this->assertStringContainsString('bg-[#3B1219]', $content); // Styled red container
    }

    public function test_all_13_sidebar_navigation_routes_are_accessible_for_authenticated_user(): void
    {
        $user = $this->createUser();

        // 1. Messages
        $res = $this->actingAs($user, 'web')->get('/messages');
        $this->assertTrue(in_array($res->status(), [200, 302], true));

        // 2. Notifications
        $res = $this->actingAs($user, 'web')->get('/notifications');
        $this->assertTrue(in_array($res->status(), [200, 302], true));

        // 3. Reels
        $res = $this->actingAs($user, 'web')->get('/reels');
        $this->assertTrue(in_array($res->status(), [200, 302], true));

        // 4. Groups
        $res = $this->actingAs($user, 'web')->get('/groups');
        $this->assertTrue(in_array($res->status(), [200, 302], true));

        // 5. Pages
        $res = $this->actingAs($user, 'web')->get('/pages');
        $this->assertTrue(in_array($res->status(), [200, 302], true));

        // 6. Marketplace
        $res = $this->actingAs($user, 'web')->get('/marketplace');
        $this->assertTrue(in_array($res->status(), [200, 302], true));

        // 7. Live Studio
        $res = $this->actingAs($user, 'web')->get('/live/studio');
        $this->assertTrue(in_array($res->status(), [200, 302], true));

        // 8. Saved Posts
        $res = $this->actingAs($user, 'web')->get('/saved');
        $this->assertTrue(in_array($res->status(), [200, 302], true));

        // 9. Events
        $res = $this->actingAs($user, 'web')->get('/events');
        $this->assertTrue(in_array($res->status(), [200, 302], true));

        // 10. Memories
        $res = $this->actingAs($user, 'web')->get('/memories');
        $this->assertTrue(in_array($res->status(), [200, 302], true));

        // 11. Settings & Privacy
        $res = $this->actingAs($user, 'web')->get('/settings');
        $this->assertTrue(in_array($res->status(), [200, 302], true));

        // 12. Help & Guidelines
        $res = $this->actingAs($user, 'web')->get('/community-guidelines');
        $this->assertTrue(in_array($res->status(), [200, 302], true));

        // 13. User Profile
        $res = $this->actingAs($user, 'web')->get('/profile/admin');
        $this->assertTrue(in_array($res->status(), [200, 302], true));
    }

    public function test_theme_mode_preference_can_be_persisted_to_database(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v1/settings/privacy', [
            'dark_mode' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.dark_mode', true);

        $user->settings->refresh();
        $this->assertTrue((bool) $user->settings->dark_mode);

        // Toggle to light mode
        $toggleRes = $this->actingAs($user, 'sanctum')->putJson('/api/v1/settings/privacy', [
            'dark_mode' => false,
        ]);

        $toggleRes->assertStatus(200)
            ->assertJsonPath('data.dark_mode', false);

        $user->settings->refresh();
        $this->assertFalse((bool) $user->settings->dark_mode);
    }
}
