<?php

namespace Tests\Feature;

use App\Jobs\PublishScheduledGroupPostsJob;
use App\Models\Comment;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase7ProfessionalGroupContentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $admin;

    private User $moderator;

    private User $member;

    private User $outsider;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Alice Owner', 'username' => 'alice_owner']);
        $this->admin = User::factory()->create(['name' => 'Bob Admin', 'username' => 'bob_admin']);
        $this->moderator = User::factory()->create(['name' => 'Charlie Mod', 'username' => 'charlie_mod']);
        $this->member = User::factory()->create(['name' => 'David Member', 'username' => 'david_dev']);
        $this->outsider = User::factory()->create(['name' => 'Eve Outsider', 'username' => 'eve_out']);

        $this->group = Group::create([
            'name' => 'Silicon Valley Tech Network',
            'slug' => 'silicon-valley-tech',
            'description' => 'A premier professional group for tech discussions.',
            'category' => 'Technology',
            'privacy' => 'public',
            'creator_id' => $this->owner->id,
            'members_count' => 4,
            'posts_count' => 0,
            'post_approval_mode' => 'automatic',
        ]);

        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now()->subDays(10),
        ]);

        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->admin->id,
            'role' => 'admin',
            'status' => 'active',
            'joined_at' => now()->subDays(8),
        ]);

        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->moderator->id,
            'role' => 'moderator',
            'status' => 'active',
            'joined_at' => now()->subDays(5),
        ]);

        GroupMember::create([
            'group_id' => $this->group->id,
            'user_id' => $this->member->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now()->subDays(2),
        ]);
    }

    public function test_member_can_create_text_and_poll_post(): void
    {
        $response = $this->actingAs($this->member)->postJson("/api/v1/groups/{$this->group->id}/posts", [
            'content' => 'Which cloud provider do you prefer for large scale workloads?',
            'type' => 'poll',
            'poll_data' => [
                'question' => 'Preferred Cloud Provider',
                'options' => ['AWS', 'Google Cloud', 'Azure'],
                'duration_days' => 7,
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.type', 'poll');

        $this->assertDatabaseHas('posts', [
            'group_id' => $this->group->id,
            'user_id' => $this->member->id,
            'status' => 'published',
        ]);

        $this->assertEquals(1, $this->group->fresh()->posts_count);
    }

    public function test_duplicate_content_posting_is_prevented(): void
    {
        $payload = ['content' => 'Identical announcement text!'];

        $first = $this->actingAs($this->member)->postJson("/api/v1/groups/{$this->group->id}/posts", $payload);
        $first->assertStatus(201);

        $second = $this->actingAs($this->member)->postJson("/api/v1/groups/{$this->group->id}/posts", $payload);
        $second->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_post_approval_workflow_requires_admin_or_moderator_approval(): void
    {
        $this->group->update(['post_approval_mode' => 'admin_approval']);

        // Member creates a post -> should be pending approval
        $response = $this->actingAs($this->member)->postJson("/api/v1/groups/{$this->group->id}/posts", [
            'content' => 'This post requires moderation before public visibility.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending_approval');

        $postId = $response->json('data.id');

        // Verify it is visible in pending queue
        $queueResponse = $this->actingAs($this->moderator)->getJson("/api/v1/groups/{$this->group->id}/posts/pending");
        $queueResponse->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertCount(1, $queueResponse->json('data'));

        // Moderator approves the post
        $approveResponse = $this->actingAs($this->moderator)->postJson("/api/v1/groups/{$this->group->id}/posts/{$postId}/approve");
        $approveResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('posts', [
            'id' => $postId,
            'status' => 'published',
        ]);
        $this->assertEquals(1, $this->group->fresh()->posts_count);
    }

    public function test_moderator_can_reject_pending_post_with_reason(): void
    {
        $this->group->update(['post_approval_mode' => 'admin_approval']);

        $post = Post::create([
            'group_id' => $this->group->id,
            'user_id' => $this->member->id,
            'content' => 'Spam link to external product.',
            'status' => 'pending_approval',
        ]);

        $rejectResponse = $this->actingAs($this->moderator)->postJson("/api/v1/groups/{$this->group->id}/posts/{$post->id}/reject", [
            'reason' => 'External promotions not permitted without prior authorization.',
        ]);

        $rejectResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'rejected',
        ]);
    }

    public function test_can_pin_and_unpin_posts_and_filter_feed(): void
    {
        $post = Post::create([
            'group_id' => $this->group->id,
            'user_id' => $this->owner->id,
            'content' => 'Important community roadmap.',
            'status' => 'published',
            'is_pinned' => false,
        ]);

        // Pin the post
        $pinResponse = $this->actingAs($this->admin)->postJson("/api/v1/groups/{$this->group->id}/posts/{$post->id}/pin");
        $pinResponse->assertStatus(200)
            ->assertJsonPath('data.is_pinned', true);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'is_pinned' => true]);

        // Feed filtered by pinned returns this post
        $feedResponse = $this->actingAs($this->member)->getJson("/api/v1/groups/{$this->group->id}/posts?filter=pinned");
        $feedResponse->assertStatus(200);
        $this->assertCount(1, $feedResponse->json('data'));
        $this->assertEquals($post->id, $feedResponse->json('data.0.id'));
    }

    public function test_can_react_to_post_and_vote_in_poll(): void
    {
        $post = Post::create([
            'group_id' => $this->group->id,
            'user_id' => $this->owner->id,
            'content' => 'Vote for next meetup topic',
            'type' => 'poll',
            'status' => 'published',
            'poll_data' => [
                'options' => [
                    ['id' => 1, 'text' => 'AI Agents', 'votes_count' => 0],
                    ['id' => 2, 'text' => 'Laravel 12', 'votes_count' => 0],
                ],
                'voted_user_ids' => [],
            ],
        ]);

        // React to post
        $reactResponse = $this->actingAs($this->member)->postJson("/api/v1/groups/{$this->group->id}/posts/{$post->id}/react", [
            'type' => 'love',
        ]);
        $reactResponse->assertStatus(200)
            ->assertJsonPath('data.viewer_reaction', 'love')
            ->assertJsonPath('data.likes_count', 1);

        // Vote in poll
        $voteResponse = $this->actingAs($this->member)->postJson("/api/v1/groups/{$this->group->id}/posts/{$post->id}/vote", [
            'option_id' => 1,
        ]);
        $voteResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $freshPost = $post->fresh();
        $this->assertEquals(1, $freshPost->poll_data['options'][0]['votes_count']);
    }

    public function test_can_report_post_and_moderator_can_action_report(): void
    {
        $post = Post::create([
            'group_id' => $this->group->id,
            'user_id' => $this->member->id,
            'content' => 'Inappropriate content here',
            'status' => 'published',
        ]);
        $this->group->increment('posts_count');

        // Member reports post
        $reportResponse = $this->actingAs($this->member)->postJson("/api/v1/groups/{$this->group->id}/posts/{$post->id}/report", [
            'reason_category' => 'harassment',
            'description' => 'Offensive comment toward team members.',
        ]);
        $reportResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $reportId = $reportResponse->json('data.id');

        // Moderator reviews and removes content
        $actionResponse = $this->actingAs($this->moderator)->postJson("/api/v1/groups/{$this->group->id}/moderation/reports/{$reportId}/action", [
            'status' => 'action_taken',
            'decision_note' => 'Confirmed offensive language. Post deleted.',
            'remove_content' => true,
        ]);
        $actionResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'action_taken');

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_group_moderator_can_delete_inappropriate_comment(): void
    {
        $post = Post::create([
            'group_id' => $this->group->id,
            'user_id' => $this->owner->id,
            'content' => 'Welcome discussion.',
            'status' => 'published',
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $this->member->id,
            'body' => 'Spam comment',
        ]);

        $deleteResponse = $this->actingAs($this->moderator)->deleteJson("/api/v1/comments/{$comment->id}");
        $deleteResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    public function test_scheduled_publishing_job_publishes_due_posts(): void
    {
        $scheduledPost = Post::create([
            'group_id' => $this->group->id,
            'user_id' => $this->member->id,
            'content' => 'Good morning Europe! Scheduled daily digest.',
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
        ]);

        $job = new PublishScheduledGroupPostsJob;
        $publishedCount = $job->handle();

        $this->assertEquals(1, $publishedCount);
        $this->assertEquals('published', $scheduledPost->fresh()->status);
        $this->assertEquals(1, $this->group->fresh()->posts_count);
    }
}
