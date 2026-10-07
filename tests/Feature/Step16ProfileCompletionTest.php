<?php

namespace Tests\Feature;

use App\Models\ProfileEducation;
use App\Models\ProfileExperience;
use App\Models\ProfileInterest;
use App\Models\ProfileLanguage;
use App\Models\ProfileSkill;
use App\Models\ProfileSocialLink;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\ProfileCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Step16ProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(array $attributes = []): User
    {
        static $counter = 500;
        $counter++;

        $user = User::factory()->create(array_merge([
            'username' => "complete_user_{$counter}",
            'name' => "Complete User {$counter}",
            'email' => "complete_user_{$counter}@example.com",
            'phone' => "+880170011{$counter}",
            'status' => 'active',
            'country' => null, // empty country initially
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ], $attributes));

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'avatar_url' => null,
                'avatar_media_id' => null,
                'cover_url' => null,
                'cover_media_id' => null,
                'bio' => null,
                'about' => null,
                'location' => null,
                'city' => null,
            ]
        );

        return $user;
    }

    public function test_baseline_completion_with_only_name_and_username(): void
    {
        $user = $this->createUser();

        /** @var ProfileCompletionService $service */
        $service = app(ProfileCompletionService::class);
        $result = $service->calculate($user);

        // 2 out of 13 sections completed (name, username) => round(2/13 * 100) = 15%
        $this->assertEquals(15, $result['percentage']);
        $this->assertEquals(2, $result['completed_count']);
        $this->assertEquals(13, $result['total_count']);

        $this->assertContains('name', $result['completed_items']);
        $this->assertContains('username', $result['completed_items']);
        $this->assertNotContains('avatar', $result['completed_items']);
        $this->assertContains('avatar', $result['remaining_items']);

        // Assert database persistence in profile_completions
        $this->assertDatabaseHas('profile_completions', [
            'user_id' => $user->id,
            'completion_percentage' => 15,
            'has_name' => true,
            'has_username' => true,
            'has_avatar' => false,
            'total_sections' => 13,
            'completed_count' => 2,
        ]);
    }

    public function test_completion_increments_step_by_step_to_100_percent(): void
    {
        $user = $this->createUser();
        $service = app(ProfileCompletionService::class);

        // 1. Initially 2/13 = 15% (Name, Username)
        $this->assertEquals(15, $service->calculate($user)['percentage']);

        // 2. Add Avatar -> 3/13 = 23%
        $user->profile->update(['avatar_url' => 'https://cdn.jugajug.com/avatar123.jpg']);
        $user->refresh();
        $this->assertEquals(23, $service->calculate($user)['percentage']);

        // 3. Add Cover -> 4/13 = 31%
        $user->profile->update(['cover_url' => 'https://cdn.jugajug.com/cover123.jpg']);
        $user->refresh();
        $this->assertEquals(31, $service->calculate($user)['percentage']);

        // 4. Add Bio -> 5/13 = 38%
        $user->profile->update(['bio' => 'Building Bangladesh’s premier network.']);
        $user->refresh();
        $this->assertEquals(38, $service->calculate($user)['percentage']);

        // 5. Add About -> 6/13 = 46%
        $user->profile->update(['about' => 'Full-stack software architect & tech enthusiast.']);
        $user->refresh();
        $this->assertEquals(46, $service->calculate($user)['percentage']);

        // 6. Add Location -> 7/13 = 54%
        $user->update(['country' => 'Bangladesh']);
        $user->refresh();
        $this->assertEquals(54, $service->calculate($user)['percentage']);

        // 7. Add Education -> 8/13 = 62%
        ProfileEducation::create([
            'user_id' => $user->id,
            'institution_name' => 'Dhaka University',
            'degree' => 'B.Sc. in Computer Science',
            'start_date' => '2016-01-01',
            'end_date' => '2020-12-31',
        ]);
        $user->refresh();
        $this->assertEquals(62, $service->calculate($user)['percentage']);

        // 8. Add Work Experience -> 9/13 = 69%
        ProfileExperience::create([
            'user_id' => $user->id,
            'company_name' => 'Jugajug Technologies',
            'job_title' => 'Senior Engineer',
            'start_date' => '2021-01-01',
            'is_current' => true,
        ]);
        $user->refresh();
        $this->assertEquals(69, $service->calculate($user)['percentage']);

        // 9. Add Skill -> 10/13 = 77%
        ProfileSkill::create([
            'user_id' => $user->id,
            'name' => 'Laravel',
            'display_order' => 1,
        ]);
        $user->refresh();
        $this->assertEquals(77, $service->calculate($user)['percentage']);

        // 10. Add Interest -> 11/13 = 85%
        ProfileInterest::create([
            'user_id' => $user->id,
            'name' => 'Artificial Intelligence',
            'display_order' => 1,
        ]);
        $user->refresh();
        $this->assertEquals(85, $service->calculate($user)['percentage']);

        // 11. Add Language -> 12/13 = 92%
        ProfileLanguage::create([
            'user_id' => $user->id,
            'language' => 'Bengali',
            'proficiency' => 'native',
        ]);
        $user->refresh();
        $this->assertEquals(92, $service->calculate($user)['percentage']);

        // 12. Add Social Link -> 13/13 = 100%!
        ProfileSocialLink::create([
            'user_id' => $user->id,
            'platform' => 'github',
            'url' => 'https://github.com/developer',
        ]);
        $user->refresh();
        $final = $service->calculate($user);
        $this->assertEquals(100, $final['percentage']);
        $this->assertEquals(13, $final['completed_count']);
        $this->assertEmpty($final['remaining_items']);
        $this->assertCount(13, $final['completed_items']);

        // Assert database record reflects 100%
        $this->assertDatabaseHas('profile_completions', [
            'user_id' => $user->id,
            'completion_percentage' => 100,
            'completed_count' => 13,
            'total_sections' => 13,
        ]);
    }

    public function test_completion_decreases_dynamically_when_record_deleted(): void
    {
        $user = $this->createUser();
        $service = app(ProfileCompletionService::class);

        $skill = ProfileSkill::create([
            'user_id' => $user->id,
            'name' => 'PHP',
        ]);

        // 3 out of 13 = 23%
        $this->assertEquals(23, $service->calculate($user)['percentage']);

        // Delete skill
        $skill->delete();
        $user->refresh();

        // Drops back to 2 out of 13 = 15%
        $this->assertEquals(15, $service->calculate($user)['percentage']);
    }

    public function test_get_api_v1_profile_completion_endpoint_success(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile/completion');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'percentage' => 15,
                    'completed_count' => 2,
                    'total_count' => 13,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'percentage',
                    'completed_items',
                    'remaining_items',
                    'completed_count',
                    'total_count',
                    'sections',
                    'last_calculated_at',
                ],
            ]);
    }

    public function test_get_api_v2_profile_completion_endpoint_success(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/profile/completion');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'percentage' => 15,
                    'completed_count' => 2,
                    'total_count' => 13,
                ],
            ]);
    }

    public function test_profile_completion_requires_authentication(): void
    {
        $this->getJson('/api/v1/profile/completion')->assertStatus(401);
        $this->getJson('/api/v2/profile/completion')->assertStatus(401);
    }

    public function test_profile_completion_caching_and_invalidation(): void
    {
        $user = $this->createUser();
        $service = app(ProfileCompletionService::class);

        // Fetch from service (caches calculation)
        $first = $service->getCompletion($user);
        $this->assertEquals(15, $first['percentage']);
        $this->assertTrue(Cache::has("profile_completion_{$user->id}"));

        // Add an education record
        ProfileEducation::create([
            'user_id' => $user->id,
            'institution_name' => 'BUET',
            'degree' => 'B.Sc.',
            'start_date' => '2015-01-01',
        ]);

        // Prior to invalidation, cached value is 15
        $this->assertEquals(15, $service->getCompletion($user)['percentage']);

        // Invalidate
        $service->invalidate($user);
        $this->assertFalse(Cache::has("profile_completion_{$user->id}"));

        // After invalidation, dynamic calculation is refreshed (3/13 = 23%)
        $refreshed = $service->getCompletion($user);
        $this->assertEquals(23, $refreshed['percentage']);
    }

    public function test_profile_dashboard_includes_completion_data(): void
    {
        $user = $this->createUser();

        // GET /api/v1/profile (me)
        $resV1 = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile');

        $resV1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'completion' => [
                            'percentage' => 15,
                            'total_count' => 13,
                        ],
                    ],
                    'stats' => [
                        'completion_percentage' => 15,
                    ],
                ],
            ]);

        // GET /api/v2/profile/{username}
        $resV2 = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v2/profile/{$user->username}");

        $resV2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'completion' => [
                            'percentage' => 15,
                            'total_count' => 13,
                        ],
                    ],
                    'stats' => [
                        'completion_percentage' => 15,
                    ],
                ],
            ]);
    }
}
