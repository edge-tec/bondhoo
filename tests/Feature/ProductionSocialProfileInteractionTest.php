<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSocialProfileInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_view_displays_canonical_user_identity_correctly(): void
    {
        $userA = User::factory()->create([
            'name' => 'Tariq Rahman',
            'username' => 'tariq_rahman',
        ]);
        UserProfile::updateOrCreate(
            ['user_id' => $userA->id],
            [
                'bio' => 'Building Bangladesh 2.0',
                'city' => 'Dhaka',
                'country' => 'Bangladesh',
            ]
        );

        $userB = User::factory()->create([
            'name' => 'Sabrina Sultana',
            'username' => 'sabrina_sultana',
        ]);
        UserProfile::updateOrCreate(
            ['user_id' => $userB->id],
            [
                'bio' => 'Tech Enthusiast',
            ]
        );

        // Visitor userB views userA profile
        $response = $this->actingAs($userB, 'sanctum')->get("/profile/{$userA->username}");

        $response->assertStatus(200);
        $response->assertSee('Tariq Rahman');
        $response->assertSee('tariq_rahman');
        $response->assertSee('Building Bangladesh 2.0');
        // Must NOT display visitor's name as profile owner
        $response->assertDontSee('Sabrina Sultana কে ফ্রেন্ডলিস্ট থেকে সরাতে চান');
    }

    public function test_complete_friend_request_lifecycle(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        // 1. User A sends friend request to User B
        $sendResponse = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/friends/{$userB->id}/request");

        $sendResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        // Check relationship status from User A perspective
        $statusA = $this->actingAs($userA, 'sanctum')
            ->getJson("/api/v1/friends/status/{$userB->id}");
        $statusA->assertStatus(200)
            ->assertJsonPath('state', 'REQUEST_SENT')
            ->assertJsonPath('is_pending_sent', true);

        // Check relationship status from User B perspective
        $statusB = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/v1/friends/status/{$userA->id}");
        $statusB->assertStatus(200)
            ->assertJsonPath('state', 'REQUEST_RECEIVED')
            ->assertJsonPath('is_pending_received', true);

        // 2. Prevent duplicate friend request
        $duplicateResponse = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/friends/{$userB->id}/request");
        $duplicateResponse->assertStatus(422);

        // 3. User A cancels outgoing friend request
        $cancelResponse = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/friends/{$userB->id}/cancel");
        $cancelResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        // 4. User A sends request again, then User B accepts
        $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/friends/{$userB->id}/request")
            ->assertStatus(201);

        $acceptResponse = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/friends/{$userA->id}/accept");
        $acceptResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // 5. Unfriend
        $unfriendResponse = $this->actingAs($userA, 'sanctum')
            ->deleteJson("/api/v1/friends/{$userB->id}");
        $unfriendResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('friendships', [
            'status' => Friendship::STATUS_ACCEPTED,
        ]);
    }

    public function test_cannot_send_friend_request_to_oneself(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/friends/{$user->id}/request");

        $response->assertStatus(422);
    }

    public function test_post_creation_and_reactions_lifecycle(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        // 1. Create post
        $postResponse = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'Production test status update on Jugajug!',
                'audience' => 'public',
            ]);

        $postResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        $postId = $postResponse->json('data.id');
        $this->assertNotNull($postId);

        // 2. Viewer reacts with 'like'
        $reactResponse = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v1/posts/{$postId}/react", [
                'type' => 'like',
            ]);

        $reactResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reacted', true)
            ->assertJsonPath('data.total_reactions', 1)
            ->assertJsonPath('data.reactions_count', 1);

        $this->assertEquals(1, Post::find($postId)->likes_count);

        // 3. Duplicate reaction toggles off (unlike)
        $unlikeResponse = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v1/posts/{$postId}/react", [
                'type' => 'like',
            ]);

        $unlikeResponse->assertStatus(200)
            ->assertJsonPath('data.reacted', false)
            ->assertJsonPath('data.total_reactions', 0)
            ->assertJsonPath('data.reactions_count', 0);

        $this->assertEquals(0, Post::find($postId)->likes_count);
    }

    public function test_comments_and_nested_replies_lifecycle(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Engaging conversation starter.',
            'audience' => 'public',
        ]);

        // 1. Comment with 'body'
        $c1Response = $this->actingAs($commenter, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'body' => 'First insightful thought!',
            ]);

        $c1Response->assertStatus(201)
            ->assertJsonPath('data.body', 'First insightful thought!')
            ->assertJsonPath('data.content', 'First insightful thought!')
            ->assertJsonPath('data.author.id', $commenter->id)
            ->assertJsonPath('data.user.id', $commenter->id);

        $parentCommentId = $c1Response->json('data.id');

        // 2. Comment with 'content' fallback alias
        $c2Response = $this->actingAs($commenter, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'content' => 'Second comment using content alias!',
            ]);

        $c2Response->assertStatus(201)
            ->assertJsonPath('data.body', 'Second comment using content alias!')
            ->assertJsonPath('data.content', 'Second comment using content alias!');

        // 3. Nested reply to parent comment
        $replyResponse = $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'body' => 'Thank you for your feedback.',
                'parent_id' => $parentCommentId,
            ]);

        $replyResponse->assertStatus(201)
            ->assertJsonPath('data.parent_id', $parentCommentId);

        $this->assertEquals(1, Comment::find($parentCommentId)->replies_count);
        $this->assertEquals(3, $post->fresh()->comments_count);

        // 4. React to comment
        $commentReact = $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/comments/{$parentCommentId}/react", [
                'type' => 'love',
            ]);

        $commentReact->assertStatus(200)
            ->assertJsonPath('data.reacted', true)
            ->assertJsonPath('data.total_reactions', 1);

        // 5. Delete comment
        $deleteResponse = $this->actingAs($commenter, 'sanctum')
            ->deleteJson("/api/v1/comments/{$parentCommentId}");

        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('comments', ['id' => $parentCommentId]);
        $this->assertEquals(2, $post->fresh()->comments_count);
    }

    public function test_post_sharing_flow(): void
    {
        $author = User::factory()->create(['name' => 'Original Author']);
        $sharer = User::factory()->create(['name' => 'Sharer User']);

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Important community announcement.',
            'audience' => 'public',
        ]);

        $shareResponse = $this->actingAs($sharer, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/share", [
                'caption' => 'Sharing this with everyone!',
            ]);

        $shareResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.shares_count', 1);

        $this->assertDatabaseHas('post_shares', [
            'user_id' => $sharer->id,
            'post_id' => $post->id,
            'caption' => 'Sharing this with everyone!',
        ]);

        $this->assertEquals(1, $post->fresh()->shares_count);
    }

    public function test_messenger_direct_conversation_reuse_from_profile(): void
    {
        $userA = User::factory()->create(['name' => 'User A']);
        $userB = User::factory()->create(['name' => 'User B']);

        // 1. Initial direct conversation creation
        $initResponse = $this->actingAs($userA, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'recipient_id' => $userB->id,
            ]);

        $initResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        $convId = $initResponse->json('data.id');
        $this->assertNotNull($convId);

        // 2. Second request to same recipient must reuse existing conversation
        $secondResponse = $this->actingAs($userA, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'recipient_id' => $userB->id,
            ]);

        $secondResponse->assertStatus(201);
        $this->assertEquals($convId, $secondResponse->json('data.id'));

        // 3. User B sends a message in this conversation
        $msgResponse = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/messages", [
                'body' => 'Hello from profile direct chat!',
            ]);

        $msgResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'Hello from profile direct chat!');

        // Confirm both users are participants
        $conv = Conversation::find($convId);
        $this->assertTrue($conv->hasParticipant($userA->id));
        $this->assertTrue($conv->hasParticipant($userB->id));
    }

    public function test_post_menu_management_and_reporting_lifecycle(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Post to manage and moderate.',
            'audience' => 'public',
        ]);

        // 1. Author pins post
        $pinResponse = $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/pin");
        $pinResponse->assertStatus(200);
        $this->assertTrue((bool) $post->fresh()->is_pinned);

        // 2. Author toggles comments
        $toggleCommentsResponse = $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments/toggle");
        $toggleCommentsResponse->assertStatus(200);
        $this->assertTrue((bool) $post->fresh()->comments_disabled);

        // 3. Viewer submits report against post
        $reportResponse = $this->actingAs($viewer, 'sanctum')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'spam',
                'details' => 'Repeated promotion of external link.',
            ]);
        $reportResponse->assertStatus(201)
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('reports', [
            'reporter_id' => $viewer->id,
            'reportable_id' => $post->id,
            'reason' => 'spam',
        ]);

        // 4. Author deletes post
        $deleteResponse = $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/posts/{$post->id}");
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }
}
