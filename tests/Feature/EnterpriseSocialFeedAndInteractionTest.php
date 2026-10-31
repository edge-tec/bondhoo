<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Story;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseSocialFeedAndInteractionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. Test Post Creation, Update, and Deletion.
     */
    public function test_post_creation_update_and_deletion(): void
    {
        $user = User::factory()->create(['name' => 'Mizanur Rahman']);

        // Create Post
        $createRes = $this->actingAs($user)->postJson('/api/v1/posts', [
            'content' => 'Life is better when you are surrounded by good people. ❤️',
            'audience' => 'public',
            'type' => 'text',
        ]);
        $createRes->assertStatus(201);
        $postId = $createRes->json('data.id');
        $this->assertNotNull($postId);

        // Edit Post
        $updateRes = $this->actingAs($user)->putJson("/api/v1/posts/{$postId}", [
            'content' => 'Life is better when you are surrounded by good people. ❤️ [Updated]',
            'audience' => 'friends',
        ]);
        $updateRes->assertStatus(200);

        // Delete Post
        $deleteRes = $this->actingAs($user)->deleteJson("/api/v1/posts/{$postId}");
        $deleteRes->assertStatus(200);
        $this->assertSoftDeleted('posts', ['id' => $postId]);
    }

    /**
     * 2. Test 7 Post Reactions (Like, Love, Care, Haha, Wow, Sad, Angry).
     */
    public function test_post_reactions_all_seven_types(): void
    {
        $author = User::factory()->create();
        $reactor = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'audience' => 'public',
            'status' => 'published',
        ]);

        $reactions = ['like', 'love', 'care', 'haha', 'wow', 'sad', 'angry'];

        foreach ($reactions as $type) {
            $response = $this->actingAs($reactor)->postJson("/api/v1/posts/{$post->id}/react", [
                'type' => $type,
            ]);
            $response->assertStatus(200);
            $this->assertTrue($response->json('data.reacted'));
            $this->assertEquals($type, $response->json('data.type'));
        }
    }

    /**
     * 3. Test Save Post toggle and Share Post.
     */
    public function test_save_and_share_post(): void
    {
        $author = User::factory()->create();
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'audience' => 'public',
            'status' => 'published',
        ]);

        // Toggle Save
        $saveRes = $this->actingAs($user)->postJson('/api/v1/profile/saved-items/toggle', [
            'item_type' => 'post',
            'item_id' => $post->id,
        ]);
        $saveRes->assertStatus(200);

        // Share Post
        $shareRes = $this->actingAs($user)->postJson("/api/v1/posts/{$post->id}/share", [
            'caption' => 'Sharing this wonderful moment!',
        ]);
        $shareRes->assertStatus(200);
        $this->assertEquals(1, $post->fresh()->shares_count);
    }

    /**
     * 4. Test Home Feed with cursor pagination and tab filtering.
     */
    public function test_home_feed_retrieval(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(5)->create([
            'audience' => 'public',
            'status' => 'published',
        ]);

        $feedRes = $this->actingAs($user)->getJson('/api/v1/feed?tab=for_you&per_page=5');
        $feedRes->assertStatus(200);
        $this->assertNotEmpty($feedRes->json('data'));
        $this->assertArrayHasKey('has_more', $feedRes->json('meta'));
    }

    /**
     * 5. Test Threaded Comments: Add, Reply, Edit, Delete, and React.
     */
    public function test_threaded_comments_and_reactions(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'audience' => 'public',
            'status' => 'published',
        ]);

        // Add Top-level Comment
        $commentRes = $this->actingAs($commenter)->postJson("/api/v1/posts/{$post->id}/comments", [
            'body' => 'Absolutely inspiring picture!',
        ]);
        $commentRes->assertStatus(201);
        $commentId = $commentRes->json('data.id');

        // Reply to Comment (Threaded)
        $replyRes = $this->actingAs($author)->postJson("/api/v1/posts/{$post->id}/comments", [
            'body' => 'Thank you so much! ❤️',
            'parent_id' => $commentId,
        ]);
        $replyRes->assertStatus(201);
        $this->assertEquals($commentId, $replyRes->json('data.parent_id'));

        // Edit Comment
        $editRes = $this->actingAs($commenter)->putJson("/api/v1/comments/{$commentId}", [
            'body' => 'Absolutely inspiring picture! Keep shining.',
        ]);
        $editRes->assertStatus(200);

        // React to Comment
        $reactRes = $this->actingAs($author)->postJson("/api/v1/comments/{$commentId}/react", [
            'type' => 'love',
        ]);
        $reactRes->assertStatus(200);

        // Delete Comment
        $deleteRes = $this->actingAs($commenter)->deleteJson("/api/v1/comments/{$commentId}");
        $deleteRes->assertStatus(200);
    }

    /**
     * 6. Test Stories: Create, View, React, Reply, and Viewers list.
     */
    public function test_story_lifecycle_view_reaction_and_reply(): void
    {
        $storyAuthor = User::factory()->create(['name' => 'Sadia Islam']);
        $viewer = User::factory()->create(['name' => 'Mizanur Rahman']);

        // Create Story
        $createRes = $this->actingAs($storyAuthor)->postJson('/api/v1/stories', [
            'type' => 'text',
            'content' => 'Having a wonderful time today!',
            'privacy' => 'public',
        ]);
        $this->assertContains($createRes->status(), [200, 201]);
        $storyId = $createRes->json('data.id');

        // View Story
        $viewRes = $this->actingAs($viewer)->postJson("/api/v1/stories/{$storyId}/view");
        $viewRes->assertStatus(200);
        $this->assertTrue($viewRes->json('data.view_recorded'));

        // Get Story Viewers
        $viewersRes = $this->actingAs($storyAuthor)->getJson("/api/v1/stories/{$storyId}/views");
        $viewersRes->assertStatus(200);
        $this->assertNotEmpty($viewersRes->json('data'));

        // React to Story (v2)
        $reactRes = $this->actingAs($viewer)->postJson("/api/v2/stories/{$storyId}/react", [
            'type' => 'love',
        ]);
        $reactRes->assertStatus(200);

        // Reply to Story (v2)
        $replyRes = $this->actingAs($viewer)->postJson("/api/v2/stories/{$storyId}/reply", [
            'message' => 'Looking so fresh and happy!',
        ]);
        $replyRes->assertStatus(201);
    }
}
