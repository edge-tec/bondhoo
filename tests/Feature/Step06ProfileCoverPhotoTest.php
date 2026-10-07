<?php

namespace Tests\Feature;

use App\Events\ProfileUpdatedEvent;
use App\Jobs\ProcessCoverJob;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Step06ProfileCoverPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
    }

    public function test_user_can_upload_valid_cover_photo_and_dispatches_queue_job(): void
    {
        Queue::fake();
        Event::fake([ProfileUpdatedEvent::class]);

        $user = User::factory()->create([
            'username' => 'coverguy',
            'name' => 'Cover Guy',
        ]);

        $cacheKey = "profile:public:{$user->username}";
        Cache::put($cacheKey, ['dummy' => 'cached'], 3600);

        $file = UploadedFile::fake()->image('clean_cover.jpg', 1200, 500);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/cover', [
                'file' => $file,
                'caption' => 'Loving my new profile cover!',
                'cover_position_y' => 25,
                'crop_x' => 10,
                'crop_y' => 10,
                'crop_width' => 1000,
                'crop_height' => 400,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'কভার ছবি সফলভাবে আপডেট করা হয়েছে।',
            ])
            ->assertJsonStructure([
                'data' => [
                    'cover_url',
                    'cover_media_id',
                    'cover_position_y',
                    'media' => ['id', 'collection', 'mime_type', 'urls'],
                    'post' => ['id', 'content', 'type'],
                ],
            ]);

        $user->refresh();
        $this->assertNotNull($user->profile->cover_url);
        $this->assertNotNull($user->profile->cover_media_id);
        $this->assertEquals(25, $user->profile->cover_position_y);

        // Verify Media record
        $this->assertDatabaseHas('media', [
            'id' => $user->profile->cover_media_id,
            'user_id' => $user->id,
            'collection' => 'covers',
            'mime_type' => 'image/jpeg',
        ]);

        // Verify Announcement Post
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'type' => 'cover_update',
            'content' => 'Loving my new profile cover!',
        ]);

        // Verify Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'PROFILE_COVER_UPLOADED',
            'entity_type' => UserProfile::class,
        ]);

        // Verify Cache Invalidation
        $this->assertFalse(Cache::has($cacheKey));

        // Verify Queue Job Dispatched
        Queue::assertPushed(ProcessCoverJob::class, function (ProcessCoverJob $job) use ($user) {
            return $job->media->user_id === $user->id && $job->crop['width'] === 1000;
        });

        // Verify Event Dispatched
        Event::assertDispatched(ProfileUpdatedEvent::class);
    }

    public function test_safe_replacement_removes_old_cover_only_after_new_one_stored(): void
    {
        Queue::fake();

        $user = User::factory()->create(['username' => 'coverreplacer']);

        // First cover upload
        $firstFile = UploadedFile::fake()->image('first_cover.jpg', 800, 300);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/cover', ['file' => $firstFile])
            ->assertStatus(200);

        $firstMedia = Media::where('user_id', $user->id)->firstOrFail();
        $firstPath = $firstMedia->original_path;
        Storage::disk('public')->assertExists($firstPath);

        // Second cover upload replaces the first
        $secondFile = UploadedFile::fake()->image('second_cover.png', 1000, 400);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/cover', ['file' => $secondFile])
            ->assertStatus(200);

        $secondMedia = Media::where('user_id', $user->id)->latest('id')->firstOrFail();
        $secondPath = $secondMedia->original_path;

        // New cover must exist
        Storage::disk('public')->assertExists($secondPath);

        // Old cover must have been safely deleted
        Storage::disk('public')->assertMissing($firstPath);
        $this->assertDatabaseMissing('media', ['id' => $firstMedia->id]);

        $user->refresh();
        $this->assertEquals($secondMedia->id, $user->profile->cover_media_id);
    }

    public function test_rejects_svg_files_to_prevent_svg_xss(): void
    {
        $user = User::factory()->create(['username' => 'coversvgtester']);

        $svgPayload = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(\'XSS\')"><rect width="300" height="100"/></svg>';
        $svgFile = UploadedFile::fake()->createWithContent('malicious_cover.svg', $svgPayload);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/cover', [
                'file' => $svgFile,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_rejects_embedded_php_or_executable_scripts(): void
    {
        $user = User::factory()->create(['username' => 'coverhacker']);

        $phpPayload = "GIF89a\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;<?php system(\$_GET['cmd']); ?>";
        $fakeImage = UploadedFile::fake()->createWithContent('exploit_cover.jpg', $phpPayload);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/cover', [
                'file' => $fakeImage,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_rejects_images_with_invalid_dimensions_or_size(): void
    {
        $user = User::factory()->create(['username' => 'coversizetester']);

        // 1. Under minimum dimensions (200x100 < 400x150)
        $tinyImage = UploadedFile::fake()->image('tiny_cover.jpg', 200, 100);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/cover', ['file' => $tinyImage])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        // 2. Exceeding max size (15MB > 10MB)
        $giantImage = UploadedFile::fake()->image('giant_cover.jpg', 600, 300)->size(15000);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/cover', ['file' => $giantImage])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_user_can_delete_cover_and_restores_default(): void
    {
        $user = User::factory()->create(['username' => 'coverdeletetester', 'name' => 'Delete Tester']);

        $file = UploadedFile::fake()->image('cover_to_delete.png', 600, 250);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/cover', ['file' => $file])
            ->assertStatus(200);

        $media = Media::where('user_id', $user->id)->firstOrFail();
        Storage::disk('public')->assertExists($media->original_path);

        // Delete Cover
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v2/profile/cover');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'কভার ছবি সফলভাবে মুছে ফেলা হয়েছে।',
            ]);

        $user->refresh();
        $this->assertNull($user->profile->cover_url);
        $this->assertNull($user->profile->cover_media_id);

        // Physical file must be deleted
        Storage::disk('public')->assertMissing($media->original_path);

        // Audit log for deletion
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'PROFILE_COVER_DELETED',
        ]);
    }

    public function test_user_can_crop_active_cover(): void
    {
        $user = User::factory()->create(['username' => 'covercroptester']);

        $file = UploadedFile::fake()->image('cover_tocrop.png', 800, 400);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/cover', ['file' => $file])
            ->assertStatus(200);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/cover/crop', [
                'crop_x' => 50,
                'crop_y' => 50,
                'crop_width' => 600,
                'crop_height' => 300,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'কভার ছবি সফলভাবে ক্রপ করা হয়েছে।',
            ]);

        $media = Media::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(600, $media->width);
        $this->assertEquals(300, $media->height);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'PROFILE_COVER_UPDATED',
        ]);
    }

    public function test_user_can_reposition_cover(): void
    {
        $user = User::factory()->create(['username' => 'repositiontester']);

        $file = UploadedFile::fake()->image('cover_toreposition.png', 800, 400);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/cover', ['file' => $file])
            ->assertStatus(200);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/cover/position', [
                'position_y' => 75,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'কভার ছবির পজিশন সফলভাবে সংরক্ষিত হয়েছে।',
                'data' => [
                    'cover_position_y' => 75,
                ],
            ]);

        $user->refresh();
        $this->assertEquals(75, $user->profile->cover_position_y);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'PROFILE_COVER_UPDATED',
        ]);
    }

    public function test_process_cover_job_generates_desktop_mobile_and_thumbnail_webp_variants(): void
    {
        $user = User::factory()->create(['username' => 'coverjobtester']);

        $file = UploadedFile::fake()->image('cover_raw.jpg', 1400, 600);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/cover', ['file' => $file])
            ->assertStatus(200);

        $media = Media::where('user_id', $user->id)->firstOrFail();

        // Run the job synchronously
        $job = new ProcessCoverJob($media, ['x' => 0, 'y' => 0, 'width' => 1200, 'height' => 500]);
        app()->call([$job, 'handle']);

        $media->refresh();
        $this->assertEquals('ready', $media->processing_status);
        $this->assertNotNull($media->thumbnail_path);
        $this->assertNotNull($media->medium_path);
        $this->assertNotNull($media->large_path);

        Storage::disk('public')->assertExists($media->thumbnail_path);
        Storage::disk('public')->assertExists($media->medium_path);
        Storage::disk('public')->assertExists($media->large_path);

        // Thumbnail file format must be WebP
        $this->assertStringEndsWith('.webp', $media->thumbnail_path);
        $this->assertStringEndsWith('.webp', $media->medium_path);
        $this->assertStringEndsWith('.webp', $media->large_path);
    }
}
