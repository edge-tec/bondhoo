<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Friendship;
use App\Models\PrivacySetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseRealtimeCallingEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected User $userB;

    protected User $stranger;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->userA = User::factory()->create([
            'name' => 'তামিম ইকবাল',
            'username' => 'tamim',
        ]);

        $this->userB = User::factory()->create([
            'name' => 'মুশফিকুর রহিম',
            'username' => 'mushfiq',
        ]);

        $this->stranger = User::factory()->create([
            'name' => 'তৃতীয় ব্যক্তি',
            'username' => 'third_person',
        ]);

        // Established accepted friendship
        Friendship::create([
            'user_id' => $this->userA->id,
            'friend_id' => $this->userB->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $this->conversation = Conversation::create([
            'type' => Conversation::TYPE_DIRECT,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->userA->id,
            'role' => 'member',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->userB->id,
            'role' => 'member',
        ]);
    }

    public function test_user_a_can_initiate_audio_call_to_friend_user_b(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'audio',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.call_type', 'audio')
            ->assertJsonPath('data.status', 'ringing');

        $callId = $response->json('data.id');

        $this->assertDatabaseHas('calls', [
            'id' => $callId,
            'conversation_id' => $this->conversation->id,
            'caller_id' => $this->userA->id,
            'call_type' => 'audio',
            'status' => 'ringing',
        ]);
    }

    public function test_user_a_can_initiate_video_call_to_friend_user_b(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'video',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.call_type', 'video')
            ->assertJsonPath('data.status', 'ringing');
    }

    public function test_user_b_can_initiate_audio_and_video_call_to_user_a(): void
    {
        $response = $this->actingAs($this->userB, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'audio',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.caller.id', $this->userB->id);
    }

    public function test_callee_receives_ringing_state_and_can_accept_call(): void
    {
        $initResp = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'audio',
        ]);
        $callId = $initResp->json('data.id');

        // Callee accepts the call
        $acceptResp = $this->actingAs($this->userB, 'sanctum')
            ->postJson("/api/v1/calls/{$callId}/respond", [
                'action' => 'accept',
            ]);

        $acceptResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('calls', [
            'id' => $callId,
            'status' => 'active',
        ]);
    }

    public function test_callee_can_reject_call(): void
    {
        $initResp = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'video',
        ]);
        $callId = $initResp->json('data.id');

        $rejectResp = $this->actingAs($this->userB, 'sanctum')
            ->postJson("/api/v1/calls/{$callId}/respond", [
                'action' => 'reject',
            ]);

        $rejectResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('calls', [
            'id' => $callId,
            'status' => 'rejected',
        ]);
    }

    public function test_caller_blocking_callee_denies_call_initiation(): void
    {
        Friendship::where(function ($q) {
            $q->where('user_id', $this->userA->id)->where('friend_id', $this->userB->id);
        })->orWhere(function ($q) {
            $q->where('user_id', $this->userB->id)->where('friend_id', $this->userA->id);
        })->update(['status' => Friendship::STATUS_BLOCKED]);

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'audio',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Cannot initiate call due to privacy/block settings.');
    }

    public function test_callee_blocking_caller_denies_call_initiation_in_reverse(): void
    {
        Friendship::where(function ($q) {
            $q->where('user_id', $this->userA->id)->where('friend_id', $this->userB->id);
        })->orWhere(function ($q) {
            $q->where('user_id', $this->userB->id)->where('friend_id', $this->userA->id);
        })->update(['status' => Friendship::STATUS_BLOCKED]);

        $response = $this->actingAs($this->userB, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'audio',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Cannot initiate call due to privacy/block settings.');
    }

    public function test_unblocking_user_restores_call_initiation(): void
    {
        $friendship = Friendship::where(function ($q) {
            $q->where('user_id', $this->userA->id)->where('friend_id', $this->userB->id);
        })->first();

        // 1. Block
        $friendship->update(['status' => Friendship::STATUS_BLOCKED]);
        $blockedResp = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'audio',
        ]);
        $blockedResp->assertStatus(403);

        // 2. Unblock
        $friendship->update(['status' => Friendship::STATUS_ACCEPTED]);
        $unblockedResp = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'audio',
        ]);
        $unblockedResp->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    public function test_receiver_with_only_me_privacy_prevents_call(): void
    {
        PrivacySetting::updateOrCreate(
            ['user_id' => $this->userB->id],
            ['who_can_message_me' => PrivacySetting::LEVEL_ONLY_ME]
        );

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'audio',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Cannot initiate call due to privacy/block settings.');
    }

    public function test_receiver_with_friends_privacy_allows_friends_only(): void
    {
        PrivacySetting::updateOrCreate(
            ['user_id' => $this->userB->id],
            ['who_can_message_me' => PrivacySetting::LEVEL_FRIENDS]
        );

        // User A is a friend -> call allowed
        $respFriend = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'audio',
        ]);
        $respFriend->assertStatus(201);
    }

    public function test_duplicate_rapid_call_initiation_is_idempotent(): void
    {
        $firstCall = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'video',
        ]);
        $callId1 = $firstCall->json('data.id');

        // Immediate second call from same caller
        $secondCall = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'video',
        ]);
        $callId2 = $secondCall->json('data.id');

        $this->assertSame($callId1, $callId2);
        $this->assertSame(1, Call::where('conversation_id', $this->conversation->id)->count());
    }

    public function test_webrtc_signaling_flow_offer_answer_and_ice_candidates(): void
    {
        $init = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'video',
        ]);
        $callId = $init->json('data.id');

        // 1. Offer
        $offerResp = $this->actingAs($this->userA, 'sanctum')
            ->postJson("/api/v1/calls/{$callId}/signal", [
                'signal_type' => 'offer',
                'payload' => ['type' => 'offer', 'sdp' => 'v=0...offer-sdp'],
            ]);
        $offerResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.signal_type', 'offer');

        // 2. Answer
        $answerResp = $this->actingAs($this->userB, 'sanctum')
            ->postJson("/api/v1/calls/{$callId}/signal", [
                'signal_type' => 'answer',
                'payload' => ['type' => 'answer', 'sdp' => 'v=0...answer-sdp'],
            ]);
        $answerResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.signal_type', 'answer');

        // 3. ICE Candidate
        $iceResp = $this->actingAs($this->userA, 'sanctum')
            ->postJson("/api/v1/calls/{$callId}/signal", [
                'signal_type' => 'candidate',
                'payload' => ['candidate' => 'candidate:1 1 UDP 2130706431 192.168.1.1 50000 typ host'],
            ]);
        $iceResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.signal_type', 'candidate');
    }

    public function test_unauthorized_user_cannot_call_or_signal_foreign_call(): void
    {
        $init = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'audio',
        ]);
        $callId = $init->json('data.id');

        // Stranger attempts to signal
        $unauthResp = $this->actingAs($this->stranger, 'sanctum')
            ->postJson("/api/v1/calls/{$callId}/signal", [
                'signal_type' => 'candidate',
                'payload' => ['candidate' => 'bogus'],
            ]);
        $unauthResp->assertStatus(403);
    }

    public function test_leaving_or_ending_call_updates_duration_and_status(): void
    {
        $init = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'audio',
        ]);
        $callId = $init->json('data.id');

        // User A leaves/hangs up
        $leaveResp = $this->actingAs($this->userA, 'sanctum')
            ->postJson("/api/v1/calls/{$callId}/leave");

        $leaveResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ended');

        $this->assertDatabaseHas('calls', [
            'id' => $callId,
            'status' => 'ended',
        ]);
    }

    public function test_call_history_persists_and_returns_complete_records(): void
    {
        $init = $this->actingAs($this->userA, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'video',
        ]);
        $callId = $init->json('data.id');

        $historyResp = $this->actingAs($this->userA, 'sanctum')->getJson('/api/v1/calls/history');

        $historyResp->assertStatus(200)
            ->assertJsonPath('success', true);

        $calls = $historyResp->json('data');
        $this->assertNotEmpty($calls);
        $this->assertSame($callId, $calls[0]['id']);
    }
}
