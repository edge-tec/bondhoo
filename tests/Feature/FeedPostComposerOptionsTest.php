<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeedPostComposerOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Create a user with an attached profile.
     */
    protected function createUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Social',
            'last_name' => 'User',
        ]);

        return $user;
    }

    /**
     * The composer publishes a post with feeling, tagged friends, audience and video media.
     */
    public function test_composer_publishes_post_with_feeling_tags_audience_and_video(): void
    {
        $author = $this->createUser(['name' => 'Aileen Author']);
        $friend = $this->createUser(['name' => 'Tagged Friend']);

        $video = Media::factory()->create([
            'user_id' => $author->id,
            'original_path' => 'posts/clip.mp4',
            'mime_type' => 'video/mp4',
        ]);

        $response = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/posts', [
                'content' => 'Weekend vibes',
                'audience' => 'friends',
                'feeling_activity' => 'feeling happy 😊',
                'tagged_user_ids' => [$friend->id],
                'media_ids' => [$video->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.audience', 'friends')
            ->assertJsonPath('data.feeling_activity', 'feeling happy 😊')
            ->assertJsonPath('data.tagged_users.0.id', $friend->id)
            ->assertJsonPath('data.media.0.mime_type', 'video/mp4');
    }

    /**
     * A post with only a feeling (no text or media) is accepted.
     */
    public function test_composer_allows_feeling_only_post(): void
    {
        $author = $this->createUser();

        $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/posts', [
                'audience' => 'only_me',
                'feeling_activity' => 'celebrating 🥳',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.feeling_activity', 'celebrating 🥳')
            ->assertJsonPath('data.audience', 'only_me');
    }

    /**
     * Tag Friends search excludes the current user and filters by query.
     */
    public function test_taggable_users_search_filters_and_excludes_self(): void
    {
        $author = $this->createUser(['name' => 'Aileen Author', 'username' => 'aileen']);
        $this->createUser(['name' => 'Rahim Uddin', 'username' => 'rahim']);
        $this->createUser(['name' => 'Karim Khan', 'username' => 'karim']);

        $response = $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/posts/taggable-users?q=rahim&limit=20');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.username', 'rahim');
    }

    /**
     * The Photo/Video button uploads a real video file through the media endpoint.
     */
    public function test_photo_video_button_uploads_video_file(): void
    {
        Storage::fake('public');
        Storage::fake('s3');
        Queue::fake();

        $author = $this->createUser();
        $file = UploadedFile::fake()->create('clip.mp4', 1024, 'video/mp4');

        $this->actingAs($author, 'sanctum')
            ->post('/api/v1/media/upload', [
                'file' => $file,
                'collection' => 'post',
            ], ['Accept' => 'application/json'])
            ->assertSuccessful()
            ->assertJsonPath('data.mime_type', 'video/mp4');
    }
}
