<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Database\Seeders\RolePermissionSeeder;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase11CompleteMemoriesSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createUser(string $username, string $name, string $role = 'USER'): User
    {
        $user = User::factory()->create([
            'username' => $username,
            'name' => $name,
            'email' => "{$username}@example.com",
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'avatar_url' => "https://ui-avatars.com/api/?name={$username}",
                'bio' => "Bio of {$name}",
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'dark_mode' => true,
            ]
        );

        $roleModel = Role::where('name', $role)->first();
        if ($roleModel) {
            $user->roles()->syncWithoutDetaching([$roleModel->id]);
        }

        return $user;
    }

    protected function createHistoricalPost(User $user, string $content, DateTimeInterface $createdAt): Post
    {
        $post = Post::create([
            'user_id' => $user->id,
            'content' => $content,
            'status' => 'published',
        ]);
        $post->timestamps = false;
        $post->created_at = $createdAt;
        $post->updated_at = $createdAt;
        $post->saveQuietly();

        return $post;
    }

    /**
     * 1. Test 'On This Day' Algorithm for Past Years' Posts
     */
    public function test_on_this_day_exact_memories_retrieval(): void
    {
        $user = $this->createUser('nostalgia_user', 'Nostalgia User');

        // Create posts from exactly 1 and 2 years ago today
        $this->createHistoricalPost(
            $user,
            'Launching our beta release today! A year to remember.',
            now()->subYear()
        );

        $this->createHistoricalPost(
            $user,
            'First day writing code for Connectly! Incredible excitement.',
            now()->subYears(2)
        );

        $res = $this->actingAs($user, 'sanctum')->getJson('/api/v1/memories');

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_count', 2);

        $todayMemories = $res->json('data.today_memories');
        $this->assertCount(2, $todayMemories);

        $this->assertEquals(1, $todayMemories[0]['years_ago']);
        $this->assertStringContainsString('1 বছর আগের আজকের দিনে', $todayMemories[0]['years_ago_label']);

        $this->assertEquals(2, $todayMemories[1]['years_ago']);
        $this->assertStringContainsString('2 বছর আগের আজকের দিনে', $todayMemories[1]['years_ago_label']);
    }

    /**
     * 2. Test Fallback to Historical Memories Around This Week
     */
    public function test_fallback_recent_historical_memories(): void
    {
        $user = $this->createUser('fallback_user', 'Fallback User');

        // Post from past year, but 3 days before today (not exact day match)
        $pastWeekPost = $this->createHistoricalPost(
            $user,
            'Mid-autumn reflection on engineering challenges.',
            now()->subYear()->subDays(3)
        );

        $res = $this->actingAs($user, 'sanctum')->getJson('/api/v1/memories');

        $res->assertStatus(200);
        $pastWeekMemories = $res->json('data.past_week_memories');
        $this->assertNotEmpty($pastWeekMemories);
        $this->assertEquals($pastWeekPost->id, $pastWeekMemories[0]['id']);
    }

    /**
     * 3. Test Empty State for New User
     */
    public function test_empty_memories_state_for_new_user(): void
    {
        $user = $this->createUser('new_user_today', 'New User Today');

        $res = $this->actingAs($user, 'sanctum')->getJson('/api/v1/memories');

        $res->assertStatus(200)
            ->assertJsonPath('data.total_count', 0)
            ->assertJsonPath('data.today_memories', [])
            ->assertJsonPath('data.past_week_memories', []);
    }

    /**
     * 4. Test Sharing / Reposting a Memory with Caption
     */
    public function test_share_memory_as_new_post(): void
    {
        $user = $this->createUser('sharing_user', 'Sharing User');

        $historicalPost = $this->createHistoricalPost(
            $user,
            'Old memory from 3 years ago.',
            now()->subYears(3)
        );

        // User shares memory with custom caption
        $shareRes = $this->actingAs($user, 'sanctum')->postJson("/api/v1/posts/{$historicalPost->id}/share", [
            'caption' => 'Looking back at this moment 3 years ago! Time flies so fast. ❤️',
        ]);

        $shareRes->assertStatus(200);

        $this->assertDatabaseHas('post_shares', [
            'user_id' => $user->id,
            'post_id' => $historicalPost->id,
            'caption' => 'Looking back at this moment 3 years ago! Time flies so fast. ❤️',
        ]);

        $historicalPost->refresh();
        $this->assertEquals(1, $historicalPost->shares_count);
    }

    /**
     * 5. Test Web Route Resolution and Memories Presentation
     */
    public function test_memories_web_routes_and_navigation(): void
    {
        $user = $this->createUser('web_memories_user', 'Web Memories User');

        $this->createHistoricalPost(
            $user,
            'A cherished post from the archives',
            now()->subYear()
        );

        // 1. /memories route redirects to dashboard?tab=memories
        $res = $this->actingAs($user, 'web')->get('/memories');
        $res->assertStatus(302)
            ->assertRedirect('/dashboard?tab=memories');

        // 2. /dashboard?tab=memories renders successfully with the memory post
        $dashboardRes = $this->actingAs($user, 'web')->get('/dashboard?tab=memories');
        $dashboardRes->assertStatus(200);
    }
}
