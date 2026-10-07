<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterprisePostComposerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'username' => 'composer_test_user',
            'name' => 'Composer Tester',
        ]);
    }

    public function test_user_can_create_rich_post_with_background_style_and_ai_disclosure(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'Exciting news from Jugajug! #tech #social',
            'audience' => 'public',
            'type' => 'text',
            'background_style' => 'gradient-aurora',
            'is_ai_generated' => true,
            'content_warning' => null,
            'location' => 'Dhaka, Bangladesh',
            'feeling_activity' => '— 🚀 Launching',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Exciting news from Jugajug! #tech #social')
            ->assertJsonPath('data.background_style', 'gradient-aurora')
            ->assertJsonPath('data.is_ai_generated', true)
            ->assertJsonPath('data.location', 'Dhaka, Bangladesh')
            ->assertJsonPath('data.feeling_activity', '— 🚀 Launching');

        $this->assertDatabaseHas('posts', [
            'user_id' => $this->user->id,
            'background_style' => 'gradient-aurora',
            'is_ai_generated' => true,
        ]);
    }

    public function test_user_can_create_post_with_poll_and_vote(): void
    {
        $createRes = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'What feature should we launch next?',
            'type' => 'poll',
            'poll_data' => [
                'question' => 'What feature should we launch next?',
                'options' => [
                    ['id' => 1, 'text' => 'Dark Mode v2'],
                    ['id' => 2, 'text' => 'Voice Clips'],
                    ['id' => 3, 'text' => 'Group Video Calling'],
                ],
                'duration_hours' => 48,
            ],
        ]);

        $createRes->assertStatus(201);
        $postId = $createRes->json('data.id');

        $voter = User::factory()->create();

        // Vote for option 2
        $voteRes = $this->actingAs($voter, 'sanctum')->postJson("/api/v1/posts/{$postId}/poll/vote", [
            'option_id' => 2,
        ]);

        $voteRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.options.1.votes_count', 1)
            ->assertJsonPath('data.total_votes', 1);

        $post = Post::find($postId);
        $this->assertEquals(1, $post->poll_data['options'][1]['votes_count']);
        $this->assertEquals(2, $post->poll_data['voted_user_ids'][$voter->id]);
    }

    public function test_user_can_create_post_with_collaborator_and_tagged_users(): void
    {
        $friend1 = User::factory()->create(['name' => 'Friend One']);
        $friend2 = User::factory()->create(['name' => 'Friend Two']);
        $collaborator = User::factory()->create(['name' => 'Collaborator Person']);

        $res = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'Collaborative project announcement with @friend1 and @friend2',
            'collaborator_id' => $collaborator->id,
            'tagged_user_ids' => [$friend1->id, $friend2->id],
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.collaborator.id', $collaborator->id)
            ->assertJsonPath('data.collaborator.status', 'pending');

        $this->assertCount(2, $res->json('data.tagged_users'));

        $postId = $res->json('data.id');

        // Collaborator accepts
        $acceptRes = $this->actingAs($collaborator, 'sanctum')->postJson("/api/v1/posts/{$postId}/collaborator", [
            'action' => 'accept',
        ]);

        $acceptRes->assertStatus(200)
            ->assertJsonPath('data.collaborator.status', 'accepted');
    }

    public function test_user_can_schedule_post_and_release_with_artisan_command(): void
    {
        $futureDate = now()->addDays(2)->toIso8601String();

        $res = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'This is a scheduled post for next week.',
            'scheduled_at' => $futureDate,
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.status', 'scheduled');

        $postId = $res->json('data.id');

        // Post should not be visible in public feed yet
        $feedRes = $this->getJson('/api/v1/feed');
        $feedRes->assertJsonMissing(['id' => $postId]);

        // Manually age the scheduled_at timestamp to test command
        Post::where('id', $postId)->update(['scheduled_at' => now()->subMinute()]);

        $this->artisan('posts:publish-scheduled')
            ->assertExitCode(0);

        $this->assertEquals('published', Post::find($postId)->status);
    }

    public function test_draft_saving_retrieval_and_clearing(): void
    {
        // 1. Auto-save draft
        $draftRes = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/posts/drafts', [
            'content' => 'Drafting a new thought...',
            'audience' => 'friends',
            'type' => 'text',
            'background_style' => 'gradient-midnight',
            'feeling_activity' => '— ☕ Sipping tea',
        ]);

        $draftRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Drafting a new thought...')
            ->assertJsonPath('data.background_style', 'gradient-midnight');

        $this->assertDatabaseHas('post_drafts', [
            'user_id' => $this->user->id,
            'content' => 'Drafting a new thought...',
        ]);

        // 2. Retrieve drafts
        $getDraftsRes = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/posts/drafts');
        $getDraftsRes->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Drafting a new thought...');

        // 3. Clear drafts
        $clearRes = $this->actingAs($this->user, 'sanctum')->deleteJson('/api/v1/posts/drafts');
        $clearRes->assertStatus(200);

        $this->assertDatabaseMissing('post_drafts', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_link_preview_ssrf_protection(): void
    {
        // Test loopback blocked
        $ssrfRes = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/posts/link-preview', [
            'url' => 'http://127.0.0.1:8000/api/v1/feed',
        ]);

        $ssrfRes->assertStatus(422)
            ->assertJsonPath('success', false);

        // Test metadata IP blocked
        $metaRes = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/posts/link-preview', [
            'url' => 'http://169.254.169.254/latest/meta-data/',
        ]);

        $metaRes->assertStatus(422);
    }

    public function test_taggable_users_search(): void
    {
        User::factory()->create(['name' => 'Rahim Uddin', 'username' => 'rahim_u']);
        User::factory()->create(['name' => 'Karim Hasan', 'username' => 'karim_h']);

        $res = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/posts/taggable-users?q=Rahim');

        $res->assertStatus(200)
            ->assertJsonPath('success', true);

        $results = $res->json('data');
        $this->assertNotEmpty($results);
        $this->assertEquals('Rahim Uddin', $results[0]['name']);
    }

    public function test_user_groups_and_group_posting(): void
    {
        $group = Group::create([
            'creator_id' => $this->user->id,
            'name' => 'Tech Developers BD',
            'slug' => 'tech-dev-bd',
            'privacy' => Group::PRIVACY_PUBLIC,
            'post_approval_mode' => Group::POST_APPROVAL_AUTO,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
        ]);

        $groupsRes = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/posts/user-groups');
        $groupsRes->assertStatus(200)
            ->assertJsonPath('data.0.id', $group->id)
            ->assertJsonPath('data.0.name', 'Tech Developers BD');

        // Post to group
        $postRes = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'Hello group members!',
            'group_id' => $group->id,
        ]);

        $postRes->assertStatus(201)
            ->assertJsonPath('data.group_id', $group->id);

        $this->assertDatabaseHas('posts', [
            'group_id' => $group->id,
            'content' => 'Hello group members!',
        ]);
    }

    public function test_gifs_catalog_and_categories(): void
    {
        $res = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/posts/gifs?category=happy');

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'categories',
                    'gifs',
                ],
            ]);

        $this->assertNotEmpty($res->json('data.gifs'));
        $this->assertNotEmpty($res->json('data.categories'));
    }
}
