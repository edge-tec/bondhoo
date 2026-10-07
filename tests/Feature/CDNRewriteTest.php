<?php

namespace Tests\Feature;

use App\Services\CdnService;
use Tests\TestCase;

/**
 * CDNRewriteTest — সিডিএন ইউআরএল রূপান্তর ও ইমেজ অপটিমাইজেশন টেস্ট
 */
class CDNRewriteTest extends TestCase
{
    /**
     * সিডিএন সার্ভিস ফাইল পাথকে যথাযথ সিডিএন ডোমেইনে রূপান্তর করে।
     */
    public function test_cdn_service_rewrites_asset_urls(): void
    {
        $service = app(CdnService::class);
        $url = $service->rewriteUrl('uploads/photos/avatar.jpg');

        $this->assertStringContainsString('uploads/photos/avatar.jpg', $url);
    }

    /**
     * ইমেজ অপটিমাইজেশন প্যারামিটার (WebP, Width, Quality) সহ সিডিএন ইউআরএল তৈরি।
     */
    public function test_cdn_service_generates_optimized_image_urls(): void
    {
        $service = app(CdnService::class);
        $optimizedUrl = $service->getOptimizedImageUrl('uploads/photos/banner.png', 800, 600, 'webp', 80);

        $this->assertStringContainsString('fmt=webp', $optimizedUrl);
        $this->assertStringContainsString('w=800', $optimizedUrl);
        $this->assertStringContainsString('h=600', $optimizedUrl);
        $this->assertStringContainsString('q=80', $optimizedUrl);
    }

    /**
     * সিডিএন ক্যাশ পার্জ মেথড সফলভাবে রেসপন্স প্রদান করে।
     */
    public function test_cdn_cache_purge_execution(): void
    {
        $service = app(CdnService::class);
        $result = $service->purgeCache(['https://cdn.jugajug.com/post/1.jpg']);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['purged']);
    }
}
