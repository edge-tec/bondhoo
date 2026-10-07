<?php

namespace Tests\Feature;

use App\Jobs\ProcessMediaJob;
use App\Models\Media;
use App\Models\User;
use App\Services\Contracts\QueueServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
    }

    public function test_user_can_request_signed_upload_url(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $payload = [
            'filename' => 'avatar.png',
            'mime_type' => 'image/png',
            'size' => 102400,
            'collection' => 'profile',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/media/signed-upload-url', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'method' => 'POST',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'media_id',
                    'upload_url',
                    'headers',
                    'path',
                    'expires_at',
                ],
            ]);

        $this->assertDatabaseHas('media', [
            'id' => $response->json('data.media_id'),
            'user_id' => $user->id,
            'collection' => 'profile',
            'processing_status' => 'pending',
        ]);
    }

    public function test_user_can_confirm_upload_and_dispatch_processing_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $path = "users/{$user->id}/profile/test.jpg";
        Storage::disk('public')->put($path, 'fake image content');

        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'profile',
            'disk' => 'public',
            'original_path' => $path,
            'mime_type' => 'image/jpeg',
            'size' => 18,
            'processing_status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/media/confirm-upload', [
                'media_id' => $media->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Upload confirmed and queued for processing.',
            ]);

        Queue::assertPushedOn(QueueServiceInterface::QUEUE_MEDIA, ProcessMediaJob::class);
    }

    public function test_confirm_upload_fails_if_file_is_missing_from_storage(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'post',
            'disk' => 'public',
            'original_path' => 'non_existent_file.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 500,
            'processing_status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/media/confirm-upload', [
                'media_id' => $media->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_user_can_perform_direct_multipart_upload(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $file = UploadedFile::fake()->image('nature.jpg', 800, 600);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/media/upload', [
                'file' => $file,
                'collection' => 'post',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'collection' => 'post',
                    'status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('media', [
            'user_id' => $user->id,
            'collection' => 'post',
        ]);

        Queue::assertPushedOn(QueueServiceInterface::QUEUE_MEDIA, ProcessMediaJob::class);
    }

    public function test_direct_upload_enforces_validation(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $file = UploadedFile::fake()->create('script.exe', 500, 'application/x-msdownload');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/media/upload', [
                'file' => $file,
                'collection' => 'post',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_can_retrieve_media_details(): void
    {
        $user = User::factory()->create();

        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'profile',
            'disk' => 'public',
            'original_path' => "users/{$user->id}/profile/avatar.png",
            'thumbnail_path' => "users/{$user->id}/profile/avatar_thumbnail.webp",
            'mime_type' => 'image/png',
            'size' => 2048,
            'processing_status' => 'ready',
        ]);

        $response = $this->getJson("/api/v1/media/{$media->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $media->id,
                    'collection' => 'profile',
                    'status' => 'ready',
                ],
            ]);
    }
}
