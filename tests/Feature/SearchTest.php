<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ইউনিভার্সাল সার্চ টেস্ট:
 * ইউজার, পোস্ট, গ্রুপ এবং পেজজুড়ে সার্বজনীন সার্চ ও ফিল্টারিং টেস্ট।
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_universal_search_returns_users_posts_groups_and_pages(): void
    {
        $viewer = User::factory()->create();

        // 1. Create matching user
        User::factory()->create([
            'name' => 'John Doe Software',
            'username' => 'johndoe_dev',
        ]);

        // 2. Create matching public post
        Post::create([
            'user_id' => $viewer->id,
            'content' => 'Building cool software applications on Laravel!',
            'audience' => 'public',
        ]);

        // 3. Create matching group
        Group::create([
            'name' => 'Software Engineers Hub',
            'slug' => 'software-engineers-hub',
            'privacy' => 'public',
            'creator_id' => $viewer->id,
        ]);

        // 4. Create matching page
        Page::create([
            'name' => 'Software Weekly Digest',
            'slug' => 'software-weekly-digest',
            'category' => 'Technology',
            'owner_id' => $viewer->id,
        ]);

        // Search query "Software"
        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=Software');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.query', 'Software');

        $this->assertNotEmpty($response->json('data.results.users'));
        $this->assertNotEmpty($response->json('data.results.posts'));
        $this->assertNotEmpty($response->json('data.results.groups'));
        $this->assertNotEmpty($response->json('data.results.pages'));
    }

    public function test_search_with_type_filter_returns_only_requested_entity(): void
    {
        $viewer = User::factory()->create();

        User::factory()->create(['name' => 'Rahim Developer']);
        Post::create([
            'user_id' => $viewer->id,
            'content' => 'Looking for a skilled developer.',
            'audience' => 'public',
        ]);

        // Filter only users
        $userSearchResponse = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=Developer&type=users');

        $userSearchResponse->assertStatus(200)
            ->assertJsonPath('data.type', 'users');

        $this->assertArrayHasKey('users', $userSearchResponse->json('data.results'));
        $this->assertArrayNotHasKey('posts', $userSearchResponse->json('data.results'));

        // Filter only posts
        $postSearchResponse = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=Developer&type=posts');

        $postSearchResponse->assertStatus(200)
            ->assertJsonPath('data.type', 'posts');

        $this->assertArrayHasKey('posts', $postSearchResponse->json('data.results'));
        $this->assertArrayNotHasKey('users', $postSearchResponse->json('data.results'));
    }

    public function test_empty_search_query_returns_empty_results(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/search?q=');

        $response->assertStatus(200)
            ->assertJsonPath('data.results.users', [])
            ->assertJsonPath('data.results.posts', [])
            ->assertJsonPath('data.results.groups', [])
            ->assertJsonPath('data.results.pages', []);
    }
}
