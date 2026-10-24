<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase12CompleteSearchEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => $user->first_name ?? 'Search',
            'last_name' => $user->last_name ?? 'User',
            'bio' => $attributes['bio'] ?? null,
        ]);

        return $user;
    }

    /**
     * Requirement 12.1: Multi-domain universal search across Users, Posts, Groups, and Pages.
     */
    public function test_universal_search_across_all_domains(): void
    {
        $viewer = $this->createUser(['name' => 'Search Explorer', 'username' => 'explorer']);

        // Domain 1: Users
        $targetUser = $this->createUser(['name' => 'Tech Master', 'username' => 'techguru']);

        // Domain 2: Posts (Public)
        $targetPost = Post::create([
            'user_id' => $targetUser->id,
            'content' => 'Exploring the future of Tech innovation in Dhaka!',
            'audience' => 'public',
        ]);

        // Domain 3: Groups (Public)
        $targetGroup = Group::create([
            'creator_id' => $targetUser->id,
            'name' => 'Tech Enthusiasts Bangladesh',
            'slug' => 'tech-enthusiasts-bd',
            'description' => 'A community for all tech lovers.',
            'privacy' => Group::PRIVACY_PUBLIC,
            'members_count' => 1,
        ]);

        // Domain 4: Pages
        $targetPage = Page::create([
            'owner_id' => $targetUser->id,
            'name' => 'Tech News Portal',
            'slug' => 'tech-news-portal',
            'category' => 'Technology',
            'bio' => 'Latest tech updates.',
        ]);

        // Execute Universal Search
        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=Tech&type=all');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.query', 'Tech')
            ->assertJsonPath('data.type', 'all');

        $data = $response->json('data.results');

        // Check Users
        $userIds = collect($data['users'])->pluck('id')->all();
        $this->assertContains($targetUser->id, $userIds);

        // Check Posts
        $postIds = collect($data['posts'])->pluck('id')->all();
        $this->assertContains($targetPost->id, $postIds);

        // Check Groups
        $groupIds = collect($data['groups'])->pluck('id')->all();
        $this->assertContains($targetGroup->id, $groupIds);

        // Check Pages
        $pageIds = collect($data['pages'])->pluck('id')->all();
        $this->assertContains($targetPage->id, $pageIds);
    }

    /**
     * Requirement 12.2: Scoped search by specific domain types (users, posts, groups, pages).
     */
    public function test_domain_specific_search_filtering(): void
    {
        $viewer = $this->createUser();
        $otherUser = $this->createUser(['name' => 'DevOps Specialist', 'username' => 'devops']);

        Post::create([
            'user_id' => $otherUser->id,
            'content' => 'DevOps practices for scalable cloud architectures',
            'audience' => 'public',
        ]);

        Group::create([
            'creator_id' => $otherUser->id,
            'name' => 'DevOps Community',
            'slug' => 'devops-community',
            'privacy' => Group::PRIVACY_PUBLIC,
            'members_count' => 1,
        ]);

        // 1. Scoped to Users
        $userSearch = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=DevOps&type=users');
        $userSearch->assertStatus(200)
            ->assertJsonPath('data.type', 'users')
            ->assertJsonCount(1, 'data.results.users')
            ->assertJsonMissingPath('data.results.posts')
            ->assertJsonMissingPath('data.results.groups');

        // 2. Scoped to Posts
        $postSearch = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=DevOps&type=posts');
        $postSearch->assertStatus(200)
            ->assertJsonPath('data.type', 'posts')
            ->assertJsonCount(1, 'data.results.posts')
            ->assertJsonMissingPath('data.results.users')
            ->assertJsonMissingPath('data.results.groups');

        // 3. Scoped to Groups
        $groupSearch = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=DevOps&type=groups');
        $groupSearch->assertStatus(200)
            ->assertJsonPath('data.type', 'groups')
            ->assertJsonCount(1, 'data.results.groups')
            ->assertJsonMissingPath('data.results.users')
            ->assertJsonMissingPath('data.results.posts');
    }

    /**
     * Requirement 12.3: Hashtag search in public posts.
     */
    public function test_hashtag_search_in_posts(): void
    {
        $viewer = $this->createUser();
        $author = $this->createUser();

        $laravelPost = Post::create([
            'user_id' => $author->id,
            'content' => 'Building enterprise apps with #laravel framework is amazing!',
            'audience' => 'public',
        ]);

        $reactPost = Post::create([
            'user_id' => $author->id,
            'content' => 'Building frontends with #react and Next.js.',
            'audience' => 'public',
        ]);

        $search = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=%23laravel&type=posts');

        $search->assertStatus(200);
        $postIds = collect($search->json('data.results.posts'))->pluck('id')->all();

        $this->assertContains($laravelPost->id, $postIds);
        $this->assertNotContains($reactPost->id, $postIds);
    }

    /**
     * Requirement 12.4: Strict bidirectional block isolation in search results.
     */
    public function test_bidirectional_block_isolation_in_search(): void
    {
        $blocker = $this->createUser(['name' => 'Alice Blocker', 'username' => 'alice']);
        $blocked = $this->createUser(['name' => 'Bob Blocked', 'username' => 'bob']);

        // Bob creates a public post
        $bobPost = Post::create([
            'user_id' => $blocked->id,
            'content' => 'Bob Secret Project Announcement',
            'audience' => 'public',
        ]);

        // Alice blocks Bob
        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/v1/users/{$blocked->id}/block")
            ->assertStatus(200);

        // 1. Blocker searches for Bob -> Bob is NOT found
        $aliceSearchUser = $this->actingAs($blocker, 'sanctum')
            ->getJson('/api/v1/search?q=Bob&type=users');
        $aliceFoundUserIds = collect($aliceSearchUser->json('data.results.users'))->pluck('id')->all();
        $this->assertNotContains($blocked->id, $aliceFoundUserIds);

        // 2. Blocker searches for Bob's post -> Post is NOT found
        $aliceSearchPost = $this->actingAs($blocker, 'sanctum')
            ->getJson('/api/v1/search?q=Secret&type=posts');
        $aliceFoundPostIds = collect($aliceSearchPost->json('data.results.posts'))->pluck('id')->all();
        $this->assertNotContains($bobPost->id, $aliceFoundPostIds);

        // 3. Bidirectional: Blocked Bob searches for Alice -> Alice is NOT found
        $bobSearchUser = $this->actingAs($blocked, 'sanctum')
            ->getJson('/api/v1/search?q=Alice&type=users');
        $bobFoundUserIds = collect($bobSearchUser->json('data.results.users'))->pluck('id')->all();
        $this->assertNotContains($blocker->id, $bobFoundUserIds);

        // 4. Self-isolation: Alice searches for "Alice" -> Alice does NOT see herself in users
        $aliceSelfSearch = $this->actingAs($blocker, 'sanctum')
            ->getJson('/api/v1/search?q=Alice&type=users');
        $selfFoundIds = collect($aliceSelfSearch->json('data.results.users'))->pluck('id')->all();
        $this->assertNotContains($blocker->id, $selfFoundIds);
    }

    /**
     * Requirement 12.5: Empty query autocomplete & suggestions.
     */
    public function test_empty_query_suggestions_excluding_blocked(): void
    {
        $viewer = $this->createUser(['name' => 'Active Viewer']);
        $popularUser = $this->createUser(['name' => 'Popular Influencer']);
        $blockedUser = $this->createUser(['name' => 'Blocked Entity']);

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v1/users/{$blockedUser->id}/block")
            ->assertStatus(200);

        // Fetch suggestions with suggest=true
        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?suggest=true');

        $response->assertStatus(200)
            ->assertJsonPath('data.is_suggestion', true);

        $suggestedUserIds = collect($response->json('data.results.users'))->pluck('id')->all();

        // Popular user is suggested
        $this->assertContains($popularUser->id, $suggestedUserIds);

        // Blocked user and self are strictly excluded
        $this->assertNotContains($blockedUser->id, $suggestedUserIds);
        $this->assertNotContains($viewer->id, $suggestedUserIds);
    }

    /**
     * Requirement 12.6: Search history recording, retrieval, and clearing.
     */
    public function test_search_history_recording_retrieval_and_clearing(): void
    {
        $user = $this->createUser();

        // 1. Initial history is empty
        $initHistory = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/search/history');
        $initHistory->assertStatus(200)
            ->assertJsonPath('data', []);

        // 2. Perform searches (these record history)
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/search?q=Laravel');
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/search?q=Vue3');
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/search?q=Tailwind');

        // Suggestions should NOT record history
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/search?suggest=true');

        // 3. Retrieve history (most recent first)
        $historyRes = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/search/history');

        $historyRes->assertStatus(200);
        $history = $historyRes->json('data');

        $this->assertEquals(['Tailwind', 'Vue3', 'Laravel'], $history);

        // 4. Clear search history
        $clearRes = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/search/history');

        $clearRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 5. Verify history is empty
        $afterClear = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/search/history');
        $afterClear->assertStatus(200)
            ->assertJsonPath('data', []);
    }
}
