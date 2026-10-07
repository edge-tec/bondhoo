<?php

namespace Tests\Feature;

use App\Models\Story;
use App\Models\User;
use App\Services\EnterpriseStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * StorageLifecycleTest — অবজেক্ট স্টোরেজ লাইফসাইকেল ও স্টোরি ক্লিনআপ টেস্ট
 */
class StorageLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * স্টোরেজ সার্ভিস সমস্ত আবশ্যক ইমেজ ও ভিডিও রেজোলিউশন প্রোফাইল তৈরি করে।
     */
    public function test_storage_service_generates_all_image_and_video_variants(): void
    {
        $service = app(EnterpriseStorageService::class);

        $imageVariants = $service->getImageVariants('media/photos/sample.jpg');
        $this->assertArrayHasKey('original', $imageVariants);
        $this->assertArrayHasKey('size_64', $imageVariants);
        $this->assertArrayHasKey('size_128', $imageVariants);
        $this->assertArrayHasKey('size_256', $imageVariants);
        $this->assertArrayHasKey('size_512', $imageVariants);

        $videoVariants = $service->getVideoVariants('media/videos/clip.mp4');
        $this->assertArrayHasKey('240p', $videoVariants);
        $this->assertArrayHasKey('360p', $videoVariants);
        $this->assertArrayHasKey('480p', $videoVariants);
        $this->assertArrayHasKey('720p', $videoVariants);
        $this->assertArrayHasKey('1080p', $videoVariants);
    }

    /**
     * ২৪ ঘণ্টার পুরোনো স্টোরিজ লাইফসাইকেল ক্লিনার দ্বারা মুছে ফেলা যাচাই।
     */
    public function test_storage_lifecycle_removes_expired_stories(): void
    {
        $user = User::factory()->create();

        // ২৪ ঘণ্টার পুরোনো এক্সপায়ার্ড স্টোরি
        $expiredStory = Story::create([
            'user_id' => $user->id,
            'media_url' => 'stories/old_story.jpg',
            'media_type' => 'image',
            'expires_at' => now()->subHours(25),
        ]);

        // বর্তমান সচল স্টোরি
        $activeStory = Story::create([
            'user_id' => $user->id,
            'media_url' => 'stories/active_story.jpg',
            'media_type' => 'image',
            'expires_at' => now()->addHours(12),
        ]);

        $this->artisan('jugajug:storage-lifecycle')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('stories', ['id' => $expiredStory->id]);
        $this->assertDatabaseHas('stories', ['id' => $activeStory->id]);
    }
}
