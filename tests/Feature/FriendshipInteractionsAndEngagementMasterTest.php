<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\PrivacySetting;
use App\Models\Reaction;
use App\Models\User;
use App\Models\UserFollower;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendshipInteractionsAndEngagementMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Helper to create a user with a profile and default privacy settings.
     */
    protected function createTestUser(string $username, array $privacyOverrides = []): User
    {
        static $counter = 200;
        $counter++;

        $user = User::factory()->create([
            'username' => $username,
            'name' => ucfirst($username).' User',
            'email' => "{$username}{$counter}@example.com",
            'phone' => "+880180000{$counter}",
            'status' => 'active',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => ucfirst($username),
            'last_name' => 'User',
            'display_name' => ucfirst($username).' User',
            'avatar_url' => 'https://example.com/default-avatar.png',
        ]);

        UserSetting::create([
            'user_id' => $user->id,
            'who_can_send_friend_requests' => $privacyOverrides['who_can_send_friend_requests'] ?? 'everyone',
            'who_can_follow_me' => $privacyOverrides['who_can_follow_me'] ?? 'everyone',
        ]);

        PrivacySetting::create(array_merge([
            'user_id' => $user->id,
            'profile_visibility' => 'public',
            'who_can_send_friend_requests' => 'everyone',
            'who_can_follow_me' => 'everyone',
            'post_default_privacy' => 'public',
        ], $privacyOverrides));

        return $user;
    }

    /**
     * Test 1: Full friend request lifecycle, duplicate prevention, and self-request blocking.
     */
    public function test_friend_request_lifecycle_and_duplicate_prevention(): void
    {
        $alice = $this->createTestUser('alice');
        $bob = $this->createTestUser('bob');

        // 1. Cannot send request to self
        $selfResp = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$alice->id}/request");
        $selfResp->assertStatus(422)
            ->assertJson(['success' => false]);

        // 2. Alice sends request to Bob
        $sendResp = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");
        $sendResp->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Friend request sent successfully.',
            ]);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $alice->id,
            'friend_id' => $bob->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        // 3. Duplicate request while pending must fail
        $dupResp = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");
        $dupResp->assertStatus(422)
            ->assertJson(['success' => false]);

        // 4. Bob sees pending request
        $requestsResp = $this->actingAs($bob, 'sanctum')
            ->getJson('/api/v1/friends/requests');
        $requestsResp->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'total']]);

        // 5. Alice sees sent pending request
        $sentResp = $this->actingAs($alice, 'sanctum')
            ->getJson('/api/v1/friends/requests/sent');
        $sentResp->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'total']]);

        // 6. Bob accepts friend request
        $acceptResp = $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/friends/{$alice->id}/accept");
        $acceptResp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Friend request accepted.',
            ]);

        $this->assertDatabaseHas('friendships', [
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // 7. Sending request when already friends must fail
        $alreadyFriendsResp = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");
        $alreadyFriendsResp->assertStatus(422)
            ->assertJson(['success' => false]);

        // 8. Friends list contains the accepted friend with pagination
        $friendsResp = $this->actingAs($alice, 'sanctum')
            ->getJson('/api/v1/friends');
        $friendsResp->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total']]);

        // 9. Unfriend
        $unfriendResp = $this->actingAs($alice, 'sanctum')
            ->deleteJson("/api/v1/friends/{$bob->id}");
        $unfriendResp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Friend removed successfully.',
            ]);
    }

    /**
     * Test 2: Cross friend requests automatically accept.
     */
    public function test_cross_friend_request_auto_accepts(): void
    {
        $charlie = $this->createTestUser('charlie');
        $david = $this->createTestUser('david');

        // Charlie sends request to David
        $this->actingAs($charlie, 'sanctum')
            ->postJson("/api/v1/friends/{$david->id}/request")
            ->assertStatus(201);

        // David sends request back to Charlie -> auto-accepts
        $crossResp = $this->actingAs($david, 'sanctum')
            ->postJson("/api/v1/friends/{$charlie->id}/request");

        $crossResp->assertStatus(201);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $charlie->id,
            'friend_id' => $david->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);
    }

    /**
     * Test 3: Friend request privacy rejection when recipient disallows requests.
     */
    public function test_friend_request_privacy_restriction(): void
    {
        $privateUser = $this->createTestUser('privateguy', [
            'who_can_send_friend_requests' => 'nobody',
        ]);
        $requester = $this->createTestUser('requester');

        $response = $this->actingAs($requester, 'sanctum')
            ->postJson("/api/v1/friends/{$privateUser->id}/request");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'This user is not accepting friend requests.',
            ]);
    }

    /**
     * Test 4: Follow/unfollow lifecycle, duplicate prevention, and lists with pagination.
     */
    public function test_follow_unfollow_lifecycle_and_duplicate_prevention(): void
    {
        $emma = $this->createTestUser('emma');
        $frank = $this->createTestUser('frank');

        // 1. Cannot follow self
        $selfFollow = $this->actingAs($emma, 'sanctum')
            ->postJson("/api/v1/users/{$emma->id}/follow");
        $selfFollow->assertStatus(422)
            ->assertJson(['success' => false]);

        // 2. Emma follows Frank
        $followResp = $this->actingAs($emma, 'sanctum')
            ->postJson("/api/v1/users/{$frank->id}/follow");
        $followResp->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User followed successfully.',
            ]);

        $this->assertDatabaseHas('user_followers', [
            'user_id' => $frank->id,
            'follower_id' => $emma->id,
        ]);

        // 3. Duplicate follow is idempotent / handled safely
        $dupFollow = $this->actingAs($emma, 'sanctum')
            ->postJson("/api/v1/users/{$frank->id}/follow");
        $dupFollow->assertStatus(201);
        $this->assertEquals(1, UserFollower::where('user_id', $frank->id)->where('follower_id', $emma->id)->count());

        // 4. Emma checks following list
        $followingResp = $this->actingAs($emma, 'sanctum')
            ->getJson('/api/v1/following');
        $followingResp->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'total']]);

        // 5. Frank checks followers list
        $followersResp = $this->actingAs($frank, 'sanctum')
            ->getJson('/api/v1/followers');
        $followersResp->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'total']]);

        // 6. Emma unfollows Frank
        $unfollowResp = $this->actingAs($emma, 'sanctum')
            ->deleteJson("/api/v1/users/{$frank->id}/follow");
        $unfollowResp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User unfollowed successfully.',
            ]);

        $this->assertDatabaseMissing('user_followers', [
            'user_id' => $frank->id,
            'follower_id' => $emma->id,
        ]);
    }

    /**
     * Test 5: Post & comment reactions with toggle-off and switch-type duplicate prevention.
     */
    public function test_post_and_comment_reactions_and_toggle_switch(): void
    {
        $author = $this->createTestUser('postauthor');
        $reactor = $this->createTestUser('postreactor');

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Exciting updates on Jugajug platform!',
            'audience' => 'public',
        ]);

        // 1. React with 'love'
        $reactLove = $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'love']);
        $reactLove->assertStatus(200)
            ->assertJsonPath('data.reacted', true)
            ->assertJsonPath('data.type', 'love');

        $this->assertDatabaseHas('reactions', [
            'user_id' => $reactor->id,
            'reactable_type' => Post::class,
            'reactable_id' => $post->id,
            'type' => 'love',
        ]);

        // 2. Reacting again with 'love' toggles it OFF (removes reaction)
        $toggleOff = $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'love']);
        $toggleOff->assertStatus(200)
            ->assertJsonPath('data.reacted', false);

        $this->assertDatabaseMissing('reactions', [
            'user_id' => $reactor->id,
            'reactable_type' => Post::class,
            'reactable_id' => $post->id,
        ]);

        // 3. React with 'haha'
        $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'haha'])
            ->assertStatus(200);

        // 4. Switch reaction type to 'wow' -> Exactly 1 reaction record maintained
        $switchType = $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", ['type' => 'wow']);
        $switchType->assertStatus(200)
            ->assertJsonPath('data.reacted', true)
            ->assertJsonPath('data.type', 'wow');

        $this->assertEquals(1, Reaction::where('user_id', $reactor->id)
            ->where('reactable_type', Post::class)
            ->where('reactable_id', $post->id)
            ->count());

        // 5. React to comment
        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'body' => 'Great comments welcome here!',
        ]);

        $commentReact = $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/comments/{$comment->id}/react", ['type' => 'like']);
        $commentReact->assertStatus(200)
            ->assertJsonPath('data.reacted', true)
            ->assertJsonPath('data.type', 'like');
    }

    /**
     * Test 6: Comments, nested replies, pagination, and authorization policy checks.
     */
    public function test_comments_and_nested_replies_with_pagination(): void
    {
        $author = $this->createTestUser('cauthor');
        $commenter = $this->createTestUser('commenter');
        $replier = $this->createTestUser('replier');

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Discussion topic for today',
            'audience' => 'public',
        ]);

        // 1. Commenter creates top-level comment
        $commentResp = $this->actingAs($commenter, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'body' => 'This is an insightful post.',
            ]);
        $commentResp->assertStatus(201)
            ->assertJsonPath('data.body', 'This is an insightful post.');

        $commentId = $commentResp->json('data.id');

        // 2. Replier creates nested reply
        $replyResp = $this->actingAs($replier, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'body' => 'I completely agree with this view.',
                'parent_id' => $commentId,
            ]);
        $replyResp->assertStatus(201)
            ->assertJsonPath('data.parent_id', $commentId);

        // 3. Fetch comments with pagination
        $listComments = $this->actingAs($commenter, 'sanctum')
            ->getJson("/api/v1/posts/{$post->id}/comments?per_page=10");
        $listComments->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total']]);

        // 4. Fetch nested replies
        $listReplies = $this->actingAs($commenter, 'sanctum')
            ->getJson("/api/v1/posts/{$post->id}/comments?parent_id={$commentId}");
        $listReplies->assertStatus(200)
            ->assertJsonPath('data.0.body', 'I completely agree with this view.');

        // 5. Update comment by author
        $updateResp = $this->actingAs($commenter, 'sanctum')
            ->putJson("/api/v1/comments/{$commentId}", [
                'body' => 'This is an edited insightful post.',
            ]);
        $updateResp->assertStatus(200);
        $this->assertDatabaseHas('comments', ['id' => $commentId, 'body' => 'This is an edited insightful post.']);

        // 6. Unauthorized update attempt by replier on commenter's comment
        $unauthUpdate = $this->actingAs($replier, 'sanctum')
            ->putJson("/api/v1/comments/{$commentId}", [
                'body' => 'Malicious edit attempt.',
            ]);
        $unauthUpdate->assertStatus(403);

        // 7. Delete comment
        $deleteResp = $this->actingAs($commenter, 'sanctum')
            ->deleteJson("/api/v1/comments/{$commentId}");
        $deleteResp->assertStatus(200);
    }

    /**
     * Test 7: Post sharing increments counter and records share.
     */
    public function test_post_sharing_and_counter_increment(): void
    {
        $author = $this->createTestUser('shareauthor');
        $sharer = $this->createTestUser('sharer');

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Public announcement to be shared widely.',
            'audience' => 'public',
        ]);

        $shareResp = $this->actingAs($sharer, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/share", [
                'caption' => 'Sharing this important post!',
            ]);

        $shareResp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'shares_count' => 1,
                ],
            ]);

        $this->assertDatabaseHas('post_shares', [
            'user_id' => $sharer->id,
            'post_id' => $post->id,
            'caption' => 'Sharing this important post!',
        ]);

        $this->assertEquals(1, $post->fresh()->shares_count);
    }

    /**
     * Test 8: Privacy & authorization enforcement: non-friends cannot react, comment, or share private/friends posts.
     */
    public function test_privacy_enforcement_for_reactions_comments_and_shares(): void
    {
        $author = $this->createTestUser('privateauthor');
        $stranger = $this->createTestUser('stranger');

        // Author creates a friends-only post
        $friendsPost = Post::create([
            'user_id' => $author->id,
            'content' => 'Friends only secret update',
            'audience' => 'friends',
        ]);

        // 1. Stranger cannot react to friends-only post
        $strangerReact = $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/posts/{$friendsPost->id}/react", ['type' => 'like']);
        $strangerReact->assertStatus(403);

        // 2. Stranger cannot comment on friends-only post
        $strangerComment = $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/posts/{$friendsPost->id}/comments", ['body' => 'I should not be able to comment']);
        $strangerComment->assertStatus(403);

        // 3. Stranger cannot share friends-only post
        $strangerShare = $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/posts/{$friendsPost->id}/share", ['caption' => 'Unauthorized share']);
        $strangerShare->assertStatus(403);

        // 4. Stranger becomes accepted friend of Author
        Friendship::create([
            'user_id' => $author->id,
            'friend_id' => $stranger->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Now as a friend, actions succeed!
        $friendReact = $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/posts/{$friendsPost->id}/react", ['type' => 'like']);
        $friendReact->assertStatus(200);

        $friendComment = $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/posts/{$friendsPost->id}/comments", ['body' => 'Now as a friend I can comment']);
        $friendComment->assertStatus(201);

        $friendShare = $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/posts/{$friendsPost->id}/share");
        $friendShare->assertStatus(200);
    }
}
