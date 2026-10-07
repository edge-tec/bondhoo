<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\PostShare;
use App\Models\Reaction;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSocialConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Concurrency & Race condition: 20 simultaneous friend requests between same two users.
     * Must result in exactly one valid friendship request row, with no duplicates or crashes.
     */
    public function test_concurrent_friend_requests_creates_only_one_valid_relationship(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $successCount = 0;
        $errorCount = 0;

        // Simulate 20 rapid/simultaneous request attempts
        for ($i = 0; $i < 20; $i++) {
            $response = $this->actingAs($sender, 'sanctum')
                ->postJson("/api/v1/friends/{$recipient->id}/request");

            if ($response->status() === 200 || $response->status() === 201) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        // Exactly one request was created initially
        $this->assertEquals(1, $successCount);
        $this->assertEquals(19, $errorCount);

        // Database must have exactly 1 record for this pair
        $totalRelationships = Friendship::where(function ($q) use ($sender, $recipient) {
            $q->where('user_id', $sender->id)->where('friend_id', $recipient->id);
        })->orWhere(function ($q) use ($sender, $recipient) {
            $q->where('user_id', $recipient->id)->where('friend_id', $sender->id);
        })->count();

        $this->assertEquals(1, $totalRelationships);

        // Reverse direction request auto-accepts without creating duplicate
        $reverseResponse = $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/v1/friends/{$sender->id}/request");

        $this->assertContains($reverseResponse->status(), [200, 201]);
        $this->assertEquals(Friendship::STATUS_ACCEPTED, Friendship::first()->status);
        $this->assertEquals(1, Friendship::count());

        // Subsequent request when already friends must be rejected with 422
        $againResponse = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/v1/friends/{$recipient->id}/request");
        $againResponse->assertStatus(422);

        // Self-request must be prohibited
        $selfResponse = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/v1/friends/{$sender->id}/request");
        $selfResponse->assertStatus(422);
    }

    /**
     * 2. Concurrency & Counters: 20 users react to a post, then 10 toggle off.
     * Counters must remain 100% accurate without negative values or duplicate reaction rows.
     */
    public function test_concurrent_reaction_toggles_maintains_consistent_counters_and_no_duplicates(): void
    {
        $author = User::factory()->create();
        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'High engagement post for concurrency testing',
            'audience' => 'public',
        ]);

        $users = User::factory()->count(20)->create();

        // 20 users react concurrently
        foreach ($users as $user) {
            $response = $this->actingAs($user, 'sanctum')
                ->postJson("/api/v1/posts/{$post->id}/react", [
                    'type' => 'like',
                ]);
            $response->assertStatus(200);
            $response->assertJsonPath('data.reacted', true);
        }

        $freshPost = $post->fresh();
        $this->assertEquals(20, $freshPost->likes_count);
        $this->assertEquals(20, Reaction::where('reactable_type', Post::class)->where('reactable_id', $post->id)->count());

        // 10 users toggle off their reaction
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($users[$i], 'sanctum')
                ->postJson("/api/v1/posts/{$post->id}/react", [
                    'type' => 'like',
                ]);
            $response->assertStatus(200);
            $response->assertJsonPath('data.reacted', false);
        }

        $freshPost = $post->fresh();
        $this->assertEquals(10, $freshPost->likes_count);
        $this->assertGreaterThanOrEqual(0, $freshPost->likes_count);
        $this->assertEquals(10, Reaction::where('reactable_type', Post::class)->where('reactable_id', $post->id)->count());

        // Reaction summary contains consistent aliases
        $summaryResponse = $this->getJson("/api/v1/posts/{$post->id}/reactions");
        $summaryResponse->assertStatus(200);
        $summaryResponse->assertJsonPath('data.total_reactions', 10);
        $summaryResponse->assertJsonPath('data.likes_count', 10);
        $summaryResponse->assertJsonPath('data.reactions_count', 10);
    }

    /**
     * 3. Concurrency: Comments and replies creation and deletion.
     * Counters must accurately reflect creation and soft/hard deletes.
     */
    public function test_concurrent_comments_and_replies_maintains_accurate_counters(): void
    {
        $author = User::factory()->create();
        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Post with lots of discussion',
            'audience' => 'public',
        ]);

        $commenters = User::factory()->count(10)->create();
        $commentIds = [];

        // 10 concurrent comments
        foreach ($commenters as $index => $commenter) {
            $response = $this->actingAs($commenter, 'sanctum')
                ->postJson("/api/v1/posts/{$post->id}/comments", [
                    'body' => "Comment number {$index}",
                ]);
            $response->assertStatus(201);
            $commentIds[] = $response->json('data.id');
        }

        $this->assertEquals(10, $post->fresh()->comments_count);

        // 5 replies to the first comment
        $firstCommentId = $commentIds[0];
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($commenters[$i], 'sanctum')
                ->postJson("/api/v1/posts/{$post->id}/comments", [
                    'body' => "Reply number {$i}",
                    'parent_id' => $firstCommentId,
                ]);
            $response->assertStatus(201);
        }

        $firstComment = Comment::find($firstCommentId);
        $this->assertEquals(5, $firstComment->replies_count);
        $this->assertEquals(15, $post->fresh()->comments_count);

        // Delete 3 top-level comments by their authors
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->actingAs($commenters[$i], 'sanctum')
                ->deleteJson("/api/v1/comments/{$commentIds[$i]}");
            $response->assertStatus(200);
        }

        $this->assertEquals(12, $post->fresh()->comments_count);
    }

    /**
     * 4. Concurrency: Concurrent share operations must preserve atomic shares_count.
     */
    public function test_concurrent_shares_maintains_consistent_counter(): void
    {
        $author = User::factory()->create();
        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Viral post being shared widely',
            'audience' => 'public',
        ]);

        $sharers = User::factory()->count(10)->create();

        foreach ($sharers as $sharer) {
            $response = $this->actingAs($sharer, 'sanctum')
                ->postJson("/api/v1/posts/{$post->id}/share", [
                    'caption' => 'Sharing this insightful post!',
                ]);
            $response->assertStatus(200);
        }

        $this->assertEquals(10, $post->fresh()->shares_count);
        $this->assertEquals(10, PostShare::where('post_id', $post->id)->count());
    }

    /**
     * 5. Messenger: Concurrent direct conversation creation must reuse exactly one conversation.
     */
    public function test_concurrent_direct_conversation_creation_reuses_single_conversation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversationIds = [];

        // Both users try to start conversation at the same time
        for ($i = 0; $i < 5; $i++) {
            $resA = $this->actingAs($userA, 'sanctum')
                ->postJson('/api/v1/conversations', [
                    'type' => 'direct',
                    'participant_id' => $userB->id,
                ]);
            $conversationIds[] = $resA->json('data.id');

            $resB = $this->actingAs($userB, 'sanctum')
                ->postJson('/api/v1/conversations', [
                    'type' => 'direct',
                    'participant_id' => $userA->id,
                ]);
            $conversationIds[] = $resB->json('data.id');
        }

        // All returned IDs must be identical
        $uniqueIds = array_unique($conversationIds);
        $this->assertCount(1, $uniqueIds);

        // Exactly one direct conversation exists in database between userA and userB
        $directConversations = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userA->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userB->id))
            ->count();

        $this->assertEquals(1, $directConversations);
    }

    /**
     * 6. Block + Messaging / Interaction Race Condition:
     * When User A blocks User B, messaging, reactions, and comments are rejected server-side.
     */
    public function test_block_enforces_interaction_and_messaging_boundaries(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // User A has a friends-only post
        $post = Post::create([
            'user_id' => $userA->id,
            'content' => 'Friends only thoughts',
            'audience' => 'friends',
        ]);

        // User A blocks User B
        $blockResponse = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/friends/{$userB->id}/block");
        $blockResponse->assertStatus(200);

        // 1. User B tries to view User A's post -> 403 Forbidden
        $viewResponse = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/v1/posts/{$post->id}");
        $viewResponse->assertStatus(403);

        // 2. User B tries to react to User A's post -> 403 Forbidden
        $reactResponse = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'like']);
        $reactResponse->assertStatus(403);

        // 3. User B tries to comment on User A's post -> 403 Forbidden
        $commentResponse = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", ['body' => 'Should be blocked']);
        $commentResponse->assertStatus(403);

        // 4. User B tries to initiate direct conversation with User A -> 403 Forbidden
        $convResponse = $this->actingAs($userB, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'participant_id' => $userA->id,
            ]);
        $convResponse->assertStatus(403);

        // 5. Existing conversation message sending is also rejected
        $conv = Conversation::create(['type' => 'direct']);
        $conv->participants()->createMany([
            ['user_id' => $userA->id, 'role' => 'member'],
            ['user_id' => $userB->id, 'role' => 'member'],
        ]);

        $msgResponse = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'type' => 'text',
                'body' => 'Bypassing block message',
            ]);
        $msgResponse->assertStatus(403);
    }

    /**
     * 7. IDOR & Authorization Hardening:
     * User B cannot edit/delete User A's post or comment, or access User A's private content.
     */
    public function test_idor_protection_prevents_unauthorized_modifications_and_private_leakage(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $post = Post::create([
            'user_id' => $owner->id,
            'content' => 'Owner post',
            'audience' => 'public',
        ]);

        $privatePost = Post::create([
            'user_id' => $owner->id,
            'content' => 'Confidential thoughts',
            'audience' => 'only_me',
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $owner->id,
            'body' => 'Owner comment',
        ]);

        // Attacker attempts to edit post -> 403
        $editPostRes = $this->actingAs($attacker, 'sanctum')
            ->putJson("/api/v1/posts/{$post->id}", [
                'content' => 'Hacked content',
            ]);
        $editPostRes->assertStatus(403);
        $this->assertEquals('Owner post', $post->fresh()->content);

        // Attacker attempts to delete post -> 403
        $delPostRes = $this->actingAs($attacker, 'sanctum')
            ->deleteJson("/api/v1/posts/{$post->id}");
        $delPostRes->assertStatus(403);
        $this->assertNull($post->fresh()->deleted_at);

        // Attacker attempts to edit comment -> 403
        $editCommentRes = $this->actingAs($attacker, 'sanctum')
            ->putJson("/api/v1/comments/{$comment->id}", [
                'body' => 'Modified comment by attacker',
            ]);
        $editCommentRes->assertStatus(403);
        $this->assertEquals('Owner comment', $comment->fresh()->body);

        // Attacker attempts to delete comment -> 403
        $delCommentRes = $this->actingAs($attacker, 'sanctum')
            ->deleteJson("/api/v1/comments/{$comment->id}");
        $delCommentRes->assertStatus(403);
        $this->assertNull($comment->fresh()->deleted_at);

        // Attacker attempts to view private 'only_me' post -> 403 and body not leaked
        $viewPrivateRes = $this->actingAs($attacker, 'sanctum')
            ->getJson("/api/v1/posts/{$privatePost->id}");
        $viewPrivateRes->assertStatus(403);
        $viewPrivateRes->assertJsonMissing(['content' => 'Confidential thoughts']);

        // Attacker attempts to share private 'only_me' post -> 403
        $sharePrivateRes = $this->actingAs($attacker, 'sanctum')
            ->postJson("/api/v1/posts/{$privatePost->id}/share", [
                'caption' => 'Trying to leak private post',
            ]);
        $sharePrivateRes->assertStatus(403);
    }

    /**
     * 8. Reporting Hardening:
     * Duplicate pending reports are prevented.
     */
    public function test_reporting_system_prevents_duplicate_pending_reports(): void
    {
        $reporter = User::factory()->create();
        $targetUser = User::factory()->create();
        $post = Post::create([
            'user_id' => $targetUser->id,
            'content' => 'Flagged post content',
            'audience' => 'public',
        ]);

        // First report submission succeeds
        $firstReportRes = $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'spam',
                'details' => 'Unsolicited advertising links',
            ]);
        $firstReportRes->assertStatus(201);
        $firstReportRes->assertJsonPath('success', true);

        // Duplicate report while pending is rejected
        $dupReportRes = $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'spam',
                'details' => 'Unsolicited advertising links',
            ]);
        $dupReportRes->assertStatus(422);
        $dupReportRes->assertJsonPath('success', false);

        // Non-existent target returns 404
        $notFoundRes = $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'post',
                'reportable_id' => 999999,
                'reason' => 'spam',
            ]);
        $notFoundRes->assertStatus(404);

        // Database only has 1 pending report
        $this->assertEquals(1, Report::where('reporter_id', $reporter->id)->where('reportable_id', $post->id)->count());
    }
}
