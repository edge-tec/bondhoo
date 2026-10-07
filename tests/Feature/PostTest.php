<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_text_post(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $payload = [
            'content' => 'Hello Jugajug! This is my first post on the platform.',
            'audience' => 'public',
            'type' => 'text',
            'location' => 'Dhaka, Bangladesh',
            'feeling_activity' => 'excited',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/posts', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'content' => 'Hello Jugajug! This is my first post on the platform.',
                    'audience' => 'public',
                    'location' => 'Dhaka, Bangladesh',
                    'feeling_activity' => 'excited',
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'Hello Jugajug! This is my first post on the platform.',
        ]);
    }

    public function test_user_can_create_post_with_attached_media(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'post',
            'disk' => 'public',
            'original_path' => 'posts/1/image.webp',
            'mime_type' => 'image/webp',
            'size' => 1024,
            'processing_status' => 'ready',
        ]);

        $payload = [
            'content' => 'Checking out the sunset!',
            'media_ids' => [$media->id],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/posts', $payload);

        $response->assertStatus(201);
        $postId = $response->json('data.id');

        $this->assertEquals(Post::class, $media->fresh()->mediable_type);
        $this->assertEquals($postId, $media->fresh()->mediable_id);
    }

    public function test_user_can_update_own_post(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Original text',
            'audience' => 'public',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/posts/{$post->id}", [
                'content' => 'Edited text',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'content' => 'Edited text',
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'content' => 'Edited text',
        ]);
    }

    public function test_user_can_delete_own_post(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Delete me',
            'audience' => 'public',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_user_can_pin_and_toggle_comments(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Important notice',
            'is_pinned' => false,
            'comments_disabled' => false,
        ]);

        // Toggle pin
        $pinRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/posts/{$post->id}/pin");
        $pinRes->assertStatus(200)->assertJson(['data' => ['is_pinned' => true]]);
        $this->assertTrue($post->fresh()->is_pinned);

        // Toggle comments
        $commentRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/posts/{$post->id}/toggle-comments");
        $commentRes->assertStatus(200)->assertJson(['data' => ['comments_disabled' => true]]);
        $this->assertTrue($post->fresh()->comments_disabled);
    }
}
