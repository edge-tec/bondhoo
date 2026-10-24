<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageAuditLog;
use App\Models\PageFollower;
use App\Models\PageMember;
use App\Models\Post;
use App\Models\PostAnalytics;
use App\Models\Reel;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PageInsightsAndAnalyticsPart10Test extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $admin;

    protected User $analyst;

    protected User $viewer;

    protected User $stranger;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'name' => 'Tech Owner',
            'email' => 'owner@insights.test',
            'username' => 'techowner',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@insights.test',
            'username' => 'techadmin',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->analyst = User::factory()->create([
            'name' => 'Data Analyst',
            'email' => 'analyst@insights.test',
            'username' => 'techanalyst',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->viewer = User::factory()->create([
            'name' => 'Basic Viewer',
            'email' => 'viewer@insights.test',
            'username' => 'techviewer',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->stranger = User::factory()->create([
            'name' => 'Stranger User',
            'email' => 'stranger@insights.test',
            'username' => 'stranger',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->page = Page::create([
            'owner_id' => $this->owner->id,
            'name' => 'Verifiable Analytics Page',
            'slug' => 'verifiable-analytics-page',
            'username' => 'verifiablepage',
            'category' => 'Technology',
            'status' => Page::STATUS_ACTIVE,
        ]);

        // Assign page team roles
        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->admin->id,
            'role' => Page::ROLE_ADMIN,
            'status' => 'active',
        ]);

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->analyst->id,
            'role' => Page::ROLE_ANALYST,
            'status' => 'active',
        ]);

        PageMember::create([
            'page_id' => $this->page->id,
            'user_id' => $this->viewer->id,
            'role' => Page::ROLE_VIEWER,
            'status' => 'active',
        ]);
    }

    /**
     * Test 1: Access control — Only users with analytics.view permission can access analytics.
     */
    public function test_unauthorized_user_cannot_access_page_analytics(): void
    {
        // Unauthenticated guest
        $this->getJson("/pages/{$this->page->slug}/analytics")
            ->assertStatus(401);

        // Stranger with no role
        $this->actingAs($this->stranger)
            ->getJson("/pages/{$this->page->slug}/analytics")
            ->assertStatus(403);

        // Member with ROLE_VIEWER (only has page.view)
        $this->actingAs($this->viewer)
            ->getJson("/pages/{$this->page->slug}/analytics")
            ->assertStatus(403);

        // Member with ROLE_ANALYST (has analytics.view)
        $this->actingAs($this->analyst)
            ->getJson("/pages/{$this->page->slug}/analytics")
            ->assertStatus(200);

        // Member with ROLE_ADMIN
        $this->actingAs($this->admin)
            ->getJson("/pages/{$this->page->slug}/analytics")
            ->assertStatus(200);

        // Page Owner
        $this->actingAs($this->owner)
            ->getJson("/pages/{$this->page->slug}/analytics")
            ->assertStatus(200);
    }

    /**
     * Test 2: Metric calculations against known persisted test records (Zero Fabrication).
     */
    public function test_verifiable_metric_calculations_against_known_test_records(): void
    {
        // Seed 4 followers
        for ($i = 0; $i < 4; $i++) {
            $user = User::factory()->create();
            PageFollower::create([
                'page_id' => $this->page->id,
                'user_id' => $user->id,
                'created_at' => now()->subDays(2),
            ]);
        }

        // Post 1: has PostAnalytics with impressions=400, reach=150, clicks=12
        $post1 = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'First verified post with analytics',
            'type' => 'text',
            'status' => 'published',
            'likes_count' => 20,
            'comments_count' => 8,
            'shares_count' => 4,
            'created_at' => now()->subDays(5),
        ]);

        PostAnalytics::create([
            'post_id' => $post1->id,
            'impressions_count' => 400,
            'unique_reach' => 150,
            'clicks_count' => 12,
            'engagement_score' => 32.0,
        ]);

        // Post 2: has PostAnalytics with impressions=600, reach=250, clicks=18
        $post2 = Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Second verified post with analytics',
            'type' => 'text',
            'status' => 'published',
            'likes_count' => 30,
            'comments_count' => 12,
            'shares_count' => 6,
            'created_at' => now()->subDays(3),
        ]);

        PostAnalytics::create([
            'post_id' => $post2->id,
            'impressions_count' => 600,
            'unique_reach' => 250,
            'clicks_count' => 18,
            'engagement_score' => 48.0,
        ]);

        // Reel with 75 views
        Reel::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'caption' => 'Verified Reel',
            'views_count' => 75,
            'status' => Reel::STATUS_READY,
            'is_draft' => false,
            'created_at' => now()->subDays(2),
        ]);

        // Story with 30 views
        Story::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'type' => 'text',
            'content' => 'Verified Story',
            'views_count' => 30,
            'status' => Story::STATUS_READY,
            'created_at' => now()->subHours(5),
        ]);

        $res = $this->actingAs($this->owner)
            ->getJson("/pages/{$this->page->slug}/analytics?period=30d");

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total_followers', 4)
            ->assertJsonPath('data.summary.new_followers', 4)
            ->assertJsonPath('data.summary.impressions', 1000) // 400 + 600
            ->assertJsonPath('data.summary.reach', 400) // 150 + 250
            ->assertJsonPath('data.summary.total_likes', 50) // 20 + 30
            ->assertJsonPath('data.summary.total_comments', 20) // 8 + 12
            ->assertJsonPath('data.summary.total_shares', 10) // 4 + 6
            ->assertJsonPath('data.summary.link_clicks', 30) // 12 + 18
            ->assertJsonPath('data.summary.total_engagements', 110) // 50 + 20 + 10 + 30
            ->assertJsonPath('data.summary.reel_views', 75)
            ->assertJsonPath('data.summary.story_views', 30);

        // Engagement rate = (110 / 400) * 100 = 27.5%
        $this->assertEquals('27.5%', $res->json('data.summary.engagement_rate'));

        // Check definitions are provided
        $res->assertJsonStructure([
            'data' => [
                'definitions' => [
                    'impressions',
                    'reach',
                    'engagement',
                    'engagement_rate',
                    'reactions',
                    'comments',
                    'shares',
                    'link_clicks',
                    'video_views',
                    'reel_views',
                    'story_views',
                    'follower_growth',
                ],
                'summary',
                'timeseries' => [
                    'follower_growth',
                    'publishing_cadence',
                ],
                'top_content',
            ],
        ]);
    }

    /**
     * Test 3: Date range filters and zero fabrication on missing historical data.
     */
    public function test_date_range_filters_and_zero_fabrication_on_missing_data(): void
    {
        // Empty page with no activity
        $emptyPage = Page::create([
            'owner_id' => $this->owner->id,
            'name' => 'Zero Activity Page',
            'slug' => 'zero-activity-page',
            'category' => 'Business',
            'status' => Page::STATUS_ACTIVE,
        ]);

        $res = $this->actingAs($this->owner)
            ->getJson("/pages/{$emptyPage->slug}/analytics?period=7d");

        $res->assertStatus(200);
        $summary = $res->json('data.summary');

        // Verify zero fabrication
        $this->assertEquals(0, $summary['total_followers']);
        $this->assertEquals(0, $summary['new_followers']);
        $this->assertEquals(0, $summary['impressions']);
        $this->assertEquals(0, $summary['reach']);
        $this->assertEquals(0, $summary['total_engagements']);
        $this->assertEquals(0, $summary['link_clicks']);
        $this->assertEquals('0%', $summary['engagement_rate']);

        // Custom historical date range with no records
        $customRes = $this->actingAs($this->owner)
            ->getJson("/pages/{$emptyPage->slug}/analytics?start_date=2024-01-01&end_date=2024-01-07");

        $customRes->assertStatus(200);
        $customSummary = $customRes->json('data.summary');
        $this->assertEquals(0, $customSummary['new_followers']);
        $this->assertEquals(0, $customSummary['posts_in_period']);
        $this->assertEquals(7, $customRes->json('data.date_range.days'));
    }

    /**
     * Test 4: Timezone-aware date boundaries and daily grouping.
     */
    public function test_timezone_boundaries_grouping(): void
    {
        // Event created at 2026-10-18 20:30:00 UTC
        // In Asia/Dhaka (+06:00), this is 2026-10-19 02:30:00
        $followerUser = User::factory()->create();
        PageFollower::create([
            'page_id' => $this->page->id,
            'user_id' => $followerUser->id,
            'created_at' => Carbon::parse('2026-10-18 20:30:00', 'UTC'),
        ]);

        // Query in UTC
        $utcRes = $this->actingAs($this->owner)
            ->getJson("/pages/{$this->page->slug}/analytics?start_date=2026-10-18&end_date=2026-10-19&timezone=UTC");

        $utcRes->assertStatus(200);
        $utcGrowth = $utcRes->json('data.timeseries.follower_growth');
        $utcCountFor18 = collect($utcGrowth)->firstWhere('date', '2026-10-18')['count'] ?? 0;
        $this->assertEquals(1, $utcCountFor18);

        // Query in Asia/Dhaka
        $dhakaRes = $this->actingAs($this->owner)
            ->getJson("/pages/{$this->page->slug}/analytics?start_date=2026-10-18&end_date=2026-10-19&timezone=Asia/Dhaka");

        $dhakaRes->assertStatus(200);
        $dhakaGrowth = $dhakaRes->json('data.timeseries.follower_growth');
        $dhakaCountFor19 = collect($dhakaGrowth)->firstWhere('date', '2026-10-19')['count'] ?? 0;
        $this->assertEquals(1, $dhakaCountFor19);
    }

    /**
     * Test 5: Duplicate events and deduplication for story views.
     */
    public function test_duplicate_events_and_deduplication(): void
    {
        $story = Story::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'type' => 'text',
            'content' => 'Deduplicated Story',
            'views_count' => 0,
            'status' => Story::STATUS_READY,
            'expires_at' => now()->addHours(12),
        ]);

        $viewerUser = User::factory()->create();

        // Viewer records view 1st time
        $res1 = $this->actingAs($viewerUser)
            ->postJson("/pages/{$this->page->slug}/stories/{$story->id}/view");
        $res1->assertStatus(200)
            ->assertJsonPath('views_count', 1);

        // Viewer records view 2nd time (duplicate event should NOT double increment)
        $res2 = $this->actingAs($viewerUser)
            ->postJson("/pages/{$this->page->slug}/stories/{$story->id}/view");
        $res2->assertStatus(200)
            ->assertJsonPath('views_count', 1);

        $this->assertEquals(1, StoryView::where('story_id', $story->id)->where('user_id', $viewerUser->id)->count());
    }

    /**
     * Test 6: Real Recent Activity and Audit Log records with pagination and authorization.
     */
    public function test_recent_activity_audit_logs_and_pagination(): void
    {
        // Generate real audit log entries via mutations
        PageAuditLog::create([
            'page_id' => $this->page->id,
            'actor_id' => $this->owner->id,
            'action' => 'team.invite',
            'target_type' => 'User',
            'target_id' => $this->admin->id,
            'created_at' => now()->subMinutes(10),
            'ip_address' => '127.0.0.1',
        ]);

        PageAuditLog::create([
            'page_id' => $this->page->id,
            'actor_id' => $this->owner->id,
            'action' => 'settings.update',
            'target_type' => 'Page',
            'target_id' => $this->page->id,
            'created_at' => now()->subMinutes(5),
            'ip_address' => '127.0.0.1',
        ]);

        // Unauthorized user cannot read audit logs
        $this->actingAs($this->stranger)
            ->getJson("/pages/{$this->page->slug}/audit-logs")
            ->assertStatus(403);

        // Authorized manager can read paginated audit logs
        $res = $this->actingAs($this->owner)
            ->getJson("/pages/{$this->page->slug}/audit-logs?per_page=1");

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2);
        $this->assertCount(1, $res->json('data'));

        // Analytics endpoint includes recent_activity stream
        $analyticsRes = $this->actingAs($this->owner)
            ->getJson("/pages/{$this->page->slug}/analytics");

        $analyticsRes->assertStatus(200);
        $recentActivity = $analyticsRes->json('data.recent_activity');
        $this->assertNotEmpty($recentActivity);
        $this->assertArrayHasKey('description', $recentActivity[0]);
        $this->assertArrayHasKey('action', $recentActivity[0]);
        $this->assertArrayHasKey('time_ago', $recentActivity[0]);
    }

    /**
     * Test 7: Input validation for date filters and invalid parameters.
     */
    public function test_date_filter_validation(): void
    {
        // end_date before start_date
        $this->actingAs($this->owner)
            ->getJson("/pages/{$this->page->slug}/analytics?start_date=2026-10-15&end_date=2026-10-10")
            ->assertStatus(422);

        // invalid period string
        $this->actingAs($this->owner)
            ->getJson("/pages/{$this->page->slug}/analytics?period=invalid_period")
            ->assertStatus(422);
    }

    /**
     * Test 8: Publishing performance over time and content type breakdown.
     */
    public function test_publishing_performance_and_content_breakdown(): void
    {
        // Create 2 text posts, 1 photo post, 1 video post
        Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Text 1',
            'type' => 'text',
            'status' => 'published',
            'created_at' => now()->subDays(3),
        ]);

        Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Text 2',
            'type' => 'text',
            'status' => 'published',
            'created_at' => now()->subDays(2),
        ]);

        Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Photo Post',
            'type' => 'photo',
            'status' => 'published',
            'created_at' => now()->subDays(1),
        ]);

        Post::create([
            'user_id' => $this->owner->id,
            'page_id' => $this->page->id,
            'content' => 'Video Post',
            'type' => 'video',
            'status' => 'published',
            'created_at' => now(),
        ]);

        $res = $this->actingAs($this->owner)
            ->getJson("/pages/{$this->page->slug}/analytics?period=7d");

        $res->assertStatus(200);
        $breakdown = $res->json('data.publishing_breakdown');

        $this->assertEquals(2, $breakdown['text']);
        $this->assertEquals(1, $breakdown['photo']);
        $this->assertEquals(1, $breakdown['video']);
        $this->assertEquals(4, $breakdown['total']);

        $cadence = $res->json('data.timeseries.publishing_cadence');
        $this->assertNotEmpty($cadence);
        $totalPublishedInCadence = collect($cadence)->sum('count');
        $this->assertEquals(4, $totalPublishedInCadence);
    }

    /**
     * Test 9: Web Manage Dashboard view loads with real analytics dataset and activity.
     */
    public function test_web_manage_dashboard_view_loads_with_verifiable_metrics(): void
    {
        $response = $this->actingAs($this->owner)
            ->get("/pages/{$this->page->slug}/manage?tab=overview");

        $response->assertStatus(200);
        $response->assertSee('Page Performance');
        $response->assertSee('Post Reach');
        $response->assertSee('Unique Reach');
        $response->assertSee('Recent Activity');
        $response->assertSee('epModalAuditActivityHistory');
        $response->assertSee('epStudioTimeRangeSelect');
    }
}
