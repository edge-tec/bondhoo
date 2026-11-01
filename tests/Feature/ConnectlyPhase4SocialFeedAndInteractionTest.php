<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConnectlyPhase4SocialFeedAndInteractionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    /**
     * 1. Test for_you personalized feed returns cursor paginated posts.
     */
    public function test_for_you_feed_returns_cursor_paginated_posts(): void
    {
        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        // Create several posts
        Post::factory()->count(10)->create([
            'audience' => 'public',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/feed?tab=for_you&per_page=5');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'author',
                        'content',
                        'audience',
                        'counters',
                        'media',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'per_page',
                    'has_more',
                    'next_cursor',
                ],
            ]);

        $this->assertCount(5, $response->json('data'));
        $this->assertTrue($response->json('meta.has_more'));
        $this->assertNotNull($response->json('meta.next_cursor'));
    }

    /**
     * 2. Test following feed only returns posts from followed users and friends.
     */
    public function test_following_feed_filters_by_following_and_friends(): void
    {
        $viewer = User::factory()->create();
        $followedUser = User::factory()->create();
        $unfollowedUser = User::factory()->create();

        // Viewer follows followedUser
        $viewer->following()->attach($followedUser->id);

        // Create posts for both
        $postFromFollowed = Post::factory()->create([
            'user_id' => $followedUser->id,
            'audience' => 'public',
            'content' => 'Post from followed creator',
            'status' => 'published',
        ]);

        $postFromUnfollowed = Post::factory()->create([
            'user_id' => $unfollowedUser->id,
            'audience' => 'public',
            'content' => 'Post from unfollowed stranger',
            'status' => 'published',
        ]);

        Sanctum::actingAs($viewer);
        $response = $this->getJson('/api/v1/feed?tab=following');

        $response->assertOk();
        $postIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($postFromFollowed->id, $postIds);
        $this->assertNotContains($postFromUnfollowed->id, $postIds);
    }

    /**
     * 3. Test post creation with content, hashtags, and audience privacy.
     */
    public function test_post_creation_with_audience_privacy(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'content' => 'Connecting people everywhere! #Connectly #Dhaka',
            'audience' => 'friends',
        ];

        $response = $this->postJson('/api/v1/posts', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.audience', 'friends')
            ->assertJsonPath('data.content', $payload['content']);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'audience' => 'friends',
            'content' => $payload['content'],
        ]);
    }

    /**
     * 4. Test post updating and deleting with authorization.
     */
    public function test_post_update_and_deletion(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'content' => 'Original text',
            'audience' => 'public',
        ]);

        // Other user cannot update
        Sanctum::actingAs($otherUser);
        $this->putJson("/api/v1/posts/{$post->id}", [
            'content' => 'Hacked text',
            'audience' => 'public',
        ])->assertStatus(403);

        // Author can update
        Sanctum::actingAs($author);
        $this->putJson("/api/v1/posts/{$post->id}", [
            'content' => 'Updated text with #fresh thoughts',
            'audience' => 'only_me',
        ])->assertOk()
            ->assertJsonPath('data.content', 'Updated text with #fresh thoughts')
            ->assertJsonPath('data.audience', 'only_me');

        // Author can delete
        $this->deleteJson("/api/v1/posts/{$post->id}")
            ->assertOk();

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    /**
     * 5. Test all 7 supported reaction types, switching, and toggling off.
     */
    public function test_post_reactions_all_seven_types_and_toggle(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'audience' => 'public',
            'status' => 'published',
        ]);
        Sanctum::actingAs($user);

        $reactions = ['like', 'love', 'care', 'haha', 'wow', 'sad', 'angry'];

        foreach ($reactions as $reaction) {
            $res = $this->postJson("/api/v1/posts/{$post->id}/react", [
                'type' => $reaction,
            ]);

            $res->assertOk()
                ->assertJsonPath('data.reacted', true)
                ->assertJsonPath('data.type', $reaction)
                ->assertJsonPath('data.reactions_count', 1);

            $this->assertEquals(1, $post->fresh()->likes_count);
        }

        // Toggle off: reacting with the same reaction removes it
        $resToggle = $this->postJson("/api/v1/posts/{$post->id}/react", [
            'type' => 'angry',
        ]);

        $resToggle->assertOk()
            ->assertJsonPath('data.reacted', false)
            ->assertJsonPath('data.reactions_count', 0);

        $this->assertEquals(0, $post->fresh()->likes_count);
    }

    /**
     * 6. Test top-level comments and nested replies.
     */
    public function test_comments_and_nested_replies(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'audience' => 'public',
        ]);

        Sanctum::actingAs($commenter);

        // 1. Post top-level comment
        $topLevelRes = $this->postJson("/api/v1/posts/{$post->id}/comments", [
            'body' => 'Awesome post brother!',
        ]);

        $topLevelRes->assertStatus(201)
            ->assertJsonPath('data.body', 'Awesome post brother!')
            ->assertJsonPath('data.parent_id', null);

        $commentId = $topLevelRes->json('data.id');

        // 2. Post nested reply
        $replyRes = $this->postJson("/api/v1/posts/{$post->id}/comments", [
            'body' => 'Thank you so much!',
            'parent_id' => $commentId,
        ]);

        $replyRes->assertStatus(201)
            ->assertJsonPath('data.body', 'Thank you so much!')
            ->assertJsonPath('data.parent_id', $commentId);

        // 3. React to comment
        $commentReactRes = $this->postJson("/api/v1/comments/{$commentId}/react", [
            'type' => 'love',
        ]);

        $commentReactRes->assertOk()
            ->assertJsonPath('data.reacted', true)
            ->assertJsonPath('data.type', 'love');

        // 4. Retrieve comments
        $listRes = $this->getJson("/api/v1/posts/{$post->id}/comments");
        $listRes->assertOk();
        $this->assertCount(1, $listRes->json('data')); // top-level only

        // Retrieve replies
        $repliesRes = $this->getJson("/api/v1/posts/{$post->id}/comments?parent_id={$commentId}");
        $repliesRes->assertOk();
        $this->assertCount(1, $repliesRes->json('data'));
    }

    /**
     * 7. Test post sharing and saved items toggle.
     */
    public function test_post_sharing_and_saved_items_toggle(): void
    {
        $viewer = User::factory()->create();
        $post = Post::factory()->create([
            'audience' => 'public',
            'status' => 'published',
        ]);

        Sanctum::actingAs($viewer);

        // Share post
        $shareRes = $this->postJson("/api/v1/posts/{$post->id}/share", [
            'caption' => 'Sharing this insightful post with my network',
        ]);

        $shareRes->assertOk();
        $this->assertEquals(1, $post->fresh()->shares_count);

        // Save post toggle
        $saveRes = $this->postJson('/api/v1/profile/saved-items/toggle', [
            'item_type' => 'post',
            'item_id' => $post->id,
        ]);

        $saveRes->assertOk();
        $this->assertDatabaseHas('saved_items', [
            'user_id' => $viewer->id,
            'item_type' => 'post',
            'item_id' => $post->id,
        ]);
    }

    /**
     * 8. Test post reporting with validation and duplicate pending report prevention.
     */
    public function test_post_reporting_and_duplicate_prevention(): void
    {
        $viewer = User::factory()->create();
        $post = Post::factory()->create(['audience' => 'public']);

        Sanctum::actingAs($viewer);

        // Submit valid report
        $reportRes = $this->postJson('/api/v1/reports', [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'spam',
            'details' => 'This account is spamming external affiliate links.',
        ]);

        $reportRes->assertStatus(201);

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $viewer->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'spam',
            'status' => Report::STATUS_PENDING,
        ]);

        // Duplicate report while pending should return 422
        $dupRes = $this->postJson('/api/v1/reports', [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'spam',
        ]);

        $dupRes->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
