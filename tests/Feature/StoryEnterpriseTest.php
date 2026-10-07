<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * এন্টারপ্রাইজ স্টোরিজ টেস্ট:
 * মাল্টি-মিডিয়া স্টোরি, সিকোয়েন্সিং, রিঅ্যাকশন, রিপ্লাই, আর্কাইভিং এবং ২৪ ঘণ্টা অটো-এক্সপায়ারেশন।
 */
class StoryEnterpriseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_text_story_with_enterprise_attributes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/stories', [
                'type' => 'text',
                'content' => 'ঢাকা টু কক্সবাজার ভ্রমণ শুরু!',
                'background_color' => 'linear-gradient(135deg, #1877f2, #00c6ff)',
                'font_family' => 'Hind Siliguri, sans-serif',
                'location' => 'Dhaka, Bangladesh',
                'privacy' => 'public',
                'allow_replies' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'ঢাকা টু কক্সবাজার ভ্রমণ শুরু!')
            ->assertJsonPath('data.location', 'Dhaka, Bangladesh')
            ->assertJsonPath('data.font_family', 'Hind Siliguri, sans-serif')
            ->assertJsonPath('data.allow_replies', true);

        $this->assertDatabaseHas('stories', [
            'user_id' => $user->id,
            'content' => 'ঢাকা টু কক্সবাজার ভ্রমণ শুরু!',
            'location' => 'Dhaka, Bangladesh',
            'is_expired' => false,
            'is_archived' => false,
        ]);
    }

    public function test_user_can_create_multi_media_story_with_sequence(): void
    {
        $user = User::factory()->create();

        $media1 = Media::create([
            'user_id' => $user->id,
            'collection' => 'story_photo',
            'disk' => 'public',
            'original_path' => 'uploads/story/photo1.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 102400,
            'processing_status' => 'ready',
        ]);

        $media2 = Media::create([
            'user_id' => $user->id,
            'collection' => 'story_video',
            'disk' => 'public',
            'original_path' => 'uploads/story/video1.mp4',
            'mime_type' => 'video/mp4',
            'size' => 2048000,
            'processing_status' => 'ready',
            'metadata' => ['duration' => 12],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/stories', [
                'type' => 'media',
                'content' => 'মেগা ট্যুর ফটো ও ভিডিও',
                'media_ids' => [$media1->id, $media2->id],
                'privacy' => 'public',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'media')
            ->assertJsonCount(2, 'data.media');

        $storyId = $response->json('data.id');

        $this->assertDatabaseHas('story_media', [
            'story_id' => $storyId,
            'media_id' => $media1->id,
            'order' => 0,
            'duration' => 5,
        ]);

        $this->assertDatabaseHas('story_media', [
            'story_id' => $storyId,
            'media_id' => $media2->id,
            'order' => 1,
            'duration' => 12,
        ]);
    }

    public function test_story_feed_groups_by_author_and_marks_viewed_state(): void
    {
        $viewer = User::factory()->create();
        $author1 = User::factory()->create();

        $story = Story::create([
            'user_id' => $author1->id,
            'type' => 'text',
            'content' => 'Author 1 story',
            'privacy' => 'public',
            'expires_at' => now()->addHours(20),
        ]);

        // ১. ফিড আনা (ভিউ করা হয়নি)
        $res1 = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v2/stories/feed');

        $res1->assertStatus(200)
            ->assertJsonPath('success', true);

        $userStories = $res1->json('data');
        $this->assertNotEmpty($userStories);
        $this->assertFalse($userStories[0]['all_viewed']);

        // ২. ভিউ রেকর্ড করা
        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/stories/{$story->id}/view")
            ->assertStatus(200)
            ->assertJsonPath('data.view_recorded', true);

        // ৩. ফিড পুনরায় চেক (all_viewed = true)
        $res2 = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v2/stories/feed');

        $userStoriesAfter = $res2->json('data');
        $this->assertTrue($userStoriesAfter[0]['all_viewed']);
    }

    public function test_user_can_react_to_story_with_emojis_and_toggle(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        $story = Story::create([
            'user_id' => $author->id,
            'type' => 'text',
            'content' => 'React to this',
            'privacy' => 'public',
            'expires_at' => now()->addHours(24),
        ]);

        // ১. লাভ রিঅ্যাকশন দেওয়া
        $res1 = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/stories/{$story->id}/react", [
                'type' => 'love',
            ]);

        $res1->assertStatus(200)
            ->assertJsonPath('data.reacted', true)
            ->assertJsonPath('data.type', 'love')
            ->assertJsonPath('data.count', 1);

        $this->assertEquals(1, $story->fresh()->reactions_count);

        // ২. পুনরায় লাভ দেওয়া -> রিঅ্যাকশন টগল অফ হবে
        $res2 = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/stories/{$story->id}/react", [
                'type' => 'love',
            ]);

        $res2->assertStatus(200)
            ->assertJsonPath('data.reacted', false)
            ->assertJsonPath('data.count', 0);

        $this->assertEquals(0, $story->fresh()->reactions_count);
    }

    public function test_user_can_reply_to_story(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        $story = Story::create([
            'user_id' => $author->id,
            'type' => 'text',
            'content' => 'Reply to me',
            'privacy' => 'public',
            'allow_replies' => true,
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v2/stories/{$story->id}/reply", [
                'message' => 'অসাধারণ দৃশ্য ভাই!',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message', 'অসাধারণ দৃশ্য ভাই!');

        $this->assertDatabaseHas('story_replies', [
            'story_id' => $story->id,
            'user_id' => $viewer->id,
            'message' => 'অসাধারণ দৃশ্য ভাই!',
        ]);

        $this->assertEquals(1, $story->fresh()->replies_count);
    }

    public function test_story_author_can_view_viewers_list_stranger_cannot(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $stranger = User::factory()->create();

        $story = Story::create([
            'user_id' => $author->id,
            'type' => 'text',
            'content' => 'View test',
            'expires_at' => now()->addHours(24),
        ]);

        StoryView::create([
            'story_id' => $story->id,
            'user_id' => $viewer->id,
            'viewed_at' => now(),
        ]);

        // Author -> 200 OK
        $this->actingAs($author, 'sanctum')
            ->getJson("/api/v2/stories/{$story->id}/viewers")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // Stranger -> 403 Forbidden
        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v2/stories/{$story->id}/viewers")
            ->assertStatus(403);
    }

    public function test_user_can_archive_story(): void
    {
        $author = User::factory()->create();

        $story = Story::create([
            'user_id' => $author->id,
            'type' => 'text',
            'content' => 'Archive this story',
            'expires_at' => now()->addHours(24),
            'is_archived' => false,
        ]);

        $response = $this->actingAs($author, 'sanctum')
            ->postJson("/api/v2/stories/{$story->id}/archive");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue($story->fresh()->is_archived);

        // আর্কাইভড স্টোরিজ লিস্ট চেক
        $listRes = $this->actingAs($author, 'sanctum')
            ->getJson('/api/v2/stories/archived');

        $listRes->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_stories_expire_command_marks_expired_stories(): void
    {
        $user = User::factory()->create();

        $activeStory = Story::create([
            'user_id' => $user->id,
            'type' => 'text',
            'content' => 'Still active',
            'expires_at' => now()->addHours(10),
            'is_expired' => false,
        ]);

        $pastStory = Story::create([
            'user_id' => $user->id,
            'type' => 'text',
            'content' => 'Expired story',
            'expires_at' => now()->subHours(1),
            'is_expired' => false,
        ]);

        $this->artisan('stories:expire')->assertSuccessful();

        $this->assertFalse($activeStory->fresh()->is_expired);
        $this->assertTrue($pastStory->fresh()->is_expired);
    }
}
