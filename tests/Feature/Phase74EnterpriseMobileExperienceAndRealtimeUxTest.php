<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase74EnterpriseMobileExperienceAndRealtimeUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_mobile_drawer_and_bottom_nav_stacking_isolation_and_cache_busting(): void
    {
        $user = User::factory()->create([
            'username' => 'phase74_mobile_user',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'web')->get('/app');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringContainsString('/css/enterprise-mobile-app.css?v=', $html);

        $navbarContent = file_get_contents(resource_path('js/Components/Navbar.vue'));
        $this->assertStringContainsString('z-[99999]', $navbarContent);
        $this->assertStringContainsString('style="z-index: 99999;"', $navbarContent);
        $this->assertStringContainsString('grid-template-columns: repeat(5, minmax(0, 1fr))', $navbarContent);

        $cssContent = file_get_contents(public_path('css/enterprise-mobile-app.css'));
        $this->assertStringContainsString('body:not(.inertia-app) .mobile-bottom-nav', $cssContent);
        $this->assertStringContainsString('body:not(.inertia-app) .mobile-menu-toggle-btn', $cssContent);
        $this->assertStringContainsString('body.inertia-app #mobileMenuDrawer', $cssContent);

        $swContent = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString("const CACHE_NAME = 'bondhoo-pwa-v7.4';", $swContent);
    }

    public function test_realtime_notification_and_message_badge_synchronization_hooks(): void
    {
        $navbarContent = file_get_contents(resource_path('js/Components/Navbar.vue'));
        $appLayoutContent = file_get_contents(resource_path('js/Layouts/AppLayout.vue'));
        $notificationsContent = file_get_contents(resource_path('js/Pages/Notifications.vue'));
        $messengerContent = file_get_contents(resource_path('js/Pages/Messenger.vue'));

        $this->assertStringContainsString('jugajug:unread-updated', $navbarContent);
        $this->assertStringContainsString('jugajug:messages-unread-updated', $navbarContent);
        $this->assertStringContainsString('liveUnreadCount', $navbarContent);
        $this->assertStringContainsString('liveUnreadMessagesCount', $navbarContent);

        $this->assertStringContainsString('jugajug:unread-updated', $appLayoutContent);
        $this->assertStringContainsString('jugajug:messages-unread-updated', $appLayoutContent);

        $this->assertStringContainsString('jugajug:unread-updated', $notificationsContent);
        $this->assertStringContainsString('syncUnreadBadge', $notificationsContent);

        $this->assertStringContainsString('jugajug:messages-unread-updated', $messengerContent);
        $this->assertStringContainsString('h-[calc(100dvh-8.5rem)]', $messengerContent);
    }

    public function test_feed_supports_saved_memories_events_tabs_and_search_filtering(): void
    {
        $viewer = User::factory()->create([
            'username' => 'phase74_viewer',
            'name' => 'Phase74 Viewer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $otherUser = User::factory()->create([
            'username' => 'phase74_other',
            'name' => 'Other Author',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        Post::factory()->create([
            'user_id' => $viewer->id,
            'content' => 'My personal memory milestone post',
            'audience' => 'public',
            'status' => 'published',
        ]);

        Post::factory()->create([
            'user_id' => $otherUser->id,
            'content' => 'UniqueSearchKeyword74 in public feed',
            'audience' => 'public',
            'status' => 'published',
        ]);

        // 1. Saved route redirects to /dashboard?tab=saved and renders Feed with activeTab=saved
        $savedRedirect = $this->actingAs($viewer, 'web')->get('/saved');
        $savedRedirect->assertRedirect('/dashboard?tab=saved');

        $savedResponse = $this->actingAs($viewer, 'web')->get('/dashboard?tab=saved');
        $savedResponse->assertStatus(200);
        $savedResponse->assertInertia(fn (Assert $page) => $page
            ->component('Feed')
            ->where('activeTab', 'saved')
        );

        // 2. Memories route filters to viewer's own posts
        $memoriesResponse = $this->actingAs($viewer, 'web')->get('/dashboard?tab=memories');
        $memoriesResponse->assertStatus(200);
        $memoriesResponse->assertInertia(fn (Assert $page) => $page
            ->component('Feed')
            ->where('activeTab', 'memories')
            ->has('posts.data', 1)
        );

        // 3. Search query filters matching posts
        $searchResponse = $this->actingAs($viewer, 'web')->get('/search?q=UniqueSearchKeyword74');
        $searchResponse->assertStatus(200);
        $searchResponse->assertInertia(fn (Assert $page) => $page
            ->component('Feed')
            ->where('searchQuery', 'UniqueSearchKeyword74')
            ->has('posts.data', 1)
        );
    }

    public function test_desktop_sidebar_left_see_more_matches_mobile_drawer_navigation_parity(): void
    {
        $sidebarContent = file_get_contents(resource_path('js/Components/SidebarLeft.vue'));

        $this->assertStringContainsString('v-if="showMore"', $sidebarContent);
        $this->assertStringContainsString('href="/messages"', $sidebarContent);
        $this->assertStringContainsString('href="/notifications"', $sidebarContent);
        $this->assertStringContainsString('href="/settings"', $sidebarContent);
        $this->assertStringContainsString('href="/terms"', $sidebarContent);
    }

    public function test_reels_viewer_modal_and_reels_page_support_auto_advance_and_mobile_swipe_gestures(): void
    {
        $modalContent = file_get_contents(resource_path('js/Components/Reels/ReelViewerModal.vue'));
        $reelsPageContent = file_get_contents(resource_path('js/Pages/Reels.vue'));

        // Verify auto-advance on video ended & timeupdate in both ReelViewerModal and Reels page
        $this->assertStringContainsString('@ended="onVideoEnded"', $modalContent);
        $this->assertStringContainsString('isAutoAdvanceEnabled', $modalContent);
        $this->assertStringContainsString('toggleAutoAdvance', $modalContent);
        $this->assertStringContainsString('playActiveVideo', $modalContent);

        $this->assertStringContainsString('@ended="onVideoEnded"', $reelsPageContent);
        $this->assertStringContainsString('isAutoAdvanceEnabled', $reelsPageContent);
        $this->assertStringContainsString('autoAdvancedCurrentReel', $reelsPageContent);

        // Verify touch + mouse/pointer gesture support and interactive target filtering
        $this->assertStringContainsString('isInteractiveTarget', $modalContent);
        $this->assertStringContainsString('@mousedown="handleMouseDown"', $modalContent);
        $this->assertStringContainsString('isInteractiveTarget', $reelsPageContent);
        $this->assertStringContainsString('@mousedown="handleMouseDown"', $reelsPageContent);
    }
}
