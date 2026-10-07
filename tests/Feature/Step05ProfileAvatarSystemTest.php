<?php

namespace Tests\Feature;

use App\Events\ProfileUpdatedEvent;
use App\Jobs\ProcessAvatarJob;
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

class Step05ProfileAvatarSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
    }

    public function test_user_can_upload_valid_avatar_and_dispatches_queue_job(): void
    {
        Queue::fake();
        Event::fake([ProfileUpdatedEvent::class]);

        $user = User::factory()->create([
            'username' => 'avatarguy',
            'name' => 'Avatar Guy',
        ]);

        $cacheKey = "profile:public:{$user->username}";
        Cache::put($cacheKey, ['dummy' => 'cached'], 3600);

        $file = UploadedFile::fake()->image('clean_avatar.png', 400, 400);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/avatar', [
                'file' => $file,
                'caption' => 'Loving my new profile look!',
                'crop_x' => 10,
                'crop_y' => 10,
                'crop_width' => 300,
                'crop_height' => 300,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'প্রোফাইল ছবি সফলভাবে আপডেট করা হয়েছে।',
            ])
            ->assertJsonStructure([
                'data' => [
                    'avatar_url',
                    'media' => ['id', 'collection', 'mime_type', 'urls'],
                    'post' => ['id', 'content', 'type'],
                ],
            ]);

        $user->refresh();
        $this->assertNotNull($user->profile->avatar_url);

        // Verify Media record
        $this->assertDatabaseHas('media', [
            'user_id' => $user->id,
            'collection' => 'avatars',
            'mime_type' => 'image/png',
        ]);

        // Verify Announcement Post
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'type' => 'avatar_update',
            'content' => 'Loving my new profile look!',
        ]);

        // Verify Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'avatar.uploaded',
            'entity_type' => UserProfile::class,
        ]);

        // Verify Cache Invalidation
        $this->assertFalse(Cache::has($cacheKey));

        // Verify Queue Job Dispatched
        Queue::assertPushed(ProcessAvatarJob::class, function (ProcessAvatarJob $job) use ($user) {
            return $job->media->user_id === $user->id && $job->crop['width'] === 300;
        });

        // Verify Event Dispatched
        Event::assertDispatched(ProfileUpdatedEvent::class);
    }

    public function test_safe_replacement_removes_old_avatar_only_after_new_one_stored(): void
    {
        Queue::fake();

        $user = User::factory()->create(['username' => 'replacer']);

        // First avatar upload
        $firstFile = UploadedFile::fake()->image('first_avatar.jpg', 300, 300);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/avatar', ['file' => $firstFile])
            ->assertStatus(200);

        $firstMedia = Media::where('user_id', $user->id)->firstOrFail();
        $firstPath = $firstMedia->original_path;
        Storage::disk('public')->assertExists($firstPath);

        // Second avatar upload replaces the first
        $secondFile = UploadedFile::fake()->image('second_avatar.webp', 450, 450);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/avatar', ['file' => $secondFile])
            ->assertStatus(200);

        $secondMedia = Media::where('user_id', $user->id)->latest('id')->firstOrFail();
        $secondPath = $secondMedia->original_path;

        // New avatar must exist
        Storage::disk('public')->assertExists($secondPath);

        // Old avatar must have been safely deleted
        Storage::disk('public')->assertMissing($firstPath);
        $this->assertDatabaseMissing('media', ['id' => $firstMedia->id]);
    }

    public function test_rejects_svg_files_to_prevent_svg_xss(): void
    {
        $user = User::factory()->create(['username' => 'svgtester']);

        $svgPayload = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(\'XSS\')"><circle cx="50" cy="50" r="40" /></svg>';
        $svgFile = UploadedFile::fake()->createWithContent('malicious.svg', $svgPayload);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/avatar', [
                'file' => $svgFile,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_rejects_embedded_php_or_executable_scripts(): void
    {
        $user = User::factory()->create(['username' => 'hacker']);

        $phpPayload = "GIF89a\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;<?php echo 'hacked'; system(\$_GET['cmd']); ?>";
        $fakeImage = UploadedFile::fake()->createWithContent('exploit.jpg', $phpPayload);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/avatar', [
                'file' => $fakeImage,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_rejects_images_with_invalid_dimensions_or_size(): void
    {
        $user = User::factory()->create(['username' => 'sizetester']);

        // 1. Under minimum dimensions (50x50 < 100x100)
        $tinyImage = UploadedFile::fake()->image('tiny.jpg', 50, 50);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/avatar', ['file' => $tinyImage])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        // 2. Exceeding max size (e.g. 15MB > 10MB)
        $giantImage = UploadedFile::fake()->image('giant.jpg', 200, 200)->size(15000);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/avatar', ['file' => $giantImage])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_user_can_delete_avatar_and_restores_default(): void
    {
        $user = User::factory()->create(['username' => 'deletetester', 'name' => 'Delete Tester']);

        $file = UploadedFile::fake()->image('avatar_to_delete.png', 200, 200);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/avatar', ['file' => $file])
            ->assertStatus(200);

        $media = Media::where('user_id', $user->id)->firstOrFail();
        Storage::disk('public')->assertExists($media->original_path);

        // Delete Avatar
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v2/profile/avatar');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'প্রোফাইল ছবি সফলভাবে মুছে ফেলা হয়েছে।',
            ])
            ->assertJsonPath('data.default_avatar_url', 'https://ui-avatars.com/api/?name=Delete+Tester&background=1877f2&color=fff&size=256');

        $user->refresh();
        $this->assertNull($user->fresh()->profile?->avatar_url);

        // Physical file must be deleted
        Storage::disk('public')->assertMissing($media->original_path);

        // Audit log for deletion
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'avatar.deleted',
        ]);
    }

    public function test_user_can_crop_active_avatar(): void
    {
        $user = User::factory()->create(['username' => 'croptester']);

        $file = UploadedFile::fake()->image('tocrop.png', 500, 500);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/avatar', ['file' => $file])
            ->assertStatus(200);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/avatar/crop', [
                'crop_x' => 50,
                'crop_y' => 50,
                'crop_width' => 200,
                'crop_height' => 200,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'প্রোফাইল ছবি সফলভাবে ক্রপ করা হয়েছে।',
            ]);

        $media = Media::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(200, $media->width);
        $this->assertEquals(200, $media->height);
    }

    public function test_process_avatar_job_generates_webp_variants(): void
    {
        $user = User::factory()->create(['username' => 'jobtester']);

        $file = UploadedFile::fake()->image('raw.jpg', 600, 600);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/avatar', ['file' => $file])
            ->assertStatus(200);

        $media = Media::where('user_id', $user->id)->firstOrFail();

        // Run the job synchronously
        $job = new ProcessAvatarJob($media, ['x' => 0, 'y' => 0, 'width' => 400, 'height' => 400]);
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
    }
}
