<?php

namespace Tests\Unit;

use App\Models\Media;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\MediaStorageService;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageUrlNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_profile_normalizes_storage_urls_with_foreign_domains(): void
    {
        $this->assertEquals(
            '/storage/users/2/profile/b90d44c1-50ad-4873-9c12-4e09734385a9.png',
            UserProfile::normalizeStorageUrl('https://hostvra.com/storage/users/2/profile/b90d44c1-50ad-4873-9c12-4e09734385a9.png')
        );

        $this->assertEquals(
            '/storage/users/2/profile/1acce19d-e1e2-46bd-94f3-d7cc13961e9c.png',
            UserProfile::normalizeStorageUrl('http://localhost:8000/storage/users/2/profile/1acce19d-e1e2-46bd-94f3-d7cc13961e9c.png')
        );

        $this->assertEquals(
            '/storage/users/2/cover/3511b7c4.jpg',
            UserProfile::normalizeStorageUrl('/storage/users/2/cover/3511b7c4.jpg')
        );

        $this->assertEquals(
            'https://example.com/avatar.jpg',
            UserProfile::normalizeStorageUrl('https://example.com/avatar.jpg')
        );

        $this->assertNull(UserProfile::normalizeStorageUrl(null));
    }

    public function test_user_profile_avatar_and_cover_accessors_and_mutators_normalize_urls(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::create([
            'user_id' => $user->id,
            'avatar_url' => 'https://hostvra.com/storage/users/1/profile/avatar.png',
            'cover_url' => 'http://localhost:8000/storage/users/1/cover/cover.jpg',
        ]);

        $this->assertEquals('/storage/users/1/profile/avatar.png', $profile->avatar_url);
        $this->assertEquals('/storage/users/1/cover/cover.jpg', $profile->cover_url);
    }

    public function test_media_storage_service_returns_clean_storage_url_without_foreign_domain(): void
    {
        config(['filesystems.cdn_url' => null]);
        config(['filesystems.disks.public.url' => '/storage']);

        $service = new MediaStorageService('public');
        $url = $service->getUrl('uploads/story_photo/2026/10/16104.jpg');

        $this->assertEquals('/storage/uploads/story_photo/2026/10/16104.jpg', $url);
    }

    public function test_profile_service_get_user_photos_returns_normalized_urls(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'avatar_url' => 'https://hostvra.com/storage/users/2/profile/avatar.png',
            'cover_url' => 'https://hostvra.com/storage/users/2/cover/cover.jpg',
        ]);

        Media::create([
            'user_id' => $user->id,
            'collection' => 'story',
            'disk' => 'public',
            'original_path' => 'uploads/story_photo/2026/10/photo.png',
            'mime_type' => 'image/png',
            'size' => 12345,
            'processing_status' => 'ready',
        ]);

        $service = app(ProfileService::class);
        $photos = $service->getUserPhotos($user, $user, 10);

        $this->assertNotEmpty($photos);
        foreach ($photos as $photo) {
            $this->assertStringStartsWith('/storage/', $photo['url']);
            $this->assertStringNotContainsString('hostvra.com', $photo['url']);
            $this->assertStringNotContainsString('localhost:8000', $photo['url']);
        }
    }
}
