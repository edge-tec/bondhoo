<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase73EnterpriseMobileNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_mobile_navigation_drawer_contains_all_14_platform_items(): void
    {
        $user = User::factory()->create([
            'username' => 'enterprise_nav_user',
            'name' => 'Enterprise Navigation User',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'web')->get('/app');
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Feed')
            ->has('auth.user')
        );

        // Inspect Navbar.vue component definition
        $navbarPath = resource_path('js/Components/Navbar.vue');
        $this->assertFileExists($navbarPath);
        $navbarContent = file_get_contents($navbarPath);

        // 1. Drawer Container & Dialog Semantics
        $this->assertStringContainsString('id="mobileMenuDrawer"', $navbarContent);
        $this->assertStringContainsString('role="dialog"', $navbarContent);
        $this->assertStringContainsString('aria-modal="true"', $navbarContent);
        $this->assertStringContainsString('aria-labelledby="mobileDrawerTitle"', $navbarContent);

        // 2. Navigation Hub Section & Core 14 Options
        $this->assertStringContainsString('Navigation Hub', $navbarContent);
        $this->assertStringContainsString('Feed / Home', $navbarContent);
        $this->assertStringContainsString('Friends', $navbarContent);
        $this->assertStringContainsString('Messages', $navbarContent);
        $this->assertStringContainsString('Notifications', $navbarContent);
        $this->assertStringContainsString('Reels', $navbarContent);
        $this->assertStringContainsString('Groups', $navbarContent);
        $this->assertStringContainsString('Pages', $navbarContent);
        $this->assertStringContainsString('Marketplace', $navbarContent);
        $this->assertStringContainsString('Live Studio', $navbarContent);
        $this->assertStringContainsString('Saved Posts', $navbarContent);
        $this->assertStringContainsString('Events', $navbarContent);
        $this->assertStringContainsString('Memories', $navbarContent);
        $this->assertStringContainsString('Settings & Privacy', $navbarContent);
        $this->assertStringContainsString('Help & Guidelines', $navbarContent);

        // 3. Drawer Header: User Information & Profile Link
        $this->assertStringContainsString('View profile', $navbarContent);
        $this->assertStringContainsString('mobileDrawerTitle', $navbarContent);
        $this->assertStringContainsString('user?.username', $navbarContent);

        // 4. Drawer Footer: Logout Form & CSRF
        $this->assertStringContainsString('action="/logout"', $navbarContent);
        $this->assertStringContainsString('Log Out', $navbarContent);
    }

    public function test_mobile_bottom_navigation_has_five_dedicated_items(): void
    {
        $user = User::factory()->create([
            'username' => 'bottom_nav_user',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'web')->get('/app');
        $response->assertStatus(200);

        // Inspect Navbar.vue component definition
        $navbarContent = file_get_contents(resource_path('js/Components/Navbar.vue'));

        // 5 Bottom Navigation Controls
        $this->assertStringContainsString('mobile-bottom-nav', $navbarContent);
        $this->assertStringContainsString('Home', $navbarContent);
        $this->assertStringContainsString('Reels', $navbarContent);
        $this->assertStringContainsString('id="mobileNavCreateBtn"', $navbarContent);
        $this->assertStringContainsString('Chat', $navbarContent);
        $this->assertStringContainsString('Menu', $navbarContent);

        // Grid-based 5-column layout for equal distribution
        $this->assertStringContainsString('grid grid-cols-5', $navbarContent);
    }

    public function test_mobile_create_action_sheet_modal_is_present(): void
    {
        $user = User::factory()->create([
            'username' => 'create_sheet_user',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->get('/app');
        $response->assertStatus(200);

        $navbarContent = file_get_contents(resource_path('js/Components/Navbar.vue'));

        $this->assertStringContainsString('id="mobileCreateActionSheet"', $navbarContent);
        $this->assertStringContainsString('Create New', $navbarContent);
        $this->assertStringContainsString('Post', $navbarContent);
        $this->assertStringContainsString('Story', $navbarContent);
        $this->assertStringContainsString('Reel', $navbarContent);
        $this->assertStringContainsString('Live', $navbarContent);
        $this->assertStringContainsString('Page', $navbarContent);
        $this->assertStringContainsString('Group', $navbarContent);
        $this->assertStringContainsString('Listing', $navbarContent);
    }

    public function test_header_responsiveness_and_safe_area_insets(): void
    {
        $user = User::factory()->create([
            'username' => 'header_test_user',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->get('/app');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Check for viewport-fit=cover and responsive header
        $this->assertStringContainsString('viewport-fit=cover', $content);
        $this->assertStringContainsString('mobile-bottom-nav', $content);
        $this->assertStringContainsString('mobileMenuDrawer', $content);

        $navbarContent = file_get_contents(resource_path('js/Components/Navbar.vue'));
        $this->assertStringContainsString('SocialHub', $navbarContent);
        $this->assertStringContainsString('Search...', $navbarContent);
        $this->assertStringContainsString('mobile-menu-toggle-btn', $navbarContent);
    }
}
