<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_comment_on_post(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'First post',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'body' => 'This is a great thought!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'body' => 'This is a great thought!',
                ],
            ]);

        $this->assertEquals(1, $post->fresh()->comments_count);

        // Verify Redis cache was updated
        $cacheService = app(CacheServiceInterface::class);
        $this->assertEquals(1, (int) $cacheService->get("post:{$post->id}:comments"));
    }

    public function test_user_can_create_nested_reply_to_comment(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $post = Post::create(['user_id' => $user->id, 'content' => 'Post']);

        $parentComment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'Parent comment',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'body' => 'I totally agree with this point.',
                'parent_id' => $parentComment->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'parent_id' => $parentComment->id,
                ],
            ]);

        $this->assertEquals(1, $parentComment->fresh()->replies_count);
    }

    public function test_cannot_comment_if_comments_disabled(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Locked post',
            'comments_disabled' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/posts/{$post->id}/comments", [
                'body' => 'Trying to comment...',
            ]);

        $response->assertStatus(403);
    }
}
