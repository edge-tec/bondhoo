<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase15DatabaseConcurrencyAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_poll_voting_option_id_and_option_ids_compatibility_cross_poll_injection_and_owner_close(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $conv = Conversation::create(['type' => 'group', 'title' => 'Phase 15 Group', 'creator_id' => $owner->id]);
        $conv->participants()->create(['user_id' => $owner->id, 'role' => 'owner']);
        $conv->participants()->create(['user_id' => $member->id, 'role' => 'member']);

        // Member creates a single-choice poll
        $poll1Res = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/polls", [
                'question' => 'Single choice poll?',
                'options' => ['Opt A', 'Opt B'],
                'is_multiple_choice' => false,
            ])
            ->assertCreated();
        $poll1Id = $poll1Res->json('data.poll.id') ?? $poll1Res->json('data.id');
        $poll1Opts = $poll1Res->json('data.poll.options') ?? $poll1Res->json('data.options');
        $optA = $poll1Opts[0]['id'];
        $optB = $poll1Opts[1]['id'];

        // Second poll to test cross-poll option injection
        $poll2Res = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/polls", [
                'question' => 'Multi choice poll?',
                'options' => ['Opt X', 'Opt Y'],
                'is_multiple_choice' => true,
            ])
            ->assertCreated();
        $poll2Id = $poll2Res->json('data.poll.id') ?? $poll2Res->json('data.id');
        $poll2Opts = $poll2Res->json('data.poll.options') ?? $poll2Res->json('data.options');
        $optX = $poll2Opts[0]['id'];
        $optY = $poll2Opts[1]['id'];

        // 1. Vote using single `option_id` format on Poll 1
        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/polls/{$poll1Id}/vote", ['option_id' => $optA])
            ->assertOk();

        // 2. Duplicate option IDs `[optB, optB]` on single-choice poll are deduplicated cleanly
        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/polls/{$poll1Id}/vote", ['option_ids' => [$optB, $optB]])
            ->assertOk();

        // 3. Multiple distinct options on single-choice poll must fail with 422
        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/polls/{$poll1Id}/vote", ['option_ids' => [$optA, $optB]])
            ->assertStatus(422);

        // 4. Cross-poll option injection (sending Poll 2's optX to Poll 1 or Poll 1's optA to Poll 2) must fail with 422 and roll back
        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/polls/{$poll2Id}/vote", ['option_ids' => [$optX, $optA]])
            ->assertStatus(422);

        $this->assertDatabaseMissing('chat_poll_votes', [
            'poll_id' => $poll2Id,
            'user_id' => $owner->id,
        ]);

        // 5. Valid multi-choice vote using `option_ids` on Poll 2
        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/polls/{$poll2Id}/vote", ['option_ids' => [$optX, $optY]])
            ->assertOk();

        $this->assertDatabaseCount('chat_poll_votes', 3); // 1 on poll1 + 2 on poll2

        // 6. Group owner can close a poll created by another member
        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/polls/{$poll1Id}/close")
            ->assertOk();

        // 7. Voting on a closed poll must fail with 422
        $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/polls/{$poll1Id}/vote", ['option_id' => $optA])
            ->assertStatus(422);
    }

    public function test_duplicate_message_idempotency_and_friend_request_race_protection(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $conv = Conversation::create(['type' => 'direct', 'creator_id' => $alice->id]);
        $conv->participants()->create(['user_id' => $alice->id, 'role' => 'member']);
        $conv->participants()->create(['user_id' => $bob->id, 'role' => 'member']);

        // 1. Duplicate message retry with identical client_message_id must not create duplicate DB rows
        $clientUuid = 'msg-idempotent-uuid-15001';
        $res1 = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'Idempotent message test',
                'type' => 'text',
                'client_message_id' => $clientUuid,
            ])
            ->assertCreated();

        $res2 = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'Idempotent message test',
                'type' => 'text',
                'client_message_id' => $clientUuid,
            ]);
        $this->assertContains($res2->getStatusCode(), [200, 201]);

        $id1 = $res1->json('data.message.id') ?? $res1->json('data.id');
        $id2 = $res2->json('data.message.id') ?? $res2->json('data.id');
        $this->assertEquals($id1, $id2);
        $this->assertEquals(1, Message::where('conversation_id', $conv->id)->count());

        // 2. Duplicate friend request protection
        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request")
            ->assertCreated();

        $dupRes = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/friends/{$bob->id}/request");
        $this->assertContains($dupRes->getStatusCode(), [200, 400, 409, 422]);
    }
}
