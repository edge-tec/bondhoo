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

class PhotoVideoPostComposerUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
        Queue::fake();

        $this->user = User::factory()->create([
            'name' => 'Mizanur Rahman',
            'username' => 'mizanur',
        ]);
    }

    /**
     * Test photo upload with various allowed formats (JPG, PNG, WebP).
     */
    public function test_user_can_upload_supported_photos(): void
    {
        $file = UploadedFile::fake()->image('holiday.webp', 1200, 800);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/media/upload', [
                'file' => $file,
                'collection' => 'post',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'mime_type' => 'image/webp',
                    'collection' => 'post',
                ],
            ]);

        $this->assertDatabaseHas('media', [
            'user_id' => $this->user->id,
            'mime_type' => 'image/webp',
            'collection' => 'post',
        ]);
    }

    /**
     * Test video upload with supported MP4 format.
     */
    public function test_user_can_upload_supported_video(): void
    {
        $video = UploadedFile::fake()->create('clip.mp4', 5000, 'video/mp4');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/media/upload', [
                'file' => $video,
                'collection' => 'video',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'mime_type' => 'video/mp4',
                    'collection' => 'video',
                ],
            ]);

        $this->assertDatabaseHas('media', [
            'user_id' => $this->user->id,
            'mime_type' => 'video/mp4',
            'collection' => 'video',
        ]);
    }

    /**
     * Test rejection of invalid or executable files.
     */
    public function test_rejects_unsupported_or_executable_files(): void
    {
        $badFile = UploadedFile::fake()->create('malicious.exe', 1000, 'application/x-msdownload');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/media/upload', [
                'file' => $badFile,
                'collection' => 'post',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * Test deleting/cleaning up an unattached media item before publishing.
     */
    public function test_user_can_delete_unattached_media(): void
    {
        $file = UploadedFile::fake()->image('temp.png', 400, 400);

        $uploadRes = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/media/upload', [
                'file' => $file,
                'collection' => 'post',
            ]);

        $uploadRes->assertStatus(201);
        $mediaId = (int) $uploadRes->json('data.id');

        $this->assertDatabaseHas('media', ['id' => $mediaId]);

        $deleteRes = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/media/{$mediaId}");

        $deleteRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Media deleted successfully.',
            ]);

        $this->assertDatabaseMissing('media', ['id' => $mediaId]);
    }

    /**
     * Test unauthorized media deletion is rejected.
     */
    public function test_unauthorized_user_cannot_delete_another_users_media(): void
    {
        $otherUser = User::factory()->create();
        $media = Media::create([
            'user_id' => $otherUser->id,
            'collection' => 'post',
            'disk' => 'public',
            'original_path' => 'posts/pending/images/test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'processing_status' => 'ready',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/media/{$media->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    /**
     * Test creating a media post with multiple photos, custom ordering, and alt texts.
     */
    public function test_create_post_with_multiple_photos_and_alt_text(): void
    {
        $media1 = Media::create([
            'user_id' => $this->user->id,
            'collection' => 'post',
            'disk' => 'public',
            'original_path' => 'posts/pending/images/p1.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'processing_status' => 'ready',
        ]);

        $media2 = Media::create([
            'user_id' => $this->user->id,
            'collection' => 'post',
            'disk' => 'public',
            'original_path' => 'posts/pending/images/p2.png',
            'mime_type' => 'image/png',
            'size' => 2048,
            'processing_status' => 'ready',
        ]);

        $taggedFriend = User::factory()->create(['name' => 'Friend One']);

        $payload = [
            'content' => 'সুন্দর এক বিকেল কাটলো বন্ধুদের সাথে! 🌅 Wonderful afternoon with friends! ❤️',
            'audience' => 'public',
            'location' => 'Dhaka, Bangladesh',
            'feeling_activity' => 'feeling happy 😊',
            'tagged_user_ids' => [$taggedFriend->id],
            'media_ids' => [$media2->id, $media1->id], // Custom arranged order
            'media_meta' => [
                'order' => [$media2->id, $media1->id],
                'alt_texts' => [
                    $media1->id => 'Sunset over the lake with reflections',
                    $media2->id => 'Group selfie at sunset',
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/posts', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'content' => 'সুন্দর এক বিকেল কাটলো বন্ধুদের সাথে! 🌅 Wonderful afternoon with friends! ❤️',
                    'audience' => 'public',
                    'location' => 'Dhaka, Bangladesh',
                    'feeling_activity' => 'feeling happy 😊',
                ],
            ]);

        $postId = (int) $response->json('data.id');

        // Verify media attachments in database
        $this->assertEquals(Post::class, Media::find($media1->id)->mediable_type);
        $this->assertEquals($postId, Media::find($media1->id)->mediable_id);
        $this->assertEquals(Post::class, Media::find($media2->id)->mediable_type);
        $this->assertEquals($postId, Media::find($media2->id)->mediable_id);

        // Verify custom order in response
        $mediaResponse = $response->json('data.media');
        $this->assertCount(2, $mediaResponse);
        $this->assertEquals($media2->id, $mediaResponse[0]['id']);
        $this->assertEquals('Group selfie at sunset', $mediaResponse[0]['alt_text']);
        $this->assertEquals($media1->id, $mediaResponse[1]['id']);
        $this->assertEquals('Sunset over the lake with reflections', $mediaResponse[1]['alt_text']);
    }

    /**
     * Test media-only post creation (empty caption text).
     */
    public function test_user_can_publish_media_only_post(): void
    {
        $media = Media::create([
            'user_id' => $this->user->id,
            'collection' => 'post',
            'disk' => 'public',
            'original_path' => 'posts/pending/images/photo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'processing_status' => 'ready',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/posts', [
                'audience' => 'public',
                'media_ids' => [$media->id],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'type' => 'media',
                ],
            ]);

        $this->assertCount(1, $response->json('data.media'));
    }

    /**
     * Test caption-only post creation (empty media).
     */
    public function test_user_can_publish_caption_only_post(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'Just text status update on Bondhoo platform! 🚀',
                'audience' => 'friends',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'content' => 'Just text status update on Bondhoo platform! 🚀',
                    'audience' => 'friends',
                    'type' => 'text',
                ],
            ]);
    }
}
