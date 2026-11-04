<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Friendship;
use App\Models\Group;
use App\Models\JobPosting;
use App\Models\Page;
use App\Models\Post;
use App\Models\ProfileSkill;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 11: Complete Professional Global Search & Discovery System Feature Test
 */
class Phase11CompleteProfessionalGlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_unified_search_returns_users_posts_groups_pages_and_events(): void
    {
        $viewer = User::factory()->create();

        // Target matching entities
        $user = User::factory()->create([
            'name' => 'Arafat Rahman Engineer',
            'username' => 'arafat_eng',
        ]);

        $post = Post::create([
            'user_id' => $viewer->id,
            'content' => 'We are hiring an Engineer for cloud infrastructure!',
            'audience' => 'public',
        ]);

        $group = Group::create([
            'name' => 'Bangladesh Engineer Community',
            'slug' => 'bangladesh-engineer-community',
            'privacy' => Group::PRIVACY_PUBLIC,
            'status' => Group::STATUS_ACTIVE,
            'creator_id' => $viewer->id,
        ]);

        $page = Page::create([
            'name' => 'Engineer Daily Official',
            'slug' => 'engineer-daily-official',
            'category' => 'Technology',
            'owner_id' => $viewer->id,
            'status' => Page::STATUS_ACTIVE,
        ]);

        $event = Event::create([
            'title' => 'National Engineer Summit 2026',
            'description' => 'Annual conference for professional engineers in Dhaka',
            'creator_id' => $viewer->id,
            'start_time' => now()->addDays(7),
            'end_time' => now()->addDays(8),
            'location_type' => 'physical',
            'address' => 'BICC, Dhaka',
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=Engineer');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.query', 'Engineer');

        $results = $response->json('data.results');

        $this->assertNotEmpty($results['users']);
        $this->assertEquals($user->id, $results['users'][0]['id']);

        $this->assertNotEmpty($results['posts']);
        $this->assertEquals($post->id, $results['posts'][0]['id']);

        $this->assertNotEmpty($results['groups']);
        $this->assertEquals($group->id, $results['groups'][0]['id']);

        $this->assertNotEmpty($results['pages']);
        $this->assertEquals($page->id, $results['pages'][0]['id']);

        $this->assertNotEmpty($results['events']);
        $this->assertEquals($event->id, $results['events'][0]['id']);
    }

    public function test_search_respects_privacy_and_blocked_users(): void
    {
        $viewer = User::factory()->create();

        // 1. Blocked User
        $blockedUser = User::factory()->create([
            'name' => 'Blocked Developer',
            'username' => 'blocked_dev',
        ]);
        Friendship::create([
            'user_id' => $viewer->id,
            'friend_id' => $blockedUser->id,
            'status' => Friendship::STATUS_BLOCKED,
        ]);

        // 2. Normal User
        $allowedUser = User::factory()->create([
            'name' => 'Allowed Developer',
            'username' => 'allowed_dev',
        ]);

        // 3. Post by Blocked User
        Post::create([
            'user_id' => $blockedUser->id,
            'content' => 'Important Developer Announcement',
            'audience' => 'public',
        ]);

        // 4. Hidden Group without membership
        $hiddenGroup = Group::create([
            'name' => 'Hidden Developer Secret Guild',
            'slug' => 'hidden-dev-guild',
            'privacy' => Group::PRIVACY_HIDDEN,
            'status' => Group::STATUS_ACTIVE,
            'creator_id' => $blockedUser->id,
        ]);

        // Search "Developer"
        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=Developer');

        $response->assertStatus(200);
        $results = $response->json('data.results');

        // Blocked user must NOT be in results
        $userIds = collect($results['users'])->pluck('id')->all();
        $this->assertNotContains($blockedUser->id, $userIds);
        $this->assertContains($allowedUser->id, $userIds);

        // Posts by blocked user must NOT be in results
        $postUserIds = collect($results['posts'])->pluck('author.id')->all();
        $this->assertNotContains($blockedUser->id, $postUserIds);

        // Hidden group must NOT be in results
        $groupIds = collect($results['groups'])->pluck('id')->all();
        $this->assertNotContains($hiddenGroup->id, $groupIds);
    }

    public function test_search_suggestions_returned_on_empty_query_when_requested(): void
    {
        $viewer = User::factory()->create();

        User::factory()->count(3)->create();
        Group::create([
            'name' => 'Featured Open Group',
            'slug' => 'featured-open-group',
            'privacy' => Group::PRIVACY_PUBLIC,
            'status' => Group::STATUS_ACTIVE,
            'creator_id' => $viewer->id,
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?suggest=1');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_suggestion', true);

        $results = $response->json('data.results');
        $this->assertNotEmpty($results['users']);
        $this->assertNotEmpty($results['groups']);
        $this->assertNotEmpty($results['trending']);
    }

    public function test_search_history_recording_retrieval_and_deletion(): void
    {
        $viewer = User::factory()->create();

        // 1. Perform a search to record history
        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=Flutter');

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=Laravel');

        // 2. Retrieve history
        $historyRes = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search/history');

        $historyRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $history = $historyRes->json('data');
        $this->assertContains('Laravel', $history);
        $this->assertContains('Flutter', $history);

        // 3. Remove single item
        $deleteSingleRes = $this->actingAs($viewer, 'sanctum')
            ->deleteJson('/api/v1/search/history/Flutter');

        $deleteSingleRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $updatedHistory = $deleteSingleRes->json('data');
        $this->assertNotContains('Flutter', $updatedHistory);
        $this->assertContains('Laravel', $updatedHistory);

        // 4. Clear all history
        $clearAllRes = $this->actingAs($viewer, 'sanctum')
            ->deleteJson('/api/v1/search/history');

        $clearAllRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify history is empty
        $emptyHistoryRes = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search/history');

        $this->assertEmpty($emptyHistoryRes->json('data'));
    }

    public function test_advanced_search_filters_and_job_search(): void
    {
        $viewer = User::factory()->create();

        // 1. User with Profile location, industry, and skills
        $user1 = User::factory()->create(['name' => 'Fahim Architect']);
        UserProfile::create([
            'user_id' => $user1->id,
            'location' => 'Gulshan, Dhaka',
            'country' => 'Bangladesh',
            'category' => 'Software Engineering',
        ]);
        ProfileSkill::create([
            'user_id' => $user1->id,
            'name' => 'Kubernetes',
        ]);

        $user2 = User::factory()->create(['name' => 'Tanvir Architect']);
        UserProfile::create([
            'user_id' => $user2->id,
            'location' => 'Chittagong',
            'country' => 'Bangladesh',
            'category' => 'Civil Engineering',
        ]);

        // Search with skills filter
        $skillFilterRes = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=Architect&type=users&skills=Kubernetes');

        $skillFilterRes->assertStatus(200);
        $userIds = collect($skillFilterRes->json('data.results.users'))->pluck('id')->all();
        $this->assertContains($user1->id, $userIds);
        $this->assertNotContains($user2->id, $userIds);

        // Search with location filter
        $locFilterRes = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=Architect&type=users&location=Gulshan');

        $locFilterRes->assertStatus(200);
        $locUserIds = collect($locFilterRes->json('data.results.users'))->pluck('id')->all();
        $this->assertContains($user1->id, $locUserIds);
        $this->assertNotContains($user2->id, $locUserIds);

        // 2. Job Postings Search
        $job = JobPosting::create([
            'user_id' => $viewer->id,
            'title' => 'Senior Cloud Architect',
            'company_name' => 'Connectly Global',
            'description' => 'Lead our cloud migration architecture.',
            'location' => 'Dhaka',
            'location_type' => 'hybrid',
            'employment_type' => 'full_time',
            'status' => JobPosting::STATUS_ACTIVE,
        ]);

        $jobRes = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=Cloud&type=jobs&job_type=full_time');

        $jobRes->assertStatus(200);
        $jobs = $jobRes->json('data.results.jobs');
        $this->assertNotEmpty($jobs);
        $this->assertEquals($job->id, $jobs[0]['id']);
        $this->assertEquals('Connectly Global', $jobs[0]['company_name']);
    }

    public function test_hashtag_search_extracts_and_counts_tags(): void
    {
        $viewer = User::factory()->create();

        Post::create([
            'user_id' => $viewer->id,
            'content' => 'Excited about #ConnectlyDev launch today! #TechSummit',
            'audience' => 'public',
        ]);

        Post::create([
            'user_id' => $viewer->id,
            'content' => 'Another post with #ConnectlyDev updates!',
            'audience' => 'public',
        ]);

        $res = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=ConnectlyDev&type=hashtags');

        $res->assertStatus(200);
        $hashtags = $res->json('data.results.hashtags');
        $this->assertNotEmpty($hashtags);
        $this->assertEquals('#ConnectlyDev', $hashtags[0]['tag']);
        $this->assertEquals(2, $hashtags[0]['count']);
    }

    public function test_search_validates_input_and_rejects_unsupported_type_or_invalid_dates(): void
    {
        $viewer = User::factory()->create();

        // 1. Unsupported type
        $invalidTypeRes = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=test&type=malicious_type');

        $invalidTypeRes->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['type']]);

        // 2. Invalid date range (date_to earlier than date_from)
        $invalidDateRes = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=test&date_from=2026-11-10&date_to=2026-11-01');

        $invalidDateRes->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['date_to']]);

        // 3. Excessive limit
        $excessiveLimitRes = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/search?q=test&limit=999');

        $excessiveLimitRes->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['limit']]);
    }

    public function test_search_history_idor_protection_and_user_isolation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // User A searches for React
        $this->actingAs($userA, 'sanctum')->getJson('/api/v1/search?q=React');

        // User B searches for Vue
        $this->actingAs($userB, 'sanctum')->getJson('/api/v1/search?q=Vue');

        // User A checks history -> only React, NOT Vue
        $historyARes = $this->actingAs($userA, 'sanctum')->getJson('/api/v1/search/history');
        $historyARes->assertStatus(200);
        $this->assertContains('React', $historyARes->json('data'));
        $this->assertNotContains('Vue', $historyARes->json('data'));

        // User B checks history -> only Vue, NOT React
        $historyBRes = $this->actingAs($userB, 'sanctum')->getJson('/api/v1/search/history');
        $historyBRes->assertStatus(200);
        $this->assertContains('Vue', $historyBRes->json('data'));
        $this->assertNotContains('React', $historyBRes->json('data'));

        // User A attempts to delete Vue -> does NOT affect User B's history
        $deleteRes = $this->actingAs($userA, 'sanctum')->deleteJson('/api/v1/search/history/Vue');
        $deleteRes->assertStatus(200);

        $verifyBRes = $this->actingAs($userB, 'sanctum')->getJson('/api/v1/search/history');
        $this->assertContains('Vue', $verifyBRes->json('data'));
    }
}
