<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupPoll;
use App\Models\Post;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * এন্টারপ্রাইজ সোশ্যাল গ্রুপ ও কমিউনিটি ফিচার টেস্ট সুইট:
 * গ্রুপ তৈরি, আরrbac, স্ক্রিনিং প্রশ্ন, পোস্ট অনুমোদন কিউ, পোল, ইভেন্ট,
 * মডারেশন স্ট্রাইক, ব্যান, রিপোর্টস এবং রিয়েল অ্যানালিটিক্স ভেরিফিকেশন।
 */
class EnterpriseGroupsPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_enterprise_group_with_rules_and_questions(): void
    {
        $creator = User::factory()->create();

        $response = $this->actingAs($creator, 'sanctum')
            ->postJson('/api/v1/groups', [
                'name' => 'Bangladesh Artificial Intelligence Community',
                'username' => 'bd-ai-community',
                'description' => 'A premier community for ML/AI engineers and researchers.',
                'category' => 'Technology',
                'privacy' => 'private',
                'membership_approval_mode' => 'approval_required',
                'post_approval_mode' => 'admin_approval',
                'rules' => [
                    ['title' => 'Be Respectful', 'description' => 'Treat everyone with dignity.'],
                    ['title' => 'No Spam', 'description' => 'Unsolicited promotional content is prohibited.'],
                ],
                'questions' => [
                    ['question' => 'What is your background in AI?', 'type' => 'text'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Bangladesh Artificial Intelligence Community')
            ->assertJsonPath('data.username', 'bd-ai-community')
            ->assertJsonPath('data.privacy', 'private')
            ->assertJsonPath('data.membership_approval_mode', 'approval_required')
            ->assertJsonPath('data.post_approval_mode', 'admin_approval');

        $this->assertDatabaseHas('groups', [
            'username' => 'bd-ai-community',
            'creator_id' => $creator->id,
            'privacy' => 'private',
        ]);

        $this->assertDatabaseHas('group_rules', [
            'title' => 'Be Respectful',
        ]);

        $this->assertDatabaseHas('group_questions', [
            'question' => 'What is your background in AI?',
        ]);

        // Verifying group chat conversation was provisioned
        $group = Group::where('username', 'bd-ai-community')->first();
        $this->assertNotNull($group->conversation_id);
    }

    public function test_duplicate_slug_resolves_automatically(): void
    {
        $creator1 = User::factory()->create();
        $creator2 = User::factory()->create();

        $this->actingAs($creator1, 'sanctum')->postJson('/api/v1/groups', ['name' => 'Web Developers']);
        $res2 = $this->actingAs($creator2, 'sanctum')->postJson('/api/v1/groups', ['name' => 'Web Developers']);

        $res2->assertStatus(201);
        $this->assertEquals('web-developers-1', $res2->json('data.slug'));
    }

    public function test_hidden_group_is_inaccessible_to_non_members(): void
    {
        $admin = User::factory()->create();
        $stranger = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($admin, [
            'name' => 'Secret Guild',
            'privacy' => 'hidden',
        ]);

        $response = $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/groups/{$group->slug}");

        $response->assertStatus(403);
    }

    public function test_membership_screening_answers_saved_and_admin_approves(): void
    {
        $admin = User::factory()->create();
        $applicant = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($admin, [
            'name' => 'Exclusive Club',
            'privacy' => 'private',
            'membership_approval_mode' => 'approval_required',
            'questions' => [
                ['question' => 'Years of experience?', 'type' => 'text'],
            ],
        ]);

        $question = $group->questions()->first();

        // Applicant joins with screening answer
        $joinRes = $this->actingAs($applicant, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/join", [
                'answers' => [
                    $question->id => '5 years in engineering',
                ],
            ]);

        $joinRes->assertStatus(200)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('group_question_answers', [
            'group_id' => $group->id,
            'question_id' => $question->id,
            'user_id' => $applicant->id,
            'answer' => '5 years in engineering',
        ]);

        // Admin approves member
        $approveRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/approve/{$applicant->id}");

        $approveRes->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $applicant->id,
            'status' => 'active',
        ]);

        $this->assertEquals(2, $group->fresh()->members_count);
    }

    public function test_member_moderation_warn_mute_ban_and_unban(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($admin, ['name' => 'Moderation Test Group']);
        $groupService->joinGroup($group, $member);

        // 1. Warn member
        $warnRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/members/{$member->id}/warn", [
                'reason' => 'Off-topic discussion in main feed.',
            ]);

        $warnRes->assertStatus(200);
        $this->assertDatabaseHas('group_member_strikes', [
            'group_id' => $group->id,
            'user_id' => $member->id,
            'action_taken' => 'warning',
        ]);

        // 2. Mute member for 60 minutes
        $muteRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/members/{$member->id}/mute", [
                'duration_minutes' => 60,
                'reason' => 'Repeated spamming.',
            ]);

        $muteRes->assertStatus(200);
        $this->assertTrue($group->getMembership($member->id)->isMuted());

        // Muted member cannot post
        $postRes = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/posts", ['content' => 'Trying to post while muted']);
        $postRes->assertStatus(403);

        // 3. Ban member
        $banRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/members/{$member->id}/ban", [
                'reason' => 'Severe rule violation.',
            ]);

        $banRes->assertStatus(200);
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $member->id,
            'status' => 'banned',
        ]);

        // Banned member cannot re-join
        $rejoinRes = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/join");
        $rejoinRes->assertStatus(403);

        // 4. Unban member
        $unbanRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/members/{$member->id}/unban");
        $unbanRes->assertStatus(200);
        $this->assertEquals('active', $group->getMembership($member->id)->status);
    }

    public function test_post_approval_queue_and_moderation(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($admin, [
            'name' => 'Moderated Community',
            'post_approval_mode' => 'admin_approval',
        ]);
        $groupService->joinGroup($group, $member);

        // Member creates post -> goes to pending approval
        $postRes = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/posts", [
                'content' => 'This is a pending post waiting for admin approval.',
            ]);

        $postRes->assertStatus(201);
        $postId = $postRes->json('data.id');

        $this->assertDatabaseHas('posts', [
            'id' => $postId,
            'status' => 'pending_approval',
        ]);

        // Published feed does not show pending post
        $feedRes = $this->actingAs($member, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/posts");
        $feedRes->assertStatus(200)->assertJsonCount(0, 'data');

        // Admin views moderation queue
        $modQueueRes = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/moderation/posts");
        $modQueueRes->assertStatus(200)->assertJsonCount(1, 'data');

        // Admin approves post
        $approvePostRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/moderation/posts/{$postId}/approve");
        $approvePostRes->assertStatus(200);

        $this->assertDatabaseHas('posts', [
            'id' => $postId,
            'status' => 'published',
        ]);

        $this->assertEquals(1, $group->fresh()->posts_count);
    }

    public function test_keyword_alert_flags_post_for_moderator_review(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($admin, [
            'name' => 'Free Discussion',
            'post_approval_mode' => 'auto', // normally auto-approved
        ]);
        $groupService->joinGroup($group, $member);

        // Post with blacklisted keyword 'scam'
        $postRes = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/posts", [
                'content' => 'Click here to earn quick cash this is not a scam promise!',
            ]);

        $postRes->assertStatus(201);
        $this->assertEquals('pending_approval', Post::find($postRes->json('data.id'))->status);
    }

    public function test_interactive_polls_creation_and_voting(): void
    {
        $admin = User::factory()->create();
        $voter = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($admin, ['name' => 'Polling Group']);
        $groupService->joinGroup($group, $voter);

        // Create poll
        $pollRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/polls", [
                'question' => 'What is your preferred programming language?',
                'options' => ['PHP', 'TypeScript', 'Python'],
                'is_multiple_choice' => false,
            ]);

        $pollRes->assertStatus(201)
            ->assertJsonPath('data.question', 'What is your preferred programming language?');

        $pollId = $pollRes->json('data.id');
        $poll = GroupPoll::find($pollId);
        $phpOption = $poll->options->firstWhere('option_text', 'PHP');

        // Vote on poll
        $voteRes = $this->actingAs($voter, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/polls/{$pollId}/vote", [
                'option_ids' => [$phpOption->id],
            ]);

        $voteRes->assertStatus(200);

        $this->assertDatabaseHas('group_poll_votes', [
            'poll_id' => $pollId,
            'poll_option_id' => $phpOption->id,
            'user_id' => $voter->id,
        ]);

        $this->assertEquals(1, $phpOption->fresh()->votes_count);
    }

    public function test_group_events_and_rsvp(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($admin, ['name' => 'Eventful Group']);
        $groupService->joinGroup($group, $member);

        // Admin creates event
        $eventRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/events", [
                'title' => 'Annual Community Meetup 2026',
                'description' => 'Networking and tech sessions.',
                'start_time' => now()->addDays(7)->toIso8601String(),
                'is_online' => true,
                'meeting_url' => 'https://jugajug.com/meet/annual-2026',
            ]);

        $eventRes->assertStatus(201);
        $eventId = $eventRes->json('data.id');

        // Member RSVPs
        $rsvpRes = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/events/{$eventId}/rsvp", [
                'status' => 'going',
            ]);

        $rsvpRes->assertStatus(200);
        $this->assertDatabaseHas('group_event_attendees', [
            'event_id' => $eventId,
            'user_id' => $member->id,
            'status' => 'going',
        ]);
    }

    public function test_group_reports_and_moderator_review(): void
    {
        $admin = User::factory()->create();
        $reporter = User::factory()->create();
        $violator = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($admin, ['name' => 'Reportable Group']);
        $groupService->joinGroup($group, $reporter);
        $groupService->joinGroup($group, $violator);

        // Submit report
        $reportRes = $this->actingAs($reporter, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/reports", [
                'reportable_type' => 'user',
                'reportable_id' => $violator->id,
                'reason_category' => 'harassment',
                'description' => 'Sent unsolicited offensive messages.',
            ]);

        $reportRes->assertStatus(201);
        $reportId = $reportRes->json('data.id');

        // Admin reviews report
        $reviewRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/groups/{$group->id}/moderation/reports/{$reportId}/review", [
                'status' => 'action_taken',
                'decision_note' => 'Member issued a strike.',
            ]);

        $reviewRes->assertStatus(200);
        $this->assertDatabaseHas('group_reports', [
            'id' => $reportId,
            'status' => 'action_taken',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_analytics_calculation_and_authorization(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();

        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($admin, ['name' => 'Analytics Community']);
        $groupService->joinGroup($group, $member);

        // Admin can access analytics
        $adminRes = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/analytics");

        $adminRes->assertStatus(200)
            ->assertJsonPath('data.overview.total_members', 2)
            ->assertJsonPath('data.overview.active_members', 2);

        // Regular member cannot access analytics (HTTP 403)
        $memberRes = $this->actingAs($member, 'sanctum')
            ->getJson("/api/v1/groups/{$group->id}/analytics");

        $memberRes->assertStatus(403);
    }

    public function test_web_routes_render_successfully(): void
    {
        $creator = User::factory()->create();
        $groupService = app(GroupService::class);
        $group = $groupService->createGroup($creator, ['name' => 'Web Verification Community']);

        // Discovery Hub
        $indexRes = $this->actingAs($creator, 'web')->get('/groups');
        $indexRes->assertStatus(200);

        // Group Home with tabs
        $showRes = $this->actingAs($creator, 'web')->get("/groups/{$group->slug}?tab=about");
        $showRes->assertStatus(200);
    }
}
