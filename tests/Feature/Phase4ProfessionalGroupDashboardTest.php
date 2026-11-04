<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Group;
use App\Models\GroupCategory;
use App\Models\GroupEvent;
use App\Models\GroupMember;
use App\Models\GroupReport;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase4ProfessionalGroupDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        GroupCategory::firstOrCreate(
            ['slug' => 'technology'],
            [
                'name' => 'Technology',
                'description' => 'Tech discussions, software and engineering.',
                'icon' => 'Cpu',
                'color' => '#3B82F6',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }

    public function test_authorized_group_owner_can_fetch_dashboard_analytics_with_real_records(): void
    {
        $owner = User::factory()->create([
            'name' => 'Tech Lead Owner',
            'username' => 'techlead',
            'email' => 'techlead@connectly.test',
        ]);

        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Enterprise Cloud Leadership',
            'username' => 'cloud-leadership',
            'slug' => 'enterprise-cloud-leadership',
            'description' => 'A senior community for engineering directors and architects.',
            'category' => 'Technology',
            'privacy' => 'public',
            'members_count' => 3,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now()->subDays(10),
        ]);

        // Add 2 active members
        $member1 = User::factory()->create(['name' => 'Alice Engineer']);
        $member2 = User::factory()->create(['name' => 'Bob DevOps']);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $member1->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now()->subDays(5),
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $member2->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now()->subDays(2),
        ]);

        // Add 1 pending applicant
        $applicant = User::factory()->create(['name' => 'Charlie Applicant']);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $applicant->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_PENDING,
            'joined_at' => now(),
        ]);

        // Add Posts
        $post1 = Post::create([
            'user_id' => $owner->id,
            'group_id' => $group->id,
            'content' => 'Microservices Architecture vs Modular Monolith in 2026',
            'status' => 'published',
            'likes_count' => 45,
            'comments_count' => 12,
            'shares_count' => 8,
        ]);

        $post2 = Post::create([
            'user_id' => $member1->id,
            'group_id' => $group->id,
            'content' => 'Optimizing Kubernetes cost in Multi-Cloud clusters',
            'status' => 'published',
            'likes_count' => 20,
            'comments_count' => 5,
            'shares_count' => 3,
        ]);

        // Add Comments
        Comment::create([
            'post_id' => $post1->id,
            'user_id' => $member1->id,
            'body' => 'Great insights on domain-driven boundaries!',
        ]);

        Comment::create([
            'post_id' => $post1->id,
            'user_id' => $member2->id,
            'body' => 'We adopted modular monolith with great developer velocity.',
        ]);

        // Add 1 Pending Post
        Post::create([
            'user_id' => $member2->id,
            'group_id' => $group->id,
            'content' => 'Draft post waiting for admin approval',
            'status' => 'pending_approval',
        ]);

        // Add Event
        GroupEvent::create([
            'group_id' => $group->id,
            'creator_id' => $owner->id,
            'title' => 'Cloud Summit 2026',
            'location' => 'Virtual Zoom',
            'start_time' => now()->addDays(14),
            'end_time' => now()->addDays(14)->addHours(3),
            'status' => 'published',
        ]);

        // Add Report
        GroupReport::create([
            'group_id' => $group->id,
            'reporter_id' => $member2->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post2->id,
            'reason_category' => 'spam',
            'description' => 'Possible spam link in comment',
            'status' => GroupReport::STATUS_PENDING,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/groups/{$group->id}/dashboard");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.overview.total_members', 3)
            ->assertJsonPath('data.overview.new_members', 3)
            ->assertJsonPath('data.overview.pending_join_requests', 1)
            ->assertJsonPath('data.overview.total_posts', 2)
            ->assertJsonPath('data.overview.pending_posts', 1)
            ->assertJsonPath('data.overview.total_comments', 2)
            ->assertJsonPath('data.overview.total_reactions', 65)
            ->assertJsonPath('data.overview.total_shares', 11)
            ->assertJsonPath('data.overview.total_events', 1)
            ->assertJsonPath('data.overview.reported_content', 1)
            ->assertJsonStructure([
                'data' => [
                    'overview' => [
                        'total_members',
                        'new_members',
                        'active_members',
                        'online_members',
                        'pending_join_requests',
                        'total_posts',
                        'total_comments',
                        'total_reactions',
                        'total_shares',
                        'total_events',
                        'pending_posts',
                        'reported_content',
                        'engagement_rate',
                        'member_growth',
                    ],
                    'charts' => [
                        'daily_member_growth',
                        'post_activity',
                        'comment_activity',
                        'engagement_trends',
                        'active_members_stats',
                        'top_performing_posts',
                        'most_active_members',
                        'member_retention',
                    ],
                    'quick_actions',
                    'filter',
                ],
            ]);

        $this->assertCount(10, $response->json('data.quick_actions'));
    }

    public function test_dashboard_analytics_date_filters_and_custom_range(): void
    {
        $owner = User::factory()->create();
        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Filtered Analytics Group',
            'username' => 'filtered-analytics',
            'slug' => 'filtered-analytics-group',
            'privacy' => 'public',
            'category' => 'Technology',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($owner);

        // 1. 7 Days Filter
        $resp7d = $this->getJson("/api/v1/groups/{$group->id}/analytics?period=7d");
        $resp7d->assertStatus(200)
            ->assertJsonPath('data.filter.period', '7d')
            ->assertJsonPath('data.filter.days', 7);

        // 2. 90 Days Filter
        $resp90d = $this->getJson("/api/v1/groups/{$group->id}/analytics?period=90d");
        $resp90d->assertStatus(200)
            ->assertJsonPath('data.filter.period', '90d')
            ->assertJsonPath('data.filter.days', 90);

        // 3. 12 Months Filter
        $resp12m = $this->getJson("/api/v1/groups/{$group->id}/analytics?period=12m");
        $resp12m->assertStatus(200)
            ->assertJsonPath('data.filter.period', '12m')
            ->assertJsonPath('data.filter.days', 365);

        // 4. Custom Date Range
        $respCustom = $this->getJson("/api/v1/groups/{$group->id}/analytics?period=custom&start_date=2026-06-01&end_date=2026-10-31");
        $respCustom->assertStatus(200)
            ->assertJsonPath('data.filter.period', 'custom')
            ->assertJsonPath('data.filter.start_date', '2026-06-01')
            ->assertJsonPath('data.filter.end_date', '2026-10-31');
    }

    public function test_top_performing_posts_and_most_active_members_ranking(): void
    {
        $owner = User::factory()->create(['name' => 'Ranking Admin']);
        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Performance Ranking Community',
            'username' => 'ranking-community',
            'slug' => 'performance-ranking-community',
            'privacy' => 'public',
            'category' => 'Technology',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        $contributor = User::factory()->create(['name' => 'Star Contributor']);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $contributor->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        // Post with low engagement
        Post::create([
            'user_id' => $owner->id,
            'group_id' => $group->id,
            'content' => 'Low engagement note',
            'status' => 'published',
            'likes_count' => 2,
            'comments_count' => 1,
            'shares_count' => 0,
        ]);

        // Post with high engagement
        $highEngagementPost = Post::create([
            'user_id' => $contributor->id,
            'group_id' => $group->id,
            'content' => 'Viral community post on AI Agents',
            'status' => 'published',
            'likes_count' => 150,
            'comments_count' => 45,
            'shares_count' => 25,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/groups/{$group->id}/dashboard");
        $response->assertStatus(200);

        $topPosts = $response->json('data.charts.top_performing_posts');
        $this->assertNotEmpty($topPosts);
        $this->assertEquals($highEngagementPost->id, $topPosts[0]['id']);
        $this->assertEquals(220, $topPosts[0]['total_engagement']);
    }

    public function test_regular_members_cannot_access_group_dashboard(): void
    {
        $owner = User::factory()->create();
        $regularMember = User::factory()->create();

        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Private Dashboard Group',
            'username' => 'priv-dash-group',
            'slug' => 'private-dashboard-group',
            'privacy' => 'public',
            'category' => 'Business',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $regularMember->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($regularMember);

        $responseDashboard = $this->getJson("/api/v1/groups/{$group->id}/dashboard");
        $responseDashboard->assertStatus(403);

        $responseAnalytics = $this->getJson("/api/v1/groups/{$group->id}/analytics");
        $responseAnalytics->assertStatus(403);
    }

    public function test_admin_with_delegated_permission_can_access_dashboard(): void
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create();

        $group = Group::create([
            'creator_id' => $owner->id,
            'name' => 'Delegated Permission Group',
            'username' => 'delegated-group',
            'slug' => 'delegated-permission-group',
            'privacy' => 'public',
            'category' => 'Business',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $manager->id,
            'role' => GroupMember::ROLE_MODERATOR,
            'permissions' => ['view_analytics'],
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($manager);

        $response = $this->getJson("/api/v1/groups/{$group->id}/dashboard");
        $response->assertStatus(200)->assertJsonPath('success', true);
    }
}
