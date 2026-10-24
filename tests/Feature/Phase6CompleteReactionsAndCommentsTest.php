<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Contracts\CacheServiceInterface;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6CompleteReactionsAndCommentsTest extends TestCase
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
            'first_name' => $user->first_name ?? 'Test',
            'last_name' => $user->last_name ?? 'User',
        ]);

        return $user;
    }

    /**
     * Test 1: User can react to a post with different reaction types and sync counters.
     */
    public function test_user_can_react_to_post_and_sync_counters(): void
    {
        $author = $this->createUser();
        $reactor = $this->createUser();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Building enterprise social platform',
            'audience' => 'public',
        ]);

        $response = $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", [
                'type' => 'love',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'reacted' => true,
                    'type' => 'love',
                    'total_reactions' => 1,
                    'breakdown' => [
                        'love' => 1,
                    ],
                ],
            ]);

        $this->assertEquals(1, $post->fresh()->likes_count);

        $cache = app(CacheServiceInterface::class);
        $this->assertEquals(1, (int) $cache->get("post:{$post->id}:reactions"));
    }

    /**
     * Test 2: Toggling the same reaction removes it.
     */
    public function test_user_can_toggle_off_reaction(): void
    {
        $author = $this->createUser();
        $reactor = $this->createUser();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Toggle reaction test',
            'audience' => 'public',
        ]);

        // React with 'haha'
        $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'haha'])
            ->assertStatus(200);

        $this->assertEquals(1, $post->fresh()->likes_count);

        // React with 'haha' again to toggle off
        $res = $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'haha']);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'reacted' => false,
                    'type' => null,
                    'total_reactions' => 0,
                ],
            ]);

        $this->assertEquals(0, $post->fresh()->likes_count);
    }

    /**
     * Test 3: Switching reaction type keeps total same but updates breakdown.
     */
    public function test_user_can_switch_reaction_type(): void
    {
        $author = $this->createUser();
        $reactor = $this->createUser();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Switch reaction test',
            'audience' => 'public',
        ]);

        // First react with 'like'
        $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'like'])
            ->assertStatus(200);

        $this->assertEquals(1, $post->fresh()->likes_count);

        // Switch to 'wow'
        $response = $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'wow']);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'reacted' => true,
                    'type' => 'wow',
                    'total_reactions' => 1,
                    'breakdown' => [
                        'wow' => 1,
                    ],
                ],
            ]);

        $this->assertEquals(1, $post->fresh()->likes_count);
    }

    /**
     * Test 4: Can retrieve reaction breakdown and reactor list.
     */
    public function test_can_retrieve_post_reaction_breakdown_and_users(): void
    {
        $author = $this->createUser(['name' => 'Author User']);
        $post = Post::create(['user_id' => $author->id, 'content' => 'Public post', 'audience' => 'public']);

        $user1 = $this->createUser(['name' => 'Alice Johnson', 'username' => 'alice']);
        $user2 = $this->createUser(['name' => 'Bob Smith', 'username' => 'bob']);

        $this->actingAs($user1, 'sanctum')->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'care']);
        $this->actingAs($user2, 'sanctum')->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'sad']);

        $response = $this->getJson("/api/v1/posts/{$post->id}/reactions");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 2,
                    'breakdown' => [
                        'care' => 1,
                        'sad' => 1,
                    ],
                ],
            ]);

        $users = $response->json('data.users');
        $this->assertCount(2, $users);
    }

    /**
     * Test 5: User can create comment on post and updates comments count and cache.
     */
    public function test_user_can_comment_on_post(): void
    {
        $author = $this->createUser();
        $commenter = $this->createUser(['name' => 'Commenter']);

        $post = Post::create(['user_id' => $author->id, 'content' => 'Main post', 'audience' => 'public']);

        $response = $this->actingAs($commenter, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'body' => 'This is an insightful comment!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'body' => 'This is an insightful comment!',
                    'post_id' => $post->id,
                    'parent_id' => null,
                ],
            ]);

        $this->assertEquals(1, $post->fresh()->comments_count);

        $cache = app(CacheServiceInterface::class);
        $this->assertEquals(1, (int) $cache->get("post:{$post->id}:comments"));
    }

    /**
     * Test 6: User can create nested reply to a comment.
     */
    public function test_user_can_create_nested_reply_to_comment(): void
    {
        $author = $this->createUser();
        $commenter1 = $this->createUser();
        $commenter2 = $this->createUser();

        $post = Post::create(['user_id' => $author->id, 'content' => 'Post with thread', 'audience' => 'public']);

        $parentComment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $commenter1->id,
            'body' => 'Root parent comment',
        ]);
        $post->increment('comments_count');

        $response = $this->actingAs($commenter2, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'body' => 'Replying to your comment.',
                'parent_id' => $parentComment->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'body' => 'Replying to your comment.',
                    'parent_id' => $parentComment->id,
                ],
            ]);

        $this->assertEquals(1, $parentComment->fresh()->replies_count);
        $this->assertEquals(2, $post->fresh()->comments_count);
    }

    /**
     * Test 7: Retrieve comments and nested replies via query params.
     */
    public function test_can_list_comments_and_nested_replies(): void
    {
        $author = $this->createUser();
        $post = Post::create(['user_id' => $author->id, 'content' => 'Discussion', 'audience' => 'public']);

        $c1 = Comment::create(['post_id' => $post->id, 'user_id' => $author->id, 'body' => 'Root 1']);
        $c2 = Comment::create(['post_id' => $post->id, 'user_id' => $author->id, 'body' => 'Root 2']);
        $reply = Comment::create(['post_id' => $post->id, 'user_id' => $author->id, 'parent_id' => $c1->id, 'body' => 'Reply to Root 1']);

        // 1. Root comments only
        $rootRes = $this->getJson("/api/v1/posts/{$post->id}/comments");
        $rootRes->assertStatus(200);
        $rootData = $rootRes->json('data');
        $this->assertCount(2, $rootData);

        // 2. Replies to Root 1
        $replyRes = $this->getJson("/api/v1/posts/{$post->id}/comments?parent_id={$c1->id}");
        $replyRes->assertStatus(200);
        $replyData = $replyRes->json('data');
        $this->assertCount(1, $replyData);
        $this->assertEquals('Reply to Root 1', $replyData[0]['body']);
    }

    /**
     * Test 8: React to a comment.
     */
    public function test_user_can_react_to_comment(): void
    {
        $author = $this->createUser();
        $reactor = $this->createUser();
        $post = Post::create(['user_id' => $author->id, 'content' => 'Comment reaction post', 'audience' => 'public']);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'body' => 'Great point!',
        ]);

        $response = $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/comments/{$comment->id}/react", [
                'type' => 'love',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'reacted' => true,
                    'type' => 'love',
                    'total_reactions' => 1,
                ],
            ]);

        $this->assertEquals(1, $comment->fresh()->likes_count);
    }

    /**
     * Test 9: Author can update comment; unauthorized user is forbidden (403).
     */
    public function test_comment_update_authorization(): void
    {
        $author = $this->createUser();
        $stranger = $this->createUser();
        $post = Post::create(['user_id' => $author->id, 'content' => 'Post', 'audience' => 'public']);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'body' => 'Original text',
        ]);

        // Unauthorized stranger fails
        $forbiddenRes = $this->actingAs($stranger, 'sanctum')
            ->putJson("/api/v1/comments/{$comment->id}", [
                'body' => 'Hacked text',
            ]);
        $forbiddenRes->assertStatus(403);

        // Author succeeds
        $authorRes = $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/comments/{$comment->id}", [
                'body' => 'Edited text by author',
            ]);
        $authorRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'body' => 'Edited text by author',
                ],
            ]);

        $this->assertEquals('Edited text by author', $comment->fresh()->body);
    }

    /**
     * Test 10: Author and Post Owner can delete comment; unauthorized stranger cannot.
     */
    public function test_comment_deletion_authorization_and_counters(): void
    {
        $postOwner = $this->createUser();
        $commentAuthor = $this->createUser();
        $stranger = $this->createUser();

        $post = Post::create(['user_id' => $postOwner->id, 'content' => 'Post by owner', 'audience' => 'public', 'comments_count' => 2]);

        $comment1 = Comment::create(['post_id' => $post->id, 'user_id' => $commentAuthor->id, 'body' => 'Comment 1']);
        $comment2 = Comment::create(['post_id' => $post->id, 'user_id' => $commentAuthor->id, 'body' => 'Comment 2']);

        // 1. Stranger tries to delete comment1 -> 403 Forbidden
        $this->actingAs($stranger, 'sanctum')
            ->deleteJson("/api/v1/comments/{$comment1->id}")
            ->assertStatus(403);

        // 2. Comment author deletes comment1 -> succeeds
        $this->actingAs($commentAuthor, 'sanctum')
            ->deleteJson("/api/v1/comments/{$comment1->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('comments', ['id' => $comment1->id]);
        $this->assertEquals(1, $post->fresh()->comments_count);

        // 3. Post owner deletes comment2 -> succeeds
        $this->actingAs($postOwner, 'sanctum')
            ->deleteJson("/api/v1/comments/{$comment2->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('comments', ['id' => $comment2->id]);
        $this->assertEquals(0, $post->fresh()->comments_count);
    }

    /**
     * Test 11: Cannot comment if post comments are disabled.
     */
    public function test_cannot_comment_when_comments_disabled(): void
    {
        $author = $this->createUser();
        $commenter = $this->createUser();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Comments locked',
            'audience' => 'public',
            'comments_disabled' => true,
        ]);

        $response = $this->actingAs($commenter, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'body' => 'Trying to post comment...',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test 12: Blocked user cannot react or comment on post.
     */
    public function test_blocked_user_cannot_react_or_comment(): void
    {
        $author = $this->createUser();
        $blockedUser = $this->createUser();

        // Author blocks user via standard endpoint
        $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/users/{$blockedUser->id}/block")
            ->assertStatus(200);

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Protected post',
            'audience' => 'public',
        ]);

        // Attempt reaction -> 403 Forbidden
        $reactRes = $this->actingAs($blockedUser, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'like']);
        $reactRes->assertStatus(403);

        // Attempt comment -> 403 Forbidden
        $commentRes = $this->actingAs($blockedUser, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", ['body' => 'Spam comment']);
        $commentRes->assertStatus(403);
    }
}
