<?php

namespace Tests\Feature;

use App\Models\PrivacySetting;
use App\Models\ProfileView;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\ProfileAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class Step17ProfileViewsAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(array $attributes = []): User
    {
        static $counter = 800;
        $counter++;

        $user = User::factory()->create(array_merge([
            'username' => "analytics_user_{$counter}",
            'name' => "Analytics User {$counter}",
            'email' => "analytics_{$counter}@example.com",
            'phone' => "+880172200{$counter}",
            'status' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ], $attributes));

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => 'User bio',
                'avatar_url' => "https://cdn.jugajug.com/avatar_{$counter}.jpg",
            ]
        );

        PrivacySetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'profile_view_visibility' => 'public',
            ]
        );

        return $user;
    }

    public function test_owner_viewing_own_profile_is_not_counted(): void
    {
        $owner = $this->createUser();

        /** @var ProfileAnalyticsService $service */
        $service = app(ProfileAnalyticsService::class);
        $recorded = $service->recordView($owner, $owner);

        $this->assertFalse($recorded);
        $this->assertEquals(0, ProfileView::where('user_id', $owner->id)->count());
    }

    public function test_authenticated_non_owner_view_is_tracked(): void
    {
        $owner = $this->createUser(['username' => 'creator_bob']);
        $viewer = $this->createUser(['username' => 'viewer_alice']);

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/profile/{$owner->username}/view");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['recorded' => true],
            ]);

        $this->assertDatabaseHas('profile_views', [
            'user_id' => $owner->id,
            'viewer_id' => $viewer->id,
            'is_anonymous' => false,
        ]);

        $view = ProfileView::where('user_id', $owner->id)->first();
        $this->assertNotNull($view->ip_hash);
        $this->assertEquals(64, strlen($view->ip_hash));
    }

    public function test_anonymous_guest_view_is_tracked(): void
    {
        $owner = $this->createUser(['username' => 'public_star']);

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)',
        ])->postJson("/api/v2/profile/{$owner->username}/view");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['recorded' => true],
            ]);

        $this->assertDatabaseHas('profile_views', [
            'user_id' => $owner->id,
            'viewer_id' => null,
            'is_anonymous' => true,
            'device_type' => 'mobile',
        ]);
    }

    public function test_artificial_view_inflation_prevention_via_cooldown(): void
    {
        $owner = $this->createUser(['username' => 'trend_target']);
        $viewer = $this->createUser(['username' => 'rapid_refresher']);

        // First view succeeds
        $res1 = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/profile/{$owner->username}/view");
        $res1->assertStatus(200)
            ->assertJson(['data' => ['recorded' => true]]);

        $this->assertEquals(1, ProfileView::where('user_id', $owner->id)->count());

        // Repeated view immediately within cooldown window must NOT inflate view count
        $res2 = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/profile/{$owner->username}/view");
        $res2->assertStatus(200)
            ->assertJson(['data' => ['recorded' => false]]);

        $this->assertEquals(1, ProfileView::where('user_id', $owner->id)->count());
    }

    public function test_device_category_detection(): void
    {
        $owner = $this->createUser();
        $service = app(ProfileAnalyticsService::class);

        // Mobile request
        $mobileReq = Request::create('/profile', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
        ]);
        $this->assertEquals('mobile', $service->parseDeviceCategory($mobileReq));

        // Tablet request
        $tabletReq = Request::create('/profile', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPad; CPU OS 15_0 like Mac OS X)',
        ]);
        $this->assertEquals('tablet', $service->parseDeviceCategory($tabletReq));

        // Desktop request
        $desktopReq = Request::create('/profile', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);
        $this->assertEquals('desktop', $service->parseDeviceCategory($desktopReq));
    }

    public function test_discovery_source_detection(): void
    {
        $service = app(ProfileAnalyticsService::class);

        // Feed
        $feedReq = Request::create('/profile', 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://jugajug.com/feed',
        ]);
        $this->assertEquals('feed', $service->parseDiscoverySource($feedReq));

        // Search
        $searchReq = Request::create('/profile', 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://jugajug.com/search?q=developer',
        ]);
        $this->assertEquals('search', $service->parseDiscoverySource($searchReq));

        // Direct
        $directReq = Request::create('/profile', 'GET');
        $this->assertEquals('direct', $service->parseDiscoverySource($directReq));

        // External
        $extReq = Request::create('/profile', 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://techcrunch.com/article/social-media',
        ]);
        $this->assertEquals('external', $service->parseDiscoverySource($extReq));
    }

    public function test_privacy_masking_for_viewers_with_only_me_visibility(): void
    {
        $owner = $this->createUser(['username' => 'celebrity']);
        $privateViewer = $this->createUser(['username' => 'incognito_user', 'name' => 'Secret Agent']);
        $publicViewer = $this->createUser(['username' => 'open_user', 'name' => 'Open Citizen']);

        // Set private viewer privacy to only_me
        $privateViewer->privacySettings()->update(['profile_view_visibility' => 'only_me']);

        // Record views directly in DB to bypass cooldown between different users
        ProfileView::create([
            'user_id' => $owner->id,
            'viewer_id' => $privateViewer->id,
            'is_anonymous' => true,
            'ip_hash' => hash('sha256', 'ip1'),
            'device_type' => 'desktop',
            'source' => 'direct',
            'viewed_at' => now()->subMinutes(10),
        ]);

        ProfileView::create([
            'user_id' => $owner->id,
            'viewer_id' => $publicViewer->id,
            'is_anonymous' => false,
            'ip_hash' => hash('sha256', 'ip2'),
            'device_type' => 'mobile',
            'source' => 'feed',
            'viewed_at' => now()->subMinutes(5),
        ]);

        $service = app(ProfileAnalyticsService::class);
        $analytics = $service->computeAnalytics($owner, '7d');

        $recent = $analytics['recent_viewers'];
        $this->assertCount(2, $recent);

        // Public viewer is visible
        $publicEntry = collect($recent)->firstWhere('username', 'open_user');
        $this->assertNotNull($publicEntry);
        $this->assertFalse($publicEntry['is_anonymous']);
        $this->assertEquals('Open Citizen', $publicEntry['name']);

        // Private viewer identity is masked
        $maskedEntry = collect($recent)->firstWhere('is_anonymous', true);
        $this->assertNotNull($maskedEntry);
        $this->assertContains($maskedEntry['name'], ['Bondhoo Member', 'Jugajug Member']);
        $this->assertNull($maskedEntry['username']);
        $this->assertNull($maskedEntry['avatar_url']);
    }

    public function test_owner_dashboard_analytics_endpoints(): void
    {
        $owner = $this->createUser();
        $viewer1 = $this->createUser();
        $viewer2 = $this->createUser();

        // Populate sample views
        ProfileView::create([
            'user_id' => $owner->id,
            'viewer_id' => $viewer1->id,
            'is_anonymous' => false,
            'ip_hash' => 'hash1',
            'device_type' => 'mobile',
            'source' => 'feed',
            'viewed_at' => now(),
        ]);
        ProfileView::create([
            'user_id' => $owner->id,
            'viewer_id' => $viewer2->id,
            'is_anonymous' => false,
            'ip_hash' => 'hash2',
            'device_type' => 'desktop',
            'source' => 'search',
            'viewed_at' => now()->subDay(),
        ]);
        ProfileView::create([
            'user_id' => $owner->id,
            'viewer_id' => null,
            'is_anonymous' => true,
            'ip_hash' => 'hash3',
            'device_type' => 'desktop',
            'source' => 'direct',
            'viewed_at' => now()->subDays(2),
        ]);

        // API v1
        $resV1 = $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/profile/analytics?timeframe=7d');

        $resV1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_views' => 3,
                    'unique_viewers_count' => 3,
                    'views_today' => 1,
                    'timeframe' => '7d',
                    'device_breakdown' => [
                        'mobile' => 1,
                        'desktop' => 2,
                        'tablet' => 0,
                    ],
                    'discovery_sources' => [
                        'feed' => 1,
                        'search' => 1,
                        'direct' => 1,
                    ],
                ],
            ]);

        // API v2
        $resV2 = $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v2/profile/analytics?timeframe=7d');

        $resV2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_views' => 3,
                    'unique_viewers_count' => 3,
                ],
            ]);
    }

    public function test_analytics_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/profile/analytics')->assertStatus(401);
        $this->getJson('/api/v2/profile/analytics')->assertStatus(401);
    }
}
