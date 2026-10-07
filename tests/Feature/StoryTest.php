<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * স্টোরি ফিচার টেস্ট:
 * ২৪ ঘণ্টার স্টোরি তৈরি, মিডিয়া অ্যাটাচমেন্ট, ভিউ ট্র্যাকিং এবং মেয়াদের ফিল্টারিং টেস্ট।
 */
class StoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_text_story(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/stories', [
                'type' => 'text',
                'content' => 'Having a wonderful day!',
                'background_color' => '#FF5733',
                'privacy' => 'public',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Having a wonderful day!')
            ->assertJsonPath('data.background_color', '#FF5733');

        $this->assertDatabaseHas('stories', [
            'user_id' => $user->id,
            'content' => 'Having a wonderful day!',
        ]);
    }

    public function test_user_can_create_media_story(): void
    {
        $user = User::factory()->create();

        $media = Media::create([
            'user_id' => $user->id,
            'collection' => 'general',
            'disk' => 'public',
            'original_path' => 'stories/sunset.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 20480,
            'processing_status' => 'ready',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/stories', [
                'type' => 'media',
                'content' => 'Sunset at Cox\'s Bazar',
                'media_ids' => [$media->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'media')
            ->assertJsonPath('data.media.0.id', $media->id);

        $this->assertEquals(Story::class, $media->fresh()->mediable_type);
        $this->assertEquals('story', $media->fresh()->collection);
    }

    public function test_story_feed_filters_expired_stories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Active story (expires in 20 hours)
        Story::create([
            'user_id' => $otherUser->id,
            'type' => 'text',
            'content' => 'Active Story',
            'privacy' => 'public',
            'expires_at' => now()->addHours(20),
        ]);

        // Expired story (expired 2 hours ago)
        Story::create([
            'user_id' => $otherUser->id,
            'type' => 'text',
            'content' => 'Old Expired Story',
            'privacy' => 'public',
            'expires_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/stories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $stories = $response->json('data.0.stories');
        $this->assertCount(1, $stories);
        $this->assertEquals('Active Story', $stories[0]['content']);
    }

    public function test_user_can_record_story_view_and_increments_counter_once(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        $story = Story::create([
            'user_id' => $author->id,
            'type' => 'text',
            'content' => 'Watch this story',
            'expires_at' => now()->addHours(24),
            'views_count' => 0,
        ]);

        // First view -> increases count to 1
        $response1 = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v1/stories/{$story->id}/view");

        $response1->assertStatus(200)
            ->assertJsonPath('data.view_recorded', true)
            ->assertJsonPath('data.views_count', 1);

        $this->assertEquals(1, $story->fresh()->views_count);

        // Second view from same user -> does not increase count
        $response2 = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v1/stories/{$story->id}/view");

        $response2->assertStatus(200)
            ->assertJsonPath('data.view_recorded', false)
            ->assertJsonPath('data.views_count', 1);

        $this->assertEquals(1, $story->fresh()->views_count);
    }

    public function test_only_story_author_can_view_story_viewers(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $stranger = User::factory()->create();

        $story = Story::create([
            'user_id' => $author->id,
            'type' => 'text',
            'content' => 'Viewer test',
            'expires_at' => now()->addHours(24),
        ]);

        // Viewer watches the story
        $this->actingAs($viewer, 'sanctum')->postJson("/api/v1/stories/{$story->id}/view");

        // Author views the viewers list -> 200 OK
        $response = $this->actingAs($author, 'sanctum')
            ->getJson("/api/v1/stories/{$story->id}/views");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.id', $viewer->id);

        // Stranger tries to view the viewers list -> 403 Forbidden
        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/stories/{$story->id}/views")
            ->assertStatus(403);
    }

    public function test_user_can_delete_own_story(): void
    {
        $user = User::factory()->create();
        $story = Story::create([
            'user_id' => $user->id,
            'type' => 'text',
            'content' => 'Delete me',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/stories/{$story->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('stories', ['id' => $story->id]);
    }
}
