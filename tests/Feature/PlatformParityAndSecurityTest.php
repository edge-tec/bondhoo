<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformParityAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_typing_endpoint_accepts_string_booleans_and_blocks_non_participants(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $intruder = User::factory()->create();

        $conversation = Conversation::create([
            'type' => 'direct',
            'creator_id' => $alice->id,
        ]);
        $conversation->participants()->create(['user_id' => $alice->id, 'role' => 'member']);
        $conversation->participants()->create(['user_id' => $bob->id, 'role' => 'member']);

        // Android sends "true" / "false" strings in Map<String, String>
        $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/presence/typing', [
                'conversation_id' => $conversation->id,
                'is_typing' => 'true',
            ])
            ->assertOk()
            ->assertJsonPath('data.is_typing', true);

        $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/presence/typing', [
                'conversation_id' => $conversation->id,
                'is_typing' => 'false',
            ])
            ->assertOk()
            ->assertJsonPath('data.is_typing', false);

        // IDOR check: non-participant must receive 403
        $this->actingAs($intruder, 'sanctum')
            ->postJson('/api/v1/presence/typing', [
                'conversation_id' => $conversation->id,
                'is_typing' => true,
            ])
            ->assertForbidden();
    }

    public function test_public_search_endpoint_is_not_shadowed_by_auth_middleware(): void
    {
        $this->getJson('/api/v1/search?q=bondhoo')
            ->assertOk();
    }

    public function test_enterprise_messenger_endpoints_enforce_participant_authorization(): void
    {
        $alice = User::factory()->create();
        $intruder = User::factory()->create();

        $conversation = Conversation::create([
            'type' => 'direct',
            'creator_id' => $alice->id,
        ]);
        $conversation->participants()->create(['user_id' => $alice->id, 'role' => 'owner']);

        $this->actingAs($intruder, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->id}/polls", [
                'question' => 'Unauthorized poll?',
                'options' => ['Yes', 'No'],
            ])
            ->assertForbidden();

        $this->actingAs($intruder, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->id}/theme", [
                'theme_color' => '#1877F2',
                'bubble_color' => '#1877F2',
            ])
            ->assertForbidden();

        $this->actingAs($intruder, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->id}/disappearing-timer", [
                'duration_seconds' => 3600,
            ])
            ->assertForbidden();
    }

    public function test_broadcasting_auth_rejects_unauthenticated_requests(): void
    {
        $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-user.1',
        ])->assertUnauthorized();
    }
}
