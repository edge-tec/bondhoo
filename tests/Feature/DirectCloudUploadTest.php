<?php

namespace Tests\Feature;

use App\Jobs\ProcessMediaJob;
use App\Models\MediaUploadSession;
use App\Models\User;
use App\Models\UserStorageQuota;
use App\Services\Media\DirectCloudUploadService;
use Aws\CommandInterface;
use Aws\Result;
use Aws\S3\S3Client;
use Database\Seeders\RolePermissionSeeder;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class DirectCloudUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected DirectCloudUploadService $cloudService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create([
            'name' => 'জামিল কন্টেন্ট ক্রিয়েটর',
            'username' => 'jamil_creator',
        ]);

        $this->cloudService = app(DirectCloudUploadService::class);
        $this->app->instance(DirectCloudUploadService::class, $this->cloudService);
    }

    /**
     * Helper to create a mocked S3Client for tests.
     */
    protected function createMockS3Client(): Mockery\MockInterface|S3Client
    {
        $mock = Mockery::mock(S3Client::class);

        $mockCommand = Mockery::mock(CommandInterface::class);
        $mock->allows('getCommand')->andReturn($mockCommand);

        $mock->allows('createPresignedRequest')->andReturnUsing(function ($command) {
            return new Psr7Request('PUT', new Uri('https://jugajug-media.s3.amazonaws.com/presigned-part-url?partNumber=1'));
        });

        return $mock;
    }

    public function test_user_can_initialize_direct_s3_multipart_video_upload(): void
    {
        $mockS3 = $this->createMockS3Client();
        $mockS3->shouldReceive('createMultipartUpload')
            ->once()
            ->andReturn(new Result(['UploadId' => 's3_mock_upload_id_999']));

        $this->cloudService->setS3Client($mockS3);

        $fileSize = 15728640; // 15 MB (3 parts of 5 MB each)
        $partSize = 5242880;  // 5 MB

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v2/uploads/direct/init', [
                'filename' => 'coxs_bazar_4k_vlog.mp4',
                'file_size' => $fileSize,
                'mime_type' => 'video/mp4',
                'collection' => 'reel',
                'disk' => 's3',
                'part_size' => $partSize,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.upload_id', 's3_mock_upload_id_999')
            ->assertJsonPath('data.total_parts', 3)
            ->assertJsonPath('data.disk', 's3');

        $parts = $response->json('data.parts');
        $this->assertCount(3, $parts);
        $this->assertEquals(1, $parts[0]['part_number']);
        $this->assertStringContainsString('presigned-part-url', $parts[0]['upload_url']);

        $sessionId = $response->json('data.session_id');
        $this->assertDatabaseHas('media_upload_sessions', [
            'session_id' => $sessionId,
            'user_id' => $this->user->id,
            'collection' => 'reel',
            'status' => MediaUploadSession::STATUS_UPLOADING,
            'total_chunks' => 3,
        ]);
    }

    public function test_user_can_initialize_cloudflare_r2_multipart_video_upload(): void
    {
        $mockS3 = $this->createMockS3Client();
        $mockS3->shouldReceive('createMultipartUpload')
            ->once()
            ->andReturn(new Result(['UploadId' => 'r2_mock_upload_id_888']));

        $this->cloudService->setS3Client($mockS3);

        $fileSize = 20971520; // 20 MB (4 parts of 5 MB)

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v2/uploads/direct/init', [
                'filename' => 'music_concert_highlight.mov',
                'file_size' => $fileSize,
                'mime_type' => 'video/quicktime',
                'collection' => 'post',
                'disk' => 'r2',
                'part_size' => 5242880,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.disk', 'r2')
            ->assertJsonPath('data.upload_id', 'r2_mock_upload_id_888')
            ->assertJsonPath('data.total_parts', 4);
    }

    public function test_user_can_complete_direct_multipart_upload_and_trigger_processing(): void
    {
        Queue::fake([ProcessMediaJob::class]);

        $sessionId = 'session_test_direct_complete_uuid';
        $s3Key = 'uploads/reel/2026/10/drone_footage.mp4';
        $bucket = 'jugajug-media';

        MediaUploadSession::create([
            'user_id' => $this->user->id,
            'session_id' => $sessionId,
            'collection' => 'reel',
            'filename' => 'drone_footage.mp4',
            'original_name' => 'drone_footage.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => 15728640,
            'chunk_size' => 5242880,
            'total_chunks' => 3,
            'uploaded_chunks_count' => 0,
            'status' => MediaUploadSession::STATUS_UPLOADING,
            'target_path' => $s3Key,
            'metadata' => [
                'driver' => 's3',
                'bucket' => $bucket,
                's3_key' => $s3Key,
                'upload_id' => 's3_mock_upload_id_999',
            ],
            'expires_at' => now()->addHours(2),
        ]);

        $mockS3 = $this->createMockS3Client();
        $mockS3->shouldReceive('completeMultipartUpload')
            ->once()
            ->with(Mockery::on(function ($args) use ($bucket, $s3Key) {
                return $args['Bucket'] === $bucket
                    && $args['Key'] === $s3Key
                    && $args['UploadId'] === 's3_mock_upload_id_999'
                    && count($args['MultipartUpload']['Parts']) === 3;
            }))
            ->andReturn(new Result(['Location' => "https://{$bucket}.s3.amazonaws.com/{$s3Key}"]));

        $mockS3->shouldReceive('headObject')
            ->once()
            ->with(['Bucket' => $bucket, 'Key' => $s3Key])
            ->andReturn(new Result([
                'ContentLength' => 15728640,
                'ETag' => '"verified_etag_checksum_123"',
            ]));

        $this->cloudService->setS3Client($mockS3);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v2/uploads/direct/complete', [
                'session_id' => $sessionId,
                'parts' => [
                    ['PartNumber' => 1, 'ETag' => '"etag_part_1"'],
                    ['PartNumber' => 2, 'ETag' => '"etag_part_2"'],
                    ['PartNumber' => 3, 'ETag' => '"etag_part_3"'],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session_id', $sessionId)
            ->assertJsonPath('data.media.collection', 'reel');

        $this->assertDatabaseHas('media', [
            'user_id' => $this->user->id,
            'collection' => 'reel',
            'disk' => 's3',
            'original_path' => $s3Key,
            'size' => 15728640,
            'checksum' => 'verified_etag_checksum_123',
            'processing_status' => 'processing',
        ]);

        $this->assertDatabaseHas('media_upload_sessions', [
            'session_id' => $sessionId,
            'status' => MediaUploadSession::STATUS_COMPLETED,
        ]);

        $quota = UserStorageQuota::where('user_id', $this->user->id)->first();
        $this->assertNotNull($quota);
        $this->assertEquals(15728640, $quota->used_storage_bytes);
        $this->assertEquals(1, $quota->today_uploads_count);

        Queue::assertPushed(ProcessMediaJob::class);
    }

    public function test_user_can_abort_direct_multipart_upload(): void
    {
        $sessionId = 'session_test_direct_abort_uuid';
        $s3Key = 'uploads/reel/2026/10/aborted_video.mp4';
        $bucket = 'jugajug-media';

        MediaUploadSession::create([
            'user_id' => $this->user->id,
            'session_id' => $sessionId,
            'collection' => 'reel',
            'filename' => 'aborted_video.mp4',
            'original_name' => 'aborted_video.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => 10485760,
            'chunk_size' => 5242880,
            'total_chunks' => 2,
            'status' => MediaUploadSession::STATUS_UPLOADING,
            'target_path' => $s3Key,
            'metadata' => [
                'driver' => 's3',
                'bucket' => $bucket,
                's3_key' => $s3Key,
                'upload_id' => 's3_mock_upload_abort_777',
            ],
            'expires_at' => now()->addHours(2),
        ]);

        $mockS3 = $this->createMockS3Client();
        $mockS3->shouldReceive('abortMultipartUpload')
            ->once()
            ->with([
                'Bucket' => $bucket,
                'Key' => $s3Key,
                'UploadId' => 's3_mock_upload_abort_777',
            ])
            ->andReturn(new Result([]));

        $this->cloudService->setS3Client($mockS3);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v2/uploads/direct/abort', [
                'session_id' => $sessionId,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('media_upload_sessions', [
            'session_id' => $sessionId,
            'status' => MediaUploadSession::STATUS_CANCELLED,
        ]);
    }

    public function test_user_can_generate_presigned_put_url_for_single_small_file(): void
    {
        $mockS3 = $this->createMockS3Client();
        $this->cloudService->setS3Client($mockS3);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v2/uploads/direct/presign', [
                'filename' => 'profile_avatar.jpg',
                'file_size' => 1048576, // 1 MB
                'mime_type' => 'image/jpeg',
                'collection' => 'general',
                'disk' => 's3',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.method', 'PUT')
            ->assertJsonPath('data.headers.Content-Type', 'image/jpeg');

        $this->assertNotEmpty($response->json('data.upload_url'));
        $this->assertNotEmpty($response->json('data.key'));
    }

    public function test_user_can_check_direct_upload_session_status(): void
    {
        $sessionId = 'session_test_direct_status_uuid';

        MediaUploadSession::create([
            'user_id' => $this->user->id,
            'session_id' => $sessionId,
            'collection' => 'reel',
            'filename' => 'status_check_video.mp4',
            'original_name' => 'status_check_video.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => 15728640,
            'chunk_size' => 5242880,
            'total_chunks' => 3,
            'status' => MediaUploadSession::STATUS_UPLOADING,
            'target_path' => 'uploads/reel/status.mp4',
            'metadata' => [
                'driver' => 'r2',
                'bucket' => 'bondhoo-videos',
                's3_key' => 'uploads/reel/status.mp4',
                'upload_id' => 'r2_status_id',
            ],
            'expires_at' => now()->addHours(2),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v2/uploads/direct/status/{$sessionId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session_id', $sessionId)
            ->assertJsonPath('data.status', 'uploading')
            ->assertJsonPath('data.disk', 'r2')
            ->assertJsonPath('data.bucket', 'bondhoo-videos')
            ->assertJsonPath('data.total_parts', 3);
    }

    public function test_direct_upload_is_rejected_when_user_capacity_is_exceeded(): void
    {
        UserStorageQuota::create([
            'user_id' => $this->user->id,
            'max_storage_bytes' => 10485760, // 10 MB limit
            'used_storage_bytes' => 10000000, // 9.5 MB used
            'is_unlimited' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v2/uploads/direct/init', [
                'filename' => 'too_large_video.mp4',
                'file_size' => 15728640, // 15 MB (exceeds quota)
                'mime_type' => 'video/mp4',
                'collection' => 'reel',
                'disk' => 's3',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
