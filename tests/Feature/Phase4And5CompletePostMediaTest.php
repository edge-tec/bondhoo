<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase4And5CompletePostMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        Queue::fake();
    }

    /**
     * Requirement 4.1: Create Text-Only Post
     */
    public function test_phase4_user_can_create_text_post(): void
    {
        $author = User::factory()->create(['name' => 'Author One']);

        $payload = [
            'content' => 'Exciting news! Launching our platform today.',
            'audience' => 'public',
            'type' => 'text',
            'location' => 'Dhaka',
            'feeling_activity' => 'celebrating',
        ];

        $response = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/posts', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Post created successfully.',
                'data' => [
                    'content' => 'Exciting news! Launching our platform today.',
                    'audience' => 'public',
                    'location' => 'Dhaka',
                    'feeling_activity' => 'celebrating',
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $author->id,
            'content' => 'Exciting news! Launching our platform today.',
            'audience' => 'public',
        ]);
    }

    /**
     * Requirement 5.1 & 5.2: Upload Multiple Media and Attach to Post
     */
    public function test_phase5_upload_multiple_media_and_attach_to_post(): void
    {
        $author = User::factory()->create(['name' => 'Photographer']);

        // 1. Upload first media
        $file1 = UploadedFile::fake()->image('nature1.jpg', 1200, 800);
        $uploadRes1 = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/media/upload', [
                'file' => $file1,
                'collection' => 'post',
            ]);
        $uploadRes1->assertStatus(201);
        $mediaId1 = $uploadRes1->json('data.id');

        // 2. Upload second media
        $file2 = UploadedFile::fake()->image('nature2.webp', 1000, 700);
        $uploadRes2 = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/media/upload', [
                'file' => $file2,
                'collection' => 'post',
            ]);
        $uploadRes2->assertStatus(201);
        $mediaId2 = $uploadRes2->json('data.id');

        // 3. Create post attaching both media items
        $postRes = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'Gallery of our summer trip!',
                'audience' => 'public',
                'type' => 'media',
                'media_ids' => [$mediaId1, $mediaId2],
            ]);

        $postRes->assertStatus(201);
        $postId = $postRes->json('data.id');

        // Verify polymorphic connection in database
        $this->assertEquals(Post::class, Media::find($mediaId1)->mediable_type);
        $this->assertEquals($postId, Media::find($mediaId1)->mediable_id);
        $this->assertEquals(Post::class, Media::find($mediaId2)->mediable_type);
        $this->assertEquals($postId, Media::find($mediaId2)->mediable_id);

        // Verify post response contains media items
        $this->assertCount(2, $postRes->json('data.media'));
    }

    /**
     * Requirement 4.2: View Single Post with Media and Author Details
     */
    public function test_phase4_view_single_post_with_author_and_media(): void
    {
        $author = User::factory()->create(['name' => 'Viewable Author', 'username' => 'viewable_u']);
        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'A post to view',
            'audience' => 'public',
        ]);

        $media = Media::create([
            'user_id' => $author->id,
            'mediable_type' => Post::class,
            'mediable_id' => $post->id,
            'collection' => 'post',
            'disk' => 'public',
            'original_path' => 'posts/1/image.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'processing_status' => 'completed',
        ]);

        $response = $this->getJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $post->id,
                    'content' => 'A post to view',
                    'author' => [
                        'id' => $author->id,
                        'username' => 'viewable_u',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data.media'));
    }

    /**
     * Requirement 4.3: Edit / Update Post Content, Audience, and Media
     */
    public function test_phase4_author_can_edit_post_content_and_media(): void
    {
        $author = User::factory()->create();
        $media1 = Media::create([
            'user_id' => $author->id,
            'collection' => 'post',
            'disk' => 'public',
            'original_path' => 'posts/old/1.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'processing_status' => 'completed',
        ]);

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Draft title',
            'audience' => 'public',
        ]);

        $media1->update([
            'mediable_type' => Post::class,
            'mediable_id' => $post->id,
        ]);

        $media2 = Media::create([
            'user_id' => $author->id,
            'collection' => 'post',
            'disk' => 'public',
            'original_path' => 'posts/new/2.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'processing_status' => 'completed',
        ]);

        // Edit post: replace media1 with media2, update content and audience
        $response = $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/posts/{$post->id}", [
                'content' => 'Final polished title',
                'audience' => 'friends',
                'media_ids' => [$media2->id],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'content' => 'Final polished title',
                    'audience' => 'friends',
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'content' => 'Final polished title',
            'audience' => 'friends',
        ]);

        // Media1 should be detached, Media2 attached
        $this->assertNull($media1->fresh()->mediable_id);
        $this->assertEquals($post->id, $media2->fresh()->mediable_id);
    }

    /**
     * Requirement 4.4: Authorization Defense: Non-Author Cannot Edit or Delete Post
     */
    public function test_phase4_unauthorized_user_cannot_edit_or_delete_post(): void
    {
        $author = User::factory()->create(['name' => 'Post Owner']);
        $intruder = User::factory()->create(['name' => 'Intruder']);

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Protected post',
            'audience' => 'public',
        ]);

        // Intruder tries to edit
        $editResponse = $this->actingAs($intruder, 'sanctum')
            ->putJson("/api/v1/posts/{$post->id}", [
                'content' => 'Hacked content',
            ]);
        $editResponse->assertStatus(403);

        // Intruder tries to delete
        $deleteResponse = $this->actingAs($intruder, 'sanctum')
            ->deleteJson("/api/v1/posts/{$post->id}");
        $deleteResponse->assertStatus(403);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'content' => 'Protected post',
            'deleted_at' => null,
        ]);
    }

    /**
     * Requirement 4.5: Soft-Delete Post
     */
    public function test_phase4_author_can_soft_delete_post(): void
    {
        $author = User::factory()->create();
        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'To be deleted',
            'audience' => 'public',
        ]);

        $deleteResponse = $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/posts/{$post->id}");

        $deleteResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Post deleted successfully.',
            ]);

        $this->assertSoftDeleted('posts', [
            'id' => $post->id,
        ]);

        // Deleted post is not viewable
        $viewResponse = $this->getJson("/api/v1/posts/{$post->id}");
        $viewResponse->assertStatus(404);
    }

    /**
     * Requirement 5.3: Disallow Invalid / Malicious File Uploads
     */
    public function test_phase5_rejects_malicious_file_uploads(): void
    {
        $user = User::factory()->create();

        $maliciousFile = UploadedFile::fake()->create('exploit.php', 100, 'application/x-php');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/media/upload', [
                'file' => $maliciousFile,
                'collection' => 'post',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }
}
