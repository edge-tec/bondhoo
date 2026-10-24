<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Media;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class Task2CompletePlatformPostsFeedTest extends TestCase
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
            'first_name' => $user->first_name ?? 'Social',
            'last_name' => $user->last_name ?? 'User',
        ]);

        return $user;
    }

    /**
     * 1. Post creation with media attachments, updating, and deleting.
     */
    public function test_post_creation_with_media_attachments_update_and_delete(): void
    {
        $author = $this->createUser(['name' => 'Author User']);

        // Create media uploaded by author
        $media1 = Media::factory()->create([
            'user_id' => $author->id,
            'original_path' => 'posts/photo1.jpg',
            'mime_type' => 'image/jpeg',
        ]);
        $media2 = Media::factory()->create([
            'user_id' => $author->id,
            'original_path' => 'posts/photo2.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        // 1. Create Post via API v1 with Media IDs
        $createRes = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'First post with attached photo gallery!',
                'audience' => 'public',
                'media_ids' => [$media1->id, $media2->id],
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'First post with attached photo gallery!')
            ->assertJsonCount(2, 'data.media');

        $postId = $createRes->json('data.id');
        $this->assertDatabaseHas('posts', ['id' => $postId, 'user_id' => $author->id]);

        // Media items are bound to post
        $this->assertEquals(Post::class, $media1->fresh()->mediable_type);
        $this->assertEquals($postId, $media1->fresh()->mediable_id);

        // 2. Update Post content and audience
        $updateRes = $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/posts/{$postId}", [
                'content' => 'Updated post content with high privacy.',
                'audience' => 'friends',
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Updated post content with high privacy.')
            ->assertJsonPath('data.audience', 'friends');

        // 3. Delete Post
        $deleteRes = $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/posts/{$postId}");

        $deleteRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('posts', ['id' => $postId]);
    }

    /**
     * 2. Reactions engine: toggle like/reactions and sync counter.
     */
    public function test_reactions_toggle_and_counter_synchronization(): void
    {
        $author = $this->createUser();
        $reactor = $this->createUser();

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'content' => 'Engaging post for reaction testing',
            'audience' => 'public',
        ]);

        // 1. Add reaction 'love'
        $reactRes = $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", [
                'type' => 'love',
            ]);

        $reactRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reacted', true)
            ->assertJsonPath('data.type', 'love');

        $this->assertEquals(1, $post->fresh()->likes_count);
        $this->assertDatabaseHas('reactions', [
            'user_id' => $reactor->id,
            'reactable_type' => Post::class,
            'reactable_id' => $post->id,
            'type' => 'love',
        ]);

        // 2. Toggle same reaction off
        $toggleOffRes = $this->actingAs($reactor, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/react", [
                'type' => 'love',
            ]);

        $toggleOffRes->assertStatus(200)
            ->assertJsonPath('data.reacted', false);

        $this->assertEquals(0, $post->fresh()->likes_count);
        $this->assertDatabaseMissing('reactions', [
            'user_id' => $reactor->id,
            'reactable_type' => Post::class,
            'reactable_id' => $post->id,
        ]);
    }

    /**
     * 3. Comments and nested replies with counter tracking.
     */
    public function test_comments_and_nested_replies_lifecycle(): void
    {
        $author = $this->createUser();
        $commenter = $this->createUser();

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'content' => 'Discussion starter post',
            'audience' => 'public',
        ]);

        // 1. Post a top-level comment
        $commentRes = $this->actingAs($commenter, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'body' => 'This is an awesome post!',
            ]);

        $commentRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'This is an awesome post!');

        $commentId = $commentRes->json('data.id');
        $this->assertEquals(1, $post->fresh()->comments_count);

        // 2. Post a nested reply to the comment
        $replyRes = $this->actingAs($author, 'sanctum')
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'body' => 'Thank you for your comment!',
                'parent_id' => $commentId,
            ]);

        $replyRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.parent_id', $commentId);

        $replyId = $replyRes->json('data.id');
        $this->assertEquals(2, $post->fresh()->comments_count);
        $this->assertEquals(1, Comment::find($commentId)->replies_count);

        // 3. Post owner can delete comments on their post
        $deleteCommentRes = $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/comments/{$commentId}");

        $deleteCommentRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('comments', ['id' => $commentId]);
    }

    /**
     * 4. Chronological home feed with cursor pagination and eager loading.
     */
    public function test_chronological_feed_with_cursor_pagination_and_eager_loading(): void
    {
        $author = $this->createUser();
        $viewer = $this->createUser();

        // Seed 10 public posts with media attached
        for ($i = 1; $i <= 10; $i++) {
            $post = Post::factory()->create([
                'user_id' => $author->id,
                'content' => "Chronological post #{$i}",
                'audience' => 'public',
                'created_at' => now()->subMinutes(11 - $i),
            ]);

            Media::factory()->create([
                'user_id' => $author->id,
                'mediable_type' => Post::class,
                'mediable_id' => $post->id,
                'original_path' => "posts/media_{$i}.jpg",
            ]);
        }

        // 1. Fetch Page 1 (per_page = 4, reverse-chronological)
        $page1 = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/feed?algorithm=chronological&per_page=4');

        $page1->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('meta.has_more', true);

        $page1Items = $page1->json('data');
        // Reverse-chronological order: latest post (post #10) first
        $this->assertStringContainsString('Chronological post #10', $page1Items[0]['content']);
        // Verify eager loading (media and author profile present)
        $this->assertNotEmpty($page1Items[0]['media']);
        $this->assertNotEmpty($page1Items[0]['author']['name']);

        $nextCursor = $page1->json('meta.next_cursor');
        $this->assertNotNull($nextCursor);

        // 2. Fetch Page 2 using cursor
        $page2 = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/v1/feed?algorithm=chronological&per_page=4&cursor={$nextCursor}");

        $page2->assertStatus(200)
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('meta.has_more', true);

        $page2Items = $page2->json('data');
        // Posts should be different from page 1
        $page1Ids = collect($page1Items)->pluck('id')->all();
        $page2Ids = collect($page2Items)->pluck('id')->all();
        $this->assertEmpty(array_intersect($page1Ids, $page2Ids));
    }

    /**
     * 5. Strict Private Post Privacy: Private/Only_me posts are NEVER returned to unauthorized viewers.
     */
    public function test_private_posts_are_never_returned_to_unauthorized_viewers(): void
    {
        $author = $this->createUser(['name' => 'Secret Keeper']);
        $stranger = $this->createUser(['name' => 'Curious Stranger']);

        // Author creates a private post
        $privatePost = Post::factory()->create([
            'user_id' => $author->id,
            'content' => 'Top secret diary entry: classified plans!',
            'audience' => 'only_me',
        ]);

        // Author creates a public post
        $publicPost = Post::factory()->create([
            'user_id' => $author->id,
            'content' => 'Public hello world message',
            'audience' => 'public',
        ]);

        // A. Author views own feed -> author sees BOTH posts
        $authorFeed = $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/feed?algorithm=chronological');
        $authorFeedPostIds = collect($authorFeed->json('data'))->pluck('id')->all();
        $this->assertContains($privatePost->id, $authorFeedPostIds);
        $this->assertContains($publicPost->id, $authorFeedPostIds);

        // B. Stranger views feed -> stranger NEVER sees the private post
        $strangerFeed = $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/v1/feed?algorithm=chronological');
        $strangerFeedPostIds = collect($strangerFeed->json('data'))->pluck('id')->all();
        $this->assertNotContains($privatePost->id, $strangerFeedPostIds);
        $this->assertContains($publicPost->id, $strangerFeedPostIds);

        // C. Unauthenticated guest views feed -> guest NEVER sees the private post
        $guestFeed = $this->getJson('/api/v1/feed?algorithm=chronological');
        $guestFeedPostIds = collect($guestFeed->json('data'))->pluck('id')->all();
        $this->assertNotContains($privatePost->id, $guestFeedPostIds);
        $this->assertContains($publicPost->id, $guestFeedPostIds);

        // D. Stranger attempts to view private post directly -> 403 Forbidden
        $strangerView = $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/posts/{$privatePost->id}");
        $strangerView->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This post is not available.');

        // E. Stranger attempts to comment on private post -> 403 Forbidden
        $strangerComment = $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/posts/{$privatePost->id}/comments", [
                'body' => 'I should not be able to comment on this',
            ]);
        $strangerComment->assertStatus(403);

        // F. Stranger attempts to react to private post -> 403 Forbidden
        $strangerReact = $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/posts/{$privatePost->id}/react", [
                'type' => 'like',
            ]);
        $strangerReact->assertStatus(403);

        // G. Unauthenticated guest attempts to view private post -> 403 Forbidden
        $guestView = $this->getJson("/api/v1/posts/{$privatePost->id}");
        $guestView->assertStatus(403);
    }

    /**
     * 6. Authorization policies for Posts and Comments.
     */
    public function test_post_and_comment_authorization_policies(): void
    {
        $author = $this->createUser();
        $stranger = $this->createUser();
        $admin = $this->createUser();
        $admin->assignRole('SUPER_ADMIN');

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'content' => 'Policy protected post',
            'audience' => 'public',
        ]);

        $comment = Comment::factory()->create([
            'user_id' => $stranger->id,
            'post_id' => $post->id,
            'body' => 'Stranger comment',
        ]);

        // Post Policy:
        // Author can update & delete
        $this->assertTrue(Gate::forUser($author)->allows('update', $post));
        $this->assertTrue(Gate::forUser($author)->allows('delete', $post));

        // Stranger cannot update or delete author's post
        $this->assertFalse(Gate::forUser($stranger)->allows('update', $post));
        $this->assertFalse(Gate::forUser($stranger)->allows('delete', $post));

        // Admin can delete post
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $post));

        // Comment Policy:
        // Stranger can delete own comment
        $this->assertTrue(Gate::forUser($stranger)->allows('delete', $comment));

        // Author can delete stranger's comment on author's post
        $this->assertTrue(Gate::forUser($author)->allows('delete', $comment));
    }
}
