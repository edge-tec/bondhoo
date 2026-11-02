<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase8BlockedAndRestrictedUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_blocked_restricted_and_muted_users(): void
    {
        $actor = User::factory()->create();
        $blocked = User::factory()->create(['name' => 'Blocked Person']);
        $restricted = User::factory()->create(['name' => 'Restricted Friend']);
        $muted = User::factory()->create(['name' => 'Muted Friend']);

        // Set friendship statuses
        Friendship::create([
            'user_id' => $actor->id,
            'friend_id' => $blocked->id,
            'status' => Friendship::STATUS_BLOCKED,
        ]);

        Friendship::create([
            'user_id' => $actor->id,
            'friend_id' => $restricted->id,
            'status' => Friendship::STATUS_ACCEPTED,
            'is_restricted' => true,
        ]);

        Friendship::create([
            'user_id' => $actor->id,
            'friend_id' => $muted->id,
            'status' => Friendship::STATUS_ACCEPTED,
            'is_muted' => true,
        ]);

        Sanctum::actingAs($actor);

        $response = $this->getJson('/api/v1/settings/blocking');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.blocked_users')
            ->assertJsonPath('data.blocked_users.0.id', $blocked->id)
            ->assertJsonCount(1, 'data.restricted_users')
            ->assertJsonPath('data.restricted_users.0.id', $restricted->id)
            ->assertJsonCount(1, 'data.muted_users')
            ->assertJsonPath('data.muted_users.0.id', $muted->id);
    }

    public function test_can_unrestrict_and_unmute_user(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create(['name' => 'Special Target']);

        $friendship = Friendship::create([
            'user_id' => $actor->id,
            'friend_id' => $target->id,
            'status' => Friendship::STATUS_ACCEPTED,
            'is_restricted' => true,
            'is_muted' => true,
        ]);

        Sanctum::actingAs($actor);

        // Unrestrict
        $unrestrictRes = $this->postJson("/api/v1/settings/blocking/{$target->id}/unrestrict");
        $unrestrictRes->assertOk()->assertJsonPath('success', true);
        $this->assertFalse((bool) $friendship->fresh()->is_restricted);

        // Unmute
        $unmuteRes = $this->postJson("/api/v1/settings/blocking/{$target->id}/unmute");
        $unmuteRes->assertOk()->assertJsonPath('success', true);
        $this->assertFalse((bool) $friendship->fresh()->is_muted);
    }
}
