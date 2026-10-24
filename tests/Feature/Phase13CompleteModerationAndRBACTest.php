<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13CompleteModerationAndRBACTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => $user->first_name ?? 'Mod',
            'last_name' => $user->last_name ?? 'User',
        ]);

        return $user;
    }

    /**
     * Requirement 13.1: Content reporting engine against posts, comments, and users with categorized reasons.
     */
    public function test_content_reporting_engine_against_posts_comments_and_users(): void
    {
        $reporter = $this->createUser();
        $targetUser = $this->createUser(['name' => 'Bad Actor']);

        $post = Post::create([
            'user_id' => $targetUser->id,
            'content' => 'Buy cheap crypto now! Click link: http://spam.example.com',
            'audience' => 'public',
        ]);

        $comment = Comment::create([
            'user_id' => $targetUser->id,
            'post_id' => $post->id,
            'body' => 'Hateful harassment text',
        ]);

        // 1. Report a Post
        $postReportRes = $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'spam',
                'details' => 'Repeated fraudulent investment links.',
            ]);

        $postReportRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Report::STATUS_PENDING)
            ->assertJsonPath('data.reason', 'spam');

        // 2. Report a Comment
        $commentReportRes = $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'comment',
                'reportable_id' => $comment->id,
                'reason' => 'harassment',
                'details' => 'Direct abusive target comment.',
            ]);

        $commentReportRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Report::STATUS_PENDING)
            ->assertJsonPath('data.reason', 'harassment');

        // 3. Report a User Profile
        $userReportRes = $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'user',
                'reportable_id' => $targetUser->id,
                'reason' => 'hate_speech',
                'details' => 'Impersonation and hate speech profile.',
            ]);

        $userReportRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Report::STATUS_PENDING)
            ->assertJsonPath('data.reason', 'hate_speech');

        // 4. Invalid reason should fail with 422 validation error
        $invalidReasonRes = $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'unknown_invalid_reason',
            ]);

        $invalidReasonRes->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    /**
     * Requirement 13.2: Duplicate pending report prevention.
     */
    public function test_duplicate_pending_report_prevention(): void
    {
        $reporter = $this->createUser();
        $targetUser = $this->createUser();

        $post = Post::create([
            'user_id' => $targetUser->id,
            'content' => 'Suspicious content',
            'audience' => 'public',
        ]);

        // First report succeeds
        $firstRes = $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'spam',
            ]);
        $firstRes->assertStatus(201);

        // Second report by same user for same post while pending fails with 422
        $secondRes = $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'violence',
            ]);

        $secondRes->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You have already submitted a pending report for this item.');
    }

    /**
     * Requirement 13.3: Strict RBAC isolation for moderation endpoints (regular users blocked with 403).
     */
    public function test_regular_users_are_forbidden_from_moderation_queue_and_actions(): void
    {
        $regularUser = $this->createUser();
        $targetUser = $this->createUser();

        $post = Post::create([
            'user_id' => $targetUser->id,
            'content' => 'Controversial post',
            'audience' => 'public',
        ]);

        $report = Report::create([
            'reporter_id' => $targetUser->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'spam',
            'status' => Report::STATUS_PENDING,
        ]);

        // Regular user cannot view reports queue (403)
        $this->actingAs($regularUser, 'sanctum')
            ->getJson('/api/v1/admin/reports')
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        // Regular user cannot review/action report (403)
        $this->actingAs($regularUser, 'sanctum')
            ->putJson("/api/v1/admin/reports/{$report->id}", [
                'status' => 'dismissed',
            ])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /**
     * Requirement 13.4: Moderator review queue, report dismissal, and content deletion action.
     */
    public function test_moderator_can_review_dismiss_and_action_reports_with_content_deletion(): void
    {
        $moderator = $this->createUser(['name' => 'Official Moderator']);
        $moderator->assignRole('MODERATOR');

        $reporter = $this->createUser();
        $author = $this->createUser(['name' => 'Spammer User']);

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Malicious payload in public post',
            'audience' => 'public',
        ]);

        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'violence',
            'details' => 'Threatening violence',
            'status' => Report::STATUS_PENDING,
        ]);

        // 1. Moderator views the report queue
        $queueRes = $this->actingAs($moderator, 'sanctum')
            ->getJson('/api/v1/admin/reports?status=pending');

        $queueRes->assertStatus(200)
            ->assertJsonPath('success', true);
        $reportIds = collect($queueRes->json('data'))->pluck('id')->all();
        $this->assertContains($report->id, $reportIds);

        // 2. Moderator takes action: Actioned + Delete Content
        $actionRes = $this->actingAs($moderator, 'sanctum')
            ->putJson("/api/v1/admin/reports/{$report->id}", [
                'status' => Report::STATUS_ACTIONED,
                'action_type' => 'delete_content',
                'admin_notes' => 'Violated safety guidelines. Content removed permanently.',
            ]);

        $actionRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Report::STATUS_ACTIONED)
            ->assertJsonPath('data.reviewed_by', $moderator->id);

        // Verify post was deleted from database
        $this->assertSoftDeleted('posts', ['id' => $post->id]);

        // Verify report is updated in DB
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => Report::STATUS_ACTIONED,
            'reviewed_by' => $moderator->id,
            'admin_notes' => 'Violated safety guidelines. Content removed permanently.',
        ]);
    }

    /**
     * Requirement 13.5: Moderator can action report with user suspension.
     */
    public function test_moderator_can_action_report_with_user_suspension(): void
    {
        $moderator = $this->createUser();
        $moderator->assignRole('MODERATOR');

        $offender = $this->createUser(['name' => 'Serial Harasser']);

        $report = Report::create([
            'reporter_id' => $moderator->id,
            'reportable_type' => User::class,
            'reportable_id' => $offender->id,
            'reason' => 'harassment',
            'status' => Report::STATUS_PENDING,
        ]);

        $response = $this->actingAs($moderator, 'sanctum')
            ->putJson("/api/v1/admin/reports/{$report->id}", [
                'status' => Report::STATUS_ACTIONED,
                'action_type' => 'suspend_user',
                'admin_notes' => 'Account suspended due to repeated harassment.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Report::STATUS_ACTIONED);

        $this->assertEquals('suspended', $offender->fresh()->status);
    }

    /**
     * Requirement 13.6: Role management and assignment (RBAC) via Admin API.
     */
    public function test_admin_role_management_and_permission_enforcement(): void
    {
        $superAdmin = $this->createUser(['name' => 'System SuperAdmin']);
        $superAdmin->assignRole('SUPER_ADMIN');

        $targetUser = $this->createUser(['name' => 'Potential Moderator']);
        $regularUser = $this->createUser();

        // 1. Regular user forbidden from viewing user list or assigning roles
        $this->actingAs($regularUser, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertStatus(403);

        $this->actingAs($regularUser, 'sanctum')
            ->postJson("/api/v1/admin/users/{$targetUser->id}/roles", ['role' => 'MODERATOR'])
            ->assertStatus(403);

        // 2. SuperAdmin views user directory
        $usersList = $this->actingAs($superAdmin, 'sanctum')
            ->getJson('/api/v1/admin/users?search='.urlencode($targetUser->username));

        $usersList->assertStatus(200)
            ->assertJsonPath('success', true);
        $userIds = collect($usersList->json('data'))->pluck('id')->all();
        $this->assertContains($targetUser->id, $userIds);

        // 3. SuperAdmin assigns MODERATOR role to target user
        $assignRes = $this->actingAs($superAdmin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$targetUser->id}/roles", [
                'role' => 'MODERATOR',
            ]);

        $assignRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue($targetUser->fresh()->hasRole('MODERATOR'));

        // 4. SuperAdmin removes MODERATOR role from target user
        $removeRes = $this->actingAs($superAdmin, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$targetUser->id}/roles", [
                'role' => 'MODERATOR',
            ]);

        $removeRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertFalse($targetUser->fresh()->hasRole('MODERATOR'));
    }

    /**
     * Requirement 13.7: Admin system metrics monitoring endpoint.
     */
    public function test_system_metrics_endpoint(): void
    {
        $admin = $this->createUser();
        $admin->assignRole('ADMIN');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/metrics');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'application' => ['name', 'environment', 'debug_mode', 'laravel_version', 'php_version'],
                    'memory' => ['current_usage_mb', 'peak_usage_mb', 'memory_limit'],
                    'system' => ['load_average', 'server_time', 'timezone'],
                    'queues' => ['connection', 'queues_monitored'],
                ],
            ]);
    }
}
