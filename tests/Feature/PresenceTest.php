<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_send_heartbeat(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/presence/heartbeat');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'online' => true,
                ],
            ]);

        // Check via show endpoint
        $check = $this->getJson("/api/v1/presence/{$user->id}");
        $check->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user_id' => $user->id,
                    'online' => true,
                ],
            ]);
    }

    public function test_authenticated_user_can_set_typing_status(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/presence/typing', [
                'conversation_id' => 42,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'conversation_id' => 42,
                    'user_id' => $user->id,
                    'is_typing' => true,
                ],
            ]);
    }
}
