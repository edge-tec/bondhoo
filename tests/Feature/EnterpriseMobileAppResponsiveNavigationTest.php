<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseMobileAppResponsiveNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_dashboard_renders_mobile_navigation_and_responsive_assets(): void
    {
        $user = User::factory()->create([
            'username' => 'mobileuser',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->get('/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Viewport & PWA tags
        $this->assertStringContainsString('viewport-fit=cover', $content);
        $this->assertStringContainsString('mobile-web-app-capable', $content);
        $this->assertStringContainsString('enterprise-mobile-app.css', $content);

        // Mobile Bottom Navigation
        $this->assertStringContainsString('mobile-bottom-nav', $content);
        $this->assertStringContainsString('mobile-nav-btn', $content);
        $this->assertStringContainsString('mobileNavCreateBtn', $content);

        // Mobile Create Action Sheet
        $this->assertStringContainsString('mobileCreateActionSheet', $content);
        $this->assertStringContainsString('openMobileCreateSheet', $content);
        $this->assertStringContainsString('handleMobileCreatePost', $content);

        // Mobile Menu Drawer
        $this->assertStringContainsString('mobileMenuDrawer', $content);
        $this->assertStringContainsString('openMobileMenuDrawer', $content);
        $this->assertStringContainsString('mobile-menu-toggle-btn', $content);

        // Touch and safe area indicators
        $this->assertStringContainsString('updateMobileNavBadges', $content);
    }

    public function test_profile_page_renders_mobile_navigation_and_protects_admin_console(): void
    {
        $regularUser = User::factory()->create([
            'username' => 'regularprofileuser',
            'status' => 'active',
        ]);

        $response = $this->actingAs($regularUser, 'web')->get(getUserProfileUrl($regularUser));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Responsive tags & CSS
        $this->assertStringContainsString('viewport-fit=cover', $content);
        $this->assertStringContainsString('enterprise-mobile-app.css', $content);

        // Mobile Bottom Nav & Drawer
        $this->assertStringContainsString('mobile-bottom-nav', $content);
        $this->assertStringContainsString('mobileMenuDrawer', $content);

        // Ensure regular user does NOT see security console in profile header
        $this->assertStringNotContainsString('সিকিউরিটি কনসোল', $content);
    }

    public function test_app_layout_views_render_mobile_navigation_components(): void
    {
        $user = User::factory()->create([
            'username' => 'layoutuser',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->get('/friends');

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('viewport-fit=cover', $content);
        $this->assertStringContainsString('enterprise-mobile-app.css', $content);
        $this->assertStringContainsString('mobile-bottom-nav', $content);
        $this->assertStringContainsString('mobileMenuDrawer', $content);
        $this->assertStringContainsString('mobileCreateActionSheet', $content);
    }
}
