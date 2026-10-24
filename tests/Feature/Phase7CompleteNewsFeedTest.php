<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Post;
use App\Models\User;
use App\Models\UserFollower;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase7CompleteNewsFeedTest extends TestCase
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
     * Requirement 7.1: Guest viewer only sees published public posts.
     */
    public function test_guest_feed_contains_only_public_published_posts(): void
    {
        $author = $this->createUser();

        Post::create(['user_id' => $author->id, 'content' => 'Public Post', 'audience' => 'public', 'status' => 'published']);
        Post::create(['user_id' => $author->id, 'content' => 'Friends Only Post', 'audience' => 'friends', 'status' => 'published']);
        Post::create(['user_id' => $author->id, 'content' => 'Private Post', 'audience' => 'only_me', 'status' => 'published']);
        Post::create(['user_id' => $author->id, 'content' => 'Draft Post', 'audience' => 'public', 'status' => 'draft']);

        $response = $this->getJson('/api/v1/feed');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $posts = $response->json('data');
        $this->assertCount(1, $posts);
        $this->assertEquals('Public Post', $posts[0]['content']);
    }

    /**
     * Requirement 7.2: Friends and Followers audience aggregation in authenticated feed.
     */
    public function test_authenticated_feed_aggregates_friends_and_followed_posts(): void
    {
        $viewer = $this->createUser();
        $friend = $this->createUser();
        $followed = $this->createUser();
        $stranger = $this->createUser();

        // 1. Establish friendship with $friend
        Friendship::create([
            'user_id' => $viewer->id,
            'friend_id' => $friend->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);
        Friendship::create([
            'user_id' => $friend->id,
            'friend_id' => $viewer->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // 2. Viewer follows $followed
        UserFollower::create([
            'user_id' => $followed->id,
            'follower_id' => $viewer->id,
        ]);

        // Create posts
        $friendPost = Post::create(['user_id' => $friend->id, 'content' => 'Friend special post', 'audience' => 'friends']);
        $followerPost = Post::create(['user_id' => $followed->id, 'content' => 'Follower exclusive post', 'audience' => 'followers']);
        $strangerFriendPost = Post::create(['user_id' => $stranger->id, 'content' => 'Stranger friend post', 'audience' => 'friends']);
        $strangerPublicPost = Post::create(['user_id' => $stranger->id, 'content' => 'Stranger public post', 'audience' => 'public']);
        $viewerOwnPost = Post::create(['user_id' => $viewer->id, 'content' => 'My personal thought', 'audience' => 'only_me']);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/feed?algorithm=chronological');

        $response->assertStatus(200);

        $feedIds = collect($response->json('data'))->pluck('id')->all();

        // Viewer should see:
        // - friend's friend-audience post
        // - followed user's follower-audience post
        // - stranger's public post
        // - their own only_me post
        $this->assertContains($friendPost->id, $feedIds);
        $this->assertContains($followerPost->id, $feedIds);
        $this->assertContains($strangerPublicPost->id, $feedIds);
        $this->assertContains($viewerOwnPost->id, $feedIds);

        // Viewer should NOT see stranger's friend-audience post
        $this->assertNotContains($strangerFriendPost->id, $feedIds);
    }

    /**
     * Requirement 7.3: Strict bidirectional block isolation in feed.
     */
    public function test_blocked_users_are_strictly_excluded_from_feed(): void
    {
        $viewer = $this->createUser();
        $blockedUser = $this->createUser();
        $normalUser = $this->createUser();

        // Viewer blocks $blockedUser
        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v1/users/{$blockedUser->id}/block")
            ->assertStatus(200);

        $blockedPost = Post::create([
            'user_id' => $blockedUser->id,
            'content' => 'Public post by blocked user',
            'audience' => 'public',
        ]);

        $normalPost = Post::create([
            'user_id' => $normalUser->id,
            'content' => 'Public post by normal user',
            'audience' => 'public',
        ]);

        // 1. Viewer's feed should not have blockedUser's post
        $viewerFeed = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/feed');
        $viewerFeedIds = collect($viewerFeed->json('data'))->pluck('id')->all();

        $this->assertNotContains($blockedPost->id, $viewerFeedIds);
        $this->assertContains($normalPost->id, $viewerFeedIds);

        // 2. BlockedUser's feed should not have viewer's public post (bidirectional)
        $viewerPublicPost = Post::create([
            'user_id' => $viewer->id,
            'content' => 'Public post by blocker',
            'audience' => 'public',
        ]);

        $blockedUserFeed = $this->actingAs($blockedUser, 'sanctum')->getJson('/api/v1/feed');
        $blockedFeedIds = collect($blockedUserFeed->json('data'))->pluck('id')->all();

        $this->assertNotContains($viewerPublicPost->id, $blockedFeedIds);
    }

    /**
     * Requirement 7.4: Ranking Algorithms (Chronological vs Smart vs Pinned).
     */
    public function test_feed_ranking_algorithms(): void
    {
        $viewer = $this->createUser();
        $author = $this->createUser();

        // 1. Pinned post (should always be on top)
        $pinned = Post::create([
            'user_id' => $author->id,
            'content' => 'Important Pinned Announcement',
            'audience' => 'public',
            'is_pinned' => true,
            'likes_count' => 0,
        ]);

        // 2. Highly engaged older post
        $viral = Post::create([
            'user_id' => $author->id,
            'content' => 'Viral post with 100 likes',
            'audience' => 'public',
            'is_pinned' => false,
            'likes_count' => 100,
            'comments_count' => 50,
            'shares_count' => 20,
        ]);

        // 3. Brand new post with 0 engagement
        $newPost = Post::create([
            'user_id' => $author->id,
            'content' => 'Fresh new post with zero likes',
            'audience' => 'public',
            'is_pinned' => false,
            'likes_count' => 0,
            'comments_count' => 0,
            'shares_count' => 0,
        ]);

        // Smart feed check
        $smartRes = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/feed?algorithm=smart');
        $smartRes->assertStatus(200);
        $smartIds = collect($smartRes->json('data'))->pluck('id')->all();

        // In Smart feed: Pinned first, then Viral, then NewPost
        $this->assertEquals($pinned->id, $smartIds[0]);
        $this->assertEquals($viral->id, $smartIds[1]);
        $this->assertEquals($newPost->id, $smartIds[2]);

        // Chronological feed check
        $chronoRes = $this->actingAs($viewer, 'sanctum')->getJson('/api/v1/feed?algorithm=chronological');
        $chronoRes->assertStatus(200);
        $chronoIds = collect($chronoRes->json('data'))->pluck('id')->all();

        // In Chronological feed: Pinned first, then NewPost (highest ID), then Viral
        $this->assertEquals($pinned->id, $chronoIds[0]);
        $this->assertEquals($newPost->id, $chronoIds[1]);
        $this->assertEquals($viral->id, $chronoIds[2]);
    }

    /**
     * Requirement 7.5: Cursor-Based Pagination forward navigation.
     */
    public function test_cursor_based_pagination_navigation(): void
    {
        $author = $this->createUser();

        for ($i = 1; $i <= 10; $i++) {
            Post::create([
                'user_id' => $author->id,
                'content' => "Cursor item {$i}",
                'audience' => 'public',
            ]);
        }

        // Page 1: Request 4 items
        $page1 = $this->getJson('/api/v1/feed?per_page=4&algorithm=chronological');
        $page1->assertStatus(200);

        $this->assertCount(4, $page1->json('data'));
        $this->assertTrue($page1->json('meta.has_more'));
        $nextCursor = $page1->json('meta.next_cursor');
        $this->assertNotNull($nextCursor);

        // Page 2: Request next 4 items using cursor
        $page2 = $this->getJson("/api/v1/feed?per_page=4&algorithm=chronological&cursor={$nextCursor}");
        $page2->assertStatus(200);

        $this->assertCount(4, $page2->json('data'));
        $this->assertTrue($page2->json('meta.has_more'));

        // Ensure distinct items across pages
        $page1Ids = collect($page1->json('data'))->pluck('id')->all();
        $page2Ids = collect($page2->json('data'))->pluck('id')->all();
        $this->assertEmpty(array_intersect($page1Ids, $page2Ids));
    }
}
