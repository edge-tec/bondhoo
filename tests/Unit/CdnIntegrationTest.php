<?php

namespace Tests\Unit;

use App\Models\Media;
use App\Models\User;
use App\Services\MediaStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * সিডিএন ইন্টিগ্রেশন ইউনিট টেস্ট:
 * মিডিয়া অ্যাসেট ইউআরএলে সিডিএন ডোমেইন রেজোলিউশন টেস্ট।
 */
class CdnIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_storage_service_uses_cdn_url_when_configured(): void
    {
        // Configure CDN URL
        config(['filesystems.cdn_url' => 'https://cdn.jugajug.com']);

        $service = new MediaStorageService('public');

        $url = $service->getUrl('posts/1/images/sample.webp');

        $this->assertEquals('https://cdn.jugajug.com/posts/1/images/sample.webp', $url);
    }

    public function test_media_model_resolves_cdn_variant_urls(): void
    {
        config(['filesystems.cdn_url' => 'https://cdn.jugajug.com']);

        $user = User::factory()->create();

        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'post',
            'disk' => 'public',
            'original_path' => 'posts/123/original.jpg',
            'thumbnail_path' => 'posts/123/thumbnail.webp',
            'medium_path' => 'posts/123/medium.webp',
            'large_path' => 'posts/123/large.webp',
            'mime_type' => 'image/jpeg',
            'size' => 51200,
            'processing_status' => 'ready',
        ]);

        $this->assertEquals('https://cdn.jugajug.com/posts/123/original.jpg', $media->getVariantUrl('original'));
        $this->assertEquals('https://cdn.jugajug.com/posts/123/thumbnail.webp', $media->getVariantUrl('thumbnail'));
        $this->assertEquals('https://cdn.jugajug.com/posts/123/medium.webp', $media->getVariantUrl('medium'));
    }
}
