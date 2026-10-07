<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupDiscussion;
use App\Models\GroupDiscussionReply;
use App\Models\GroupMember;
use App\Models\GroupMemberBadge;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Jugajug Enterprise Community & Groups V2 Test Suite
 * Covers V2 REST API, Discussions, Q&A, Health Score, Contributor Badges,
 * Subscriptions, Bookmarks, Custom Roles, and Negative Authorization.
 */
class EnterpriseCommunityV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_v2_community_with_advanced_identity_and_classification(): void
    {
        $creator = User::factory()->create();

        $payload = [
            'name' => 'Bangladeshi Open Source Guild',
            'username' => 'bd-opensource',
            'description' => 'A collaboration ecosystem for developers building open source in Bangladesh.',
            'community_type' => Group::TYPE_PROJECT,
            'category' => 'Technology',
            'tags' => ['opensource', 'php', 'laravel'],
            'language' => 'bn',
            'location' => 'Dhaka, Bangladesh',
            'requires_member_approval' => true,
        ];

        $response = $this->actingAs($creator, 'sanctum')
            ->postJson('/api/v2/groups', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Bangladeshi Open Source Guild')
            ->assertJsonPath('data.username', 'bd-opensource')
            ->assertJsonPath('data.community_type', Group::TYPE_PROJECT);

        $this->assertDatabaseHas('groups', [
            'name' => 'Bangladeshi Open Source Guild',
            'username' => 'bd-opensource',
            'community_type' => Group::TYPE_PROJECT,
            'creator_id' => $creator->id,
        ]);
    }

    public function test_v2_discover_returns_ranked_communities_with_explainable_signals(): void
    {
        $creator = User::factory()->create();
        $user = User::factory()->create();

        $group1 = Group::factory()->create([
            'creator_id' => $creator->id,
            'name' => 'High Health Tech Group',
            'community_type' => Group::TYPE_PUBLIC,
            'health_score' => 88,
            'members_count' => 120,
            'status' => Group::STATUS_ACTIVE,
        ]);

        $group2 = Group::factory()->create([
            'creator_id' => $creator->id,
            'name' => 'Emerging AI Group',
            'community_type' => Group::TYPE_INTEREST,
            'health_score' => 60,
            'members_count' => 25,
            'status' => Group::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/groups/discover');

        $response->assertStatus(200)
            ->assertJsonPath('meta.algorithm', 'jugajug-explainable-health-v2')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'health_score',
                        'discovery_reason',
                    ],
                ],
            ]);

        $items = $response->json('data');
        $this->assertNotEmpty($items);
        $this->assertEquals($group1->id, $items[0]['id']);
        $this->assertStringContainsString('High community activity', $items[0]['discovery_reason']);
    }

    public function test_community_health_score_calculation_and_endpoint(): void
    {
        $creator = User::factory()->create();
        $group = Group::factory()->create([
            'creator_id' => $creator->id,
            'privacy' => 'public',
            'community_type' => Group::TYPE_PUBLIC,
            'members_count' => 1,
            'active_members_count' => 1,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $creator->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Add 5 recent posts
        for ($i = 0; $i < 5; $i++) {
            Post::factory()->create([
                'user_id' => $creator->id,
                'group_id' => $group->id,
                'status' => 'published',
                'created_at' => now(),
            ]);
        }

        $response = $this->actingAs($creator, 'sanctum')
            ->getJson("/api/v2/groups/{$group->id}/health");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'health_score',
                    'health_metrics' => [
                        'active_ratio',
                        'weekly_posts',
                        'pending_reports',
                    ],
                ],
            ]);

        $score = $response->json('data.health_score');
        $this->assertGreaterThanOrEqual(60, $score);
    }

    public function test_discussions_flow_and_accepted_answer_marking(): void
    {
        $creator = User::factory()->create();
        $respondent = User::factory()->create();

        $group = Group::factory()->create([
            'creator_id' => $creator->id,
            'privacy' => 'public',
            'community_type' => Group::TYPE_PUBLIC,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $creator->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $respondent->id,
            'role' => 'member',
            'status' => 'active',
        ]);

        // 1. Creator posts a question
        $discRes = $this->actingAs($creator, 'sanctum')
            ->postJson("/api/v2/groups/{$group->id}/discussions", [
                'type' => 'question',
                'title' => 'How to optimize PostgreSQL indexing for large communities?',
                'body' => 'We are reaching 500k records in group_members. What indexes are optimal?',
                'tags' => ['postgres', 'database', 'optimization'],
            ]);

        $discRes->assertStatus(201);
        $discussionId = $discRes->json('data.id');

        // 2. Respondent replies with a solution
        $replyRes = $this->actingAs($respondent, 'sanctum')
            ->postJson("/api/v2/groups/{$group->id}/discussions/{$discussionId}/replies", [
                'body' => 'Use composite index on (group_id, status) and partial index on active members.',
            ]);

        $replyRes->assertStatus(201);
        $replyId = $replyRes->json('data.id');

        // 3. Creator marks this reply as Accepted Answer
        $solveRes = $this->actingAs($creator, 'sanctum')
            ->postJson("/api/v2/groups/{$group->id}/discussions/{$discussionId}/solve/{$replyId}");

        $solveRes->assertStatus(200)
            ->assertJsonPath('data.is_solved', true)
            ->assertJsonPath('data.accepted_answer_id', $replyId);

        $this->assertDatabaseHas('group_discussions', [
            'id' => $discussionId,
            'is_solved' => true,
            'accepted_answer_id' => $replyId,
        ]);
    }

    public function test_only_discussion_author_or_moderator_can_mark_accepted_answer(): void
    {
        $creator = User::factory()->create();
        $respondent = User::factory()->create();
        $randomMember = User::factory()->create();

        $group = Group::factory()->create([
            'creator_id' => $creator->id,
            'privacy' => 'public',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $creator->id,
            'role' => 'member',
            'status' => 'active',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $respondent->id,
            'role' => 'member',
            'status' => 'active',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $randomMember->id,
            'role' => 'member',
            'status' => 'active',
        ]);

        $discussion = GroupDiscussion::create([
            'group_id' => $group->id,
            'author_id' => $creator->id,
            'type' => 'question',
            'title' => 'Sample question',
            'body' => 'Question body',
        ]);

        $reply = GroupDiscussionReply::create([
            'discussion_id' => $discussion->id,
            'author_id' => $respondent->id,
            'body' => 'A plausible solution',
        ]);

        // Random member tries to mark as solved -> Must be rejected (403)
        $response = $this->actingAs($randomMember, 'sanctum')
            ->postJson("/api/v2/groups/{$group->id}/discussions/{$discussion->id}/solve/{$reply->id}");

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');

        $this->assertDatabaseHas('group_discussions', [
            'id' => $discussion->id,
            'is_solved' => false,
        ]);
    }

    public function test_private_community_discussions_cannot_be_viewed_by_non_members(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $group = Group::factory()->create([
            'creator_id' => $owner->id,
            'privacy' => 'private',
            'community_type' => Group::TYPE_PRIVATE,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($outsider, 'sanctum')
            ->getJson("/api/v2/groups/{$group->id}/discussions");

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_contributor_badges_assignment_and_rbac(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $regularUser = User::factory()->create();

        $group = Group::factory()->create(['creator_id' => $admin->id]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'active',
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $regularUser->id,
            'role' => 'member',
            'status' => 'active',
        ]);

        // Regular user tries to assign badge -> Forbidden (403)
        $badReq = $this->actingAs($regularUser, 'sanctum')
            ->postJson("/api/v2/groups/{$group->id}/members/{$member->id}/badges", [
                'badge_type' => GroupMemberBadge::TYPE_COMMUNITY_MENTOR,
            ]);

        $badReq->assertStatus(403);

        // Admin assigns badge -> Success (201)
        $goodReq = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v2/groups/{$group->id}/members/{$member->id}/badges", [
                'badge_type' => GroupMemberBadge::TYPE_COMMUNITY_MENTOR,
            ]);

        $goodReq->assertStatus(201)
            ->assertJsonPath('data.badge_type', GroupMemberBadge::TYPE_COMMUNITY_MENTOR);

        $this->assertDatabaseHas('group_member_badges', [
            'group_id' => $group->id,
            'user_id' => $member->id,
            'badge_type' => GroupMemberBadge::TYPE_COMMUNITY_MENTOR,
            'assigned_by' => $admin->id,
        ]);
    }

    public function test_granular_subscriptions_and_bookmarks_toggle(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        // 1. Toggle subscription on
        $sub1 = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v2/groups/{$group->id}/subscriptions/discussion/101");

        $sub1->assertStatus(200)
            ->assertJsonPath('data.subscribed', true);

        $this->assertDatabaseHas('group_subscriptions', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'subscribable_type' => 'discussion',
            'subscribable_id' => 101,
        ]);

        // 2. Toggle subscription off
        $sub2 = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v2/groups/{$group->id}/subscriptions/discussion/101");

        $sub2->assertStatus(200)
            ->assertJsonPath('data.subscribed', false);

        $this->assertDatabaseMissing('group_subscriptions', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'subscribable_type' => 'discussion',
            'subscribable_id' => 101,
        ]);

        // 3. Toggle bookmark on
        $bm1 = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v2/groups/{$group->id}/bookmarks/resource/55");

        $bm1->assertStatus(200)
            ->assertJsonPath('data.bookmarked', true);

        $this->assertDatabaseHas('group_bookmarks', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'bookmarkable_type' => 'resource',
            'bookmarkable_id' => 55,
        ]);
    }

    public function test_custom_roles_creation_and_listing(): void
    {
        $admin = User::factory()->create();
        $group = Group::factory()->create(['creator_id' => $admin->id]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'Community Editor',
            'description' => 'Allowed to publish announcements and curate guides.',
            'permissions' => ['publish_announcements', 'manage_guides', 'pin_discussions'],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v2/groups/{$group->id}/custom-roles", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Community Editor');

        $this->assertDatabaseHas('group_custom_roles', [
            'group_id' => $group->id,
            'name' => 'Community Editor',
        ]);

        // List custom roles
        $listRes = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v2/groups/{$group->id}/custom-roles");

        $listRes->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
