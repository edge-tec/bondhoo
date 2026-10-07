<?php

namespace Tests\Feature;

use App\Events\ProfileUpdatedEvent;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UsernameHistory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Step09UsernameSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Test user can successfully change their username.
     */
    public function test_user_can_successfully_change_username(): void
    {
        Event::fake([ProfileUpdatedEvent::class]);

        $user = User::factory()->create(['username' => 'original_user']);
        $user->profile()->create(['slug' => 'original_user']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/username', [
                'username' => 'awesome_coder',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.old_username', 'original_user')
            ->assertJsonPath('data.username', 'awesome_coder')
            ->assertJsonPath('data.profile_url', '/@awesome_coder')
            ->assertJsonPath('data.slug', 'awesome_coder');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'awesome_coder',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'slug' => 'awesome_coder',
        ]);

        $this->assertDatabaseHas('username_histories', [
            'user_id' => $user->id,
            'username' => 'original_user',
        ]);

        Event::assertDispatched(ProfileUpdatedEvent::class, function ($event) use ($user) {
            return $event->user->id === $user->id && in_array('username', $event->updatedSections, true);
        });
    }

    /**
     * Test case-insensitive uniqueness is strictly enforced.
     */
    public function test_case_insensitive_uniqueness_enforced(): void
    {
        User::factory()->create(['username' => 'johndoe']);
        $otherUser = User::factory()->create(['username' => 'otheruser']);

        // Attempting different casings of existing username
        $response1 = $this->actingAs($otherUser, 'sanctum')
            ->putJson('/api/v2/profile/username', [
                'username' => 'JohnDoe',
            ]);

        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['username']);

        $response2 = $this->actingAs($otherUser, 'sanctum')
            ->putJson('/api/v2/profile/username', [
                'username' => 'JOHNDOE',
            ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    /**
     * Test reserved system usernames are strictly rejected.
     */
    public function test_reserved_usernames_are_strictly_rejected(): void
    {
        $user = User::factory()->create(['username' => 'regularuser']);

        $reserved = ['admin', 'administrator', 'support', 'help', 'api', 'login', 'register', 'settings', 'profile', 'security'];

        foreach ($reserved as $name) {
            $response = $this->actingAs($user, 'sanctum')
                ->putJson('/api/v2/profile/username', [
                    'username' => $name,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['username']);
        }
    }

    /**
     * Test disallowed characters and length boundary constraints.
     */
    public function test_disallowed_characters_and_length_boundaries(): void
    {
        $user = User::factory()->create();

        // 1. Min length (< 3 chars)
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/username', ['username' => 'ab'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);

        // 2. Max length (> 30 chars)
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/username', ['username' => str_repeat('a', 31)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);

        // 3. Starting or ending with a dot or dash
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/username', ['username' => '.invalidname'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/username', ['username' => 'invalidname.'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);

        // 4. Consecutive dots
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/username', ['username' => 'john..doe'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);

        // 5. Special characters not allowed
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/username', ['username' => 'john@doe'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/username', ['username' => 'john doe'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    /**
     * Test username history is preserved across multiple changes.
     */
    public function test_username_history_is_preserved_on_multiple_changes(): void
    {
        $user = User::factory()->create(['username' => 'first_handle']);

        // 1st change
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/username', ['username' => 'second_handle'])
            ->assertOk();

        // 2nd change
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/username', ['username' => 'third_handle'])
            ->assertOk();

        $history = UsernameHistory::where('user_id', $user->id)
            ->pluck('username')
            ->toArray();

        $this->assertContains('first_handle', $history);
        $this->assertContains('second_handle', $history);
        $this->assertCount(2, $history);
    }

    /**
     * Test old profile web URL permanently redirects (301) to new profile URL.
     */
    public function test_old_profile_url_redirects_to_new_username(): void
    {
        $user = User::factory()->create(['username' => 'initial_brand']);
        $user->profile()->create(['slug' => 'initial_brand']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/username', ['username' => 'rebranded'])
            ->assertOk();

        // Visiting old profile handle /@initial_brand on web
        $response = $this->get('/@initial_brand');

        $response->assertStatus(301);
        $response->assertRedirect('/u/rebranded');
    }

    /**
     * Test API resolves old username from history with url_migration metadata.
     */
    public function test_api_resolves_old_username_from_history(): void
    {
        $user = User::factory()->create(['username' => 'old_api_handle']);
        $user->profile()->create(['slug' => 'old_api_handle']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/username', ['username' => 'new_api_handle'])
            ->assertOk();

        // Fetching by historical username
        $response = $this->getJson('/api/v2/profile/old_api_handle');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'new_api_handle')
            ->assertJsonPath('data.url_migration.redirected', true)
            ->assertJsonPath('data.url_migration.redirected_from', 'old_api_handle')
            ->assertJsonPath('data.url_migration.current_username', 'new_api_handle');
    }

    /**
     * Test both old and new username caches are cleared after update.
     */
    public function test_both_old_and_new_caches_are_invalidated(): void
    {
        $user = User::factory()->create(['username' => 'cached_old']);
        $oldUser = 'cached_old';
        $newUser = 'cached_new';

        Cache::put("profile:public:{$oldUser}", ['cached' => true], 3600);
        Cache::put("profile:about:{$oldUser}", ['cached' => true], 3600);
        Cache::put("profile:public:{$newUser}", ['cached' => true], 3600);
        Cache::put("profile:about:{$newUser}", ['cached' => true], 3600);

        $this->assertTrue(Cache::has("profile:public:{$oldUser}"));
        $this->assertTrue(Cache::has("profile:public:{$newUser}"));

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/username', ['username' => $newUser])
            ->assertOk();

        $this->assertFalse(Cache::has("profile:public:{$oldUser}"));
        $this->assertFalse(Cache::has("profile:about:{$oldUser}"));
        $this->assertFalse(Cache::has("profile:public:{$newUser}"));
        $this->assertFalse(Cache::has("profile:about:{$newUser}"));
    }

    /**
     * Test audit log is created upon username change.
     */
    public function test_audit_log_records_username_change(): void
    {
        $user = User::factory()->create(['username' => 'audit_target']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v2/profile/username', ['username' => 'audit_verified'])
            ->assertOk();

        $log = AuditLog::where('user_id', $user->id)
            ->where('action', 'USERNAME_CHANGED')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('audit_target', $log->old_values['username']);
        $this->assertEquals('/@audit_target', $log->old_values['profile_url']);
        $this->assertEquals('audit_verified', $log->new_values['username']);
        $this->assertEquals('/@audit_verified', $log->new_values['profile_url']);
    }

    /**
     * Test username availability check endpoint.
     */
    public function test_username_availability_check_endpoint(): void
    {
        User::factory()->create(['username' => 'existinguser']);

        // 1. Available username
        $responseAvailable = $this->getJson('/api/v2/profile/username/check?username=fresh_user');
        $responseAvailable->assertOk()
            ->assertJsonPath('data.is_available', true)
            ->assertJsonPath('data.username', 'fresh_user');

        // 2. Taken username
        $responseTaken = $this->getJson('/api/v2/profile/username/check?username=existinguser');
        $responseTaken->assertOk()
            ->assertJsonPath('data.is_available', false)
            ->assertJsonPath('data.reason', 'taken');

        // 3. Reserved username
        $responseReserved = $this->getJson('/api/v2/profile/username/check?username=admin');
        $responseReserved->assertOk()
            ->assertJsonPath('data.is_available', false)
            ->assertJsonPath('data.reason', 'reserved');
    }

    /**
     * Test rate limiting on username check endpoint to prevent enumeration abuse.
     */
    public function test_rate_limiter_prevents_username_enumeration(): void
    {
        // Send 20 allowed requests
        for ($i = 1; $i <= 20; $i++) {
            $response = $this->getJson("/api/v2/profile/username/check?username=testuser{$i}");
            $response->assertOk();
        }

        // 21st request should be rate-limited (HTTP 429)
        $blockedResponse = $this->getJson('/api/v2/profile/username/check?username=testuser21');
        $blockedResponse->assertStatus(429);
    }

    /**
     * Test v1 profile update username endpoint works as expected.
     */
    public function test_v1_profile_update_username_endpoint_works(): void
    {
        $user = User::factory()->create(['username' => 'v1_original']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/username', [
                'username' => 'v1_updated',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.old_username', 'v1_original')
            ->assertJsonPath('data.username', 'v1_updated');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'v1_updated',
        ]);
    }
}
