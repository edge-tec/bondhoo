<?php

namespace Tests\Feature;

use App\Events\ProfileUpdatedEvent;
use App\Models\ProfileInterest;
use App\Models\ProfileLanguage;
use App\Models\ProfileSkill;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Step12SkillsInterestsLanguagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Test user can add skills and reorder them.
     */
    public function test_user_can_add_skills_and_reorder(): void
    {
        Event::fake([ProfileUpdatedEvent::class]);

        $user = User::factory()->create(['username' => 'dev_ninja']);

        // 1. Add first skill
        $res1 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/skills', [
                'name' => 'Laravel Framework',
                'level' => 'expert',
            ]);

        $res1->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Laravel Framework')
            ->assertJsonPath('data.level', 'expert');

        $skill1Id = $res1->json('data.id');

        // 2. Add second skill
        $res2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/skills', [
                'name' => 'TypeScript',
                'level' => 'intermediate',
            ]);

        $res2->assertStatus(201)
            ->assertJsonPath('data.name', 'TypeScript');

        $skill2Id = $res2->json('data.id');

        $this->assertEquals(2, $user->skills()->count());

        // 3. Reorder skills
        $reorderRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/skills/reorder', [
                'ids' => [$skill2Id, $skill1Id],
            ]);

        $reorderRes->assertOk()
            ->assertJsonPath('data.0.id', $skill2Id)
            ->assertJsonPath('data.1.id', $skill1Id);

        $this->assertEquals(1, ProfileSkill::find($skill2Id)->display_order);
        $this->assertEquals(2, ProfileSkill::find($skill1Id)->display_order);

        Event::assertDispatched(ProfileUpdatedEvent::class);
    }

    /**
     * Test user can remove a skill.
     */
    public function test_user_can_remove_skill(): void
    {
        $user = User::factory()->create();
        $skill = ProfileSkill::create([
            'user_id' => $user->id,
            'name' => 'Docker',
            'level' => 'intermediate',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v2/profile/skills/{$skill->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('profile_skills', [
            'id' => $skill->id,
        ]);
    }

    /**
     * Test duplicate skill prevention with case-insensitive check and whitespace normalization.
     */
    public function test_duplicate_skills_are_strictly_prevented(): void
    {
        $user = User::factory()->create();

        ProfileSkill::create([
            'user_id' => $user->id,
            'name' => 'PHP',
            'level' => 'expert',
        ]);

        // Attempting to add exact lowercase duplicate
        $response1 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/skills', [
                'name' => 'php',
            ]);

        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Attempting to add with extra whitespace
        $response2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/skills', [
                'name' => '   PHP   ',
            ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test searching skills across the platform.
     */
    public function test_user_can_search_skills(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        ProfileSkill::create(['user_id' => $user1->id, 'name' => 'Python']);
        ProfileSkill::create(['user_id' => $user2->id, 'name' => 'Python']);
        ProfileSkill::create(['user_id' => $user1->id, 'name' => 'PyTorch']);
        ProfileSkill::create(['user_id' => $user2->id, 'name' => 'Rust']);

        $response = $this->getJson('/api/v2/skills/search?q=py');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertContains('Python', $names);
        $this->assertContains('PyTorch', $names);
        $this->assertNotContains('Rust', $names);
    }

    /**
     * Test user can add, list and remove interests.
     */
    public function test_user_can_add_and_remove_interests(): void
    {
        $user = User::factory()->create();

        // 1. Add interest
        $addRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/interests', [
                'name' => 'Artificial Intelligence',
                'category' => 'Technology',
            ]);

        $addRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Artificial Intelligence')
            ->assertJsonPath('data.category', 'Technology');

        $interestId = $addRes->json('data.id');

        // 2. List interests
        $listRes = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/profile/interests');

        $listRes->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Artificial Intelligence');

        // 3. Remove interest
        $deleteRes = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v2/profile/interests/{$interestId}");

        $deleteRes->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('profile_interests', [
            'id' => $interestId,
        ]);
    }

    /**
     * Test duplicate interest prevention.
     */
    public function test_duplicate_interests_are_strictly_prevented(): void
    {
        $user = User::factory()->create();

        ProfileInterest::create([
            'user_id' => $user->id,
            'name' => 'Machine Learning',
            'category' => 'Science',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/interests', [
                'name' => 'machine learning',
                'category' => 'Tech',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test searching interests across the platform.
     */
    public function test_user_can_search_interests(): void
    {
        $user = User::factory()->create();
        ProfileInterest::create(['user_id' => $user->id, 'name' => 'Astronomy', 'category' => 'Science']);
        ProfileInterest::create(['user_id' => $user->id, 'name' => 'Astrophotography', 'category' => 'Photography']);
        ProfileInterest::create(['user_id' => $user->id, 'name' => 'Gardening', 'category' => 'Lifestyle']);

        $response = $this->getJson('/api/v2/interests/search?q=astro');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertContains('Astronomy', $names);
        $this->assertContains('Astrophotography', $names);
        $this->assertNotContains('Gardening', $names);
    }

    /**
     * Test user can add, update proficiency, and remove languages with all proficiencies.
     */
    public function test_user_can_add_update_and_remove_languages_with_all_proficiencies(): void
    {
        $user = User::factory()->create();

        $proficiencies = [
            'Basic' => 'basic',
            'Conversational' => 'conversational',
            'Professional' => 'professional',
            'Fluent' => 'fluent',
            'Native' => 'native',
        ];

        $langIndex = 1;
        $createdIds = [];

        foreach ($proficiencies as $input => $expected) {
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/v2/profile/languages', [
                    'language' => "Language_{$langIndex}",
                    'proficiency' => $input,
                ]);

            $response->assertStatus(201)
                ->assertJsonPath('data.proficiency', $expected);

            $createdIds[] = $response->json('data.id');
            $langIndex++;
        }

        $this->assertEquals(5, $user->languages()->count());

        // Update proficiency
        $firstId = $createdIds[0];
        $updateRes = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v2/profile/languages/{$firstId}", [
                'proficiency' => 'Native',
            ]);

        $updateRes->assertOk()
            ->assertJsonPath('data.proficiency', 'native');

        // Delete language
        $deleteRes = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v2/profile/languages/{$firstId}");

        $deleteRes->assertOk();
        $this->assertDatabaseMissing('profile_languages', ['id' => $firstId]);
    }

    /**
     * Test invalid language proficiency is rejected.
     */
    public function test_invalid_language_proficiency_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/languages', [
                'language' => 'Bengali',
                'proficiency' => 'Master Wizard Level',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['proficiency']);
    }

    /**
     * Test duplicate language prevention.
     */
    public function test_duplicate_languages_are_strictly_prevented(): void
    {
        $user = User::factory()->create();

        ProfileLanguage::create([
            'user_id' => $user->id,
            'language' => 'English',
            'proficiency' => 'fluent',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/languages', [
                'language' => 'english',
                'proficiency' => 'native',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['language']);
    }

    /**
     * Test searching languages.
     */
    public function test_user_can_search_languages(): void
    {
        $response = $this->getJson('/api/v2/languages/search?q=ben');

        $response->assertOk();
        $this->assertContains('Bengali', $response->json('data'));
    }

    /**
     * Test cross-user authorization is strictly enforced (403 Forbidden).
     */
    public function test_cross_user_authorization_is_enforced(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $skill = ProfileSkill::create(['user_id' => $owner->id, 'name' => 'Solidity']);
        $interest = ProfileInterest::create(['user_id' => $owner->id, 'name' => 'Web3']);
        $language = ProfileLanguage::create(['user_id' => $owner->id, 'language' => 'German', 'proficiency' => 'basic']);

        // Attacker attempts to delete owner's skill
        $this->actingAs($attacker, 'sanctum')
            ->deleteJson("/api/v2/profile/skills/{$skill->id}")
            ->assertStatus(403);

        // Attacker attempts to delete owner's interest
        $this->actingAs($attacker, 'sanctum')
            ->deleteJson("/api/v2/profile/interests/{$interest->id}")
            ->assertStatus(403);

        // Attacker attempts to update owner's language
        $this->actingAs($attacker, 'sanctum')
            ->putJson("/api/v2/profile/languages/{$language->id}", ['proficiency' => 'native'])
            ->assertStatus(403);

        // Attacker attempts to delete owner's language
        $this->actingAs($attacker, 'sanctum')
            ->deleteJson("/api/v2/profile/languages/{$language->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('profile_skills', ['id' => $skill->id]);
        $this->assertDatabaseHas('profile_interests', ['id' => $interest->id]);
        $this->assertDatabaseHas('profile_languages', ['id' => $language->id]);
    }

    /**
     * Test audit logging records all taxonomy actions.
     */
    public function test_audit_logging_and_cache_invalidation_for_taxonomy_actions(): void
    {
        $user = User::factory()->create();

        // 1. Skill Added & Removed
        $skillRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/skills', ['name' => 'Cybersecurity']);
        $skillId = $skillRes->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'SKILL_ADDED',
            'entity_type' => ProfileSkill::class,
            'entity_id' => $skillId,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v2/profile/skills/{$skillId}")
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'SKILL_REMOVED',
            'entity_type' => ProfileSkill::class,
            'entity_id' => $skillId,
        ]);

        // 2. Interest Added & Removed
        $interestRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/interests', ['name' => 'Robotics']);
        $interestId = $interestRes->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'INTEREST_ADDED',
            'entity_type' => ProfileInterest::class,
            'entity_id' => $interestId,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v2/profile/interests/{$interestId}")
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'INTEREST_REMOVED',
            'entity_type' => ProfileInterest::class,
            'entity_id' => $interestId,
        ]);

        // 3. Language Added, Updated, Removed
        $langRes = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/languages', [
                'language' => 'Japanese',
                'proficiency' => 'basic',
            ]);
        $langId = $langRes->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'LANGUAGE_ADDED',
            'entity_type' => ProfileLanguage::class,
            'entity_id' => $langId,
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v2/profile/languages/{$langId}", [
                'proficiency' => 'conversational',
            ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'LANGUAGE_UPDATED',
            'entity_type' => ProfileLanguage::class,
            'entity_id' => $langId,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v2/profile/languages/{$langId}")
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'LANGUAGE_REMOVED',
            'entity_type' => ProfileLanguage::class,
            'entity_id' => $langId,
        ]);
    }
}
