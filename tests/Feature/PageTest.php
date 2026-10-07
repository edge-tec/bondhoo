<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\Services\PageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * পেজ ফিচার টেস্ট:
 * পেজ তৈরি, ফলো/আনফলো, এবং পেজ পোস্ট পাবলিশিং টেস্ট।
 */
class PageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pages', [
                'name' => 'Jugajug Official News',
                'category' => 'Media & News',
                'bio' => 'Official news and updates from Jugajug.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Jugajug Official News')
            ->assertJsonPath('data.slug', 'jugajug-official-news')
            ->assertJsonPath('data.is_owner', true);

        $this->assertDatabaseHas('pages', [
            'name' => 'Jugajug Official News',
            'owner_id' => $user->id,
        ]);
    }

    public function test_user_can_follow_and_unfollow_page(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();

        $pageService = app(PageService::class);
        $page = $pageService->createPage($owner, [
            'name' => 'Tech Insider',
            'category' => 'Technology',
        ]);

        // Follow page
        $followResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/pages/{$page->id}/follow");

        $followResponse->assertStatus(200)
            ->assertJsonPath('data.is_following', true)
            ->assertJsonPath('data.followers_count', 1);

        $this->assertEquals(1, $page->fresh()->followers_count);

        // Unfollow page
        $unfollowResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/pages/{$page->id}/follow");

        $unfollowResponse->assertStatus(200)
            ->assertJsonPath('data.is_following', false)
            ->assertJsonPath('data.followers_count', 0);

        $this->assertEquals(0, $page->fresh()->followers_count);
    }

    public function test_owner_can_publish_posts_to_page(): void
    {
        $owner = User::factory()->create();

        $pageService = app(PageService::class);
        $page = $pageService->createPage($owner, [
            'name' => 'Design Studio',
            'category' => 'Art & Design',
        ]);

        $postResponse = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/pages/{$page->id}/posts", [
                'content' => 'Announcing our new portfolio!',
            ]);

        $postResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.page_id', $page->id);

        $this->assertEquals(1, $page->fresh()->posts_count);

        // Verify page feed
        $feedResponse = $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/pages/{$page->id}/posts");

        $feedResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Announcing our new portfolio!');
    }

    public function test_non_owner_cannot_publish_posts_to_page(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $pageService = app(PageService::class);
        $page = $pageService->createPage($owner, [
            'name' => 'Corporate Page',
            'category' => 'Business',
        ]);

        $response = $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/pages/{$page->id}/posts", [
                'content' => 'Unauthorized post',
            ]);

        $response->assertStatus(403);
    }
}
