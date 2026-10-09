<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\CallParticipant;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebRTCCallingWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;

    protected User $user2;

    protected User $stranger;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->user1 = User::factory()->create([
            'name' => 'মমিনুল হক',
            'username' => 'mominul',
        ]);

        $this->user2 = User::factory()->create([
            'name' => 'সাকিব আল হাসান',
            'username' => 'sakib',
        ]);

        $this->stranger = User::factory()->create([
            'name' => 'অপরিচিত ইউজার',
            'username' => 'stranger',
        ]);

        $this->conversation = Conversation::create([
            'type' => Conversation::TYPE_DIRECT,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user1->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user2->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_call_route(): void
    {
        $response = $this->get("/call/{$this->conversation->id}?type=audio");
        $response->assertRedirect('/login');
    }

    public function test_unauthorized_user_cannot_access_call_room(): void
    {
        $response = $this->actingAs($this->stranger)->get("/call/{$this->conversation->id}?type=audio");
        $response->assertStatus(403);
    }

    public function test_non_existent_conversation_returns_404(): void
    {
        $response = $this->actingAs($this->user1)->get('/call/99999?type=audio');
        $response->assertStatus(404);
    }

    public function test_participant_can_open_audio_call_room_successfully(): void
    {
        $response = $this->actingAs($this->user1)->get("/call/{$this->conversation->id}?type=audio");

        $response->assertStatus(200);
        $response->assertSee('সাকিব আল হাসান');
        $response->assertSee('অডিও কল');
        $response->assertSee('btnToggleMic', false);
        $response->assertSee('btnToggleCam', false);
        $response->assertSee('audioVisualizer', false);
    }

    public function test_participant_can_open_video_call_room_successfully(): void
    {
        $response = $this->actingAs($this->user2)->get("/call/{$this->conversation->id}?type=video");

        $response->assertStatus(200);
        $response->assertSee('মমিনুল হক');
        $response->assertSee('ভিডিও কল');
        $response->assertSee('remoteVideo', false);
        $response->assertSee('localVideo', false);
    }

    public function test_participant_can_send_call_signals_via_conversation_api(): void
    {
        $response = $this->actingAs($this->user1)->postJson("/api/v1/conversations/{$this->conversation->id}/call/signal", [
            'signal_type' => 'offer',
            'call_type' => 'video',
            'payload' => [
                'type' => 'offer',
                'sdp' => 'v=0\r\no=mock 12345 2 IN IP4 127.0.0.1\r\ns=-\r\n',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.signal_type', 'offer');
    }

    public function test_callee_can_open_video_call_room_with_answer_parameter(): void
    {
        $call = Call::create([
            'conversation_id' => $this->conversation->id,
            'caller_id' => $this->user1->id,
            'call_type' => 'video',
            'status' => Call::STATUS_RINGING,
            'started_at' => now(),
        ]);

        CallParticipant::create([
            'call_id' => $call->id,
            'user_id' => $this->user2->id,
            'role' => 'callee',
            'status' => 'ringing',
        ]);

        $response = $this->actingAs($this->user2)->get("/call/{$this->conversation->id}?type=video&answer=1&call_id={$call->id}");

        $response->assertStatus(200);
        $response->assertSee('মমিনুল হক');
        $response->assertSee('ভিডিও কল');
        $response->assertSee('remoteVideo', false);
        $response->assertSee('localVideo', false);
    }

    public function test_accessing_ended_call_redirects_to_messages_without_reviving_call_room(): void
    {
        $endedCall = Call::create([
            'conversation_id' => $this->conversation->id,
            'caller_id' => $this->user1->id,
            'call_type' => 'video',
            'status' => Call::STATUS_ENDED,
            'started_at' => now()->subMinutes(5),
            'ended_at' => now()->subMinutes(3),
            'duration_seconds' => 120,
        ]);

        $response = $this->actingAs($this->user2)->get("/call/{$this->conversation->id}?type=video&call_id={$endedCall->id}");

        $response->assertRedirect("/messages/{$this->conversation->id}");
        $response->assertSessionHas('info', 'কলটি ইতোমধ্যে সমাপ্ত হয়েছে।');
    }

    public function test_callee_attempting_to_answer_ended_call_is_redirected_to_messages(): void
    {
        $endedCall = Call::create([
            'conversation_id' => $this->conversation->id,
            'caller_id' => $this->user1->id,
            'call_type' => 'video',
            'status' => Call::STATUS_REJECTED,
            'started_at' => now()->subMinute(),
            'ended_at' => now(),
            'duration_seconds' => 0,
        ]);

        $response = $this->actingAs($this->user2)->get("/call/{$this->conversation->id}?type=video&answer=1&call_id={$endedCall->id}");

        $response->assertRedirect("/messages/{$this->conversation->id}");
        $response->assertSessionHas('info');
    }

    public function test_call_room_includes_no_cache_headers_and_floating_pip_controls(): void
    {
        $response = $this->actingAs($this->user1)->get("/call/{$this->conversation->id}?type=video");

        $response->assertStatus(200);
        $response->assertHeader('Cache-Control');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));

        // Draggable PIP elements
        $response->assertSee('localVideoContainer', false);
        $response->assertSee('btnResetPip', false);
        $response->assertSee('btnFlipCam', false);
        $response->assertSee('networkQualityBadge', false);
        $response->assertSee('btnFullscreen', false);
    }

    public function test_cancelled_ringing_call_has_zero_duration(): void
    {
        $call = Call::create([
            'conversation_id' => $this->conversation->id,
            'caller_id' => $this->user1->id,
            'call_type' => 'audio',
            'status' => Call::STATUS_RINGING,
            'started_at' => now(),
        ]);

        CallParticipant::create([
            'call_id' => $call->id,
            'user_id' => $this->user1->id,
            'role' => 'caller',
            'status' => 'accepted',
        ]);

        CallParticipant::create([
            'call_id' => $call->id,
            'user_id' => $this->user2->id,
            'role' => 'callee',
            'status' => 'ringing',
        ]);

        // Caller leaves before callee answers
        $leaveResp = $this->actingAs($this->user1, 'sanctum')->postJson("/api/v1/calls/{$call->id}/leave");
        $leaveResp->assertStatus(200);

        $call->refresh();
        $this->assertSame(0, $call->duration_seconds);
        $this->assertSame(Call::STATUS_ENDED, $call->status);

        // Callee status updated to missed
        $calleeParticipant = CallParticipant::where('call_id', $call->id)->where('user_id', $this->user2->id)->first();
        $this->assertSame('missed', $calleeParticipant->status);

        // Call response array includes outcome
        $respArray = $call->toResponseArray($this->user1);
        $this->assertSame('missed', $respArray['outcome']);
        $this->assertSame(0, $respArray['duration_seconds']);
    }

    public function test_call_initiation_delivers_incoming_event_to_recipient_sync_stream(): void
    {
        // User1 initiates a call to User2
        $response = $this->actingAs($this->user1, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'receiver_id' => $this->user2->id,
            'call_type' => 'video',
        ]);

        $response->assertStatus(201);
        $callId = $response->json('data.id');
        $this->assertNotNull($callId);

        // User2 polls /api/v1/messenger/sync and receives the call.incoming event
        $syncResp = $this->actingAs($this->user2, 'sanctum')->getJson('/api/v1/messenger/sync?since_id=0');
        $syncResp->assertStatus(200);

        $events = collect($syncResp->json('data.events'));
        $incomingEvent = $events->firstWhere('event_type', 'call.incoming');

        $this->assertNotNull($incomingEvent, 'Receiver did not receive call.incoming sync event');
        $this->assertSame($callId, $incomingEvent['payload']['call_id']);
        $this->assertSame($this->user1->id, $incomingEvent['payload']['caller']['id']);
        $this->assertSame('video', $incomingEvent['payload']['call_type']);
    }

    public function test_duplicate_leave_requests_preserve_duration_and_create_single_message(): void
    {
        // Setup an active call that has been running for 45 seconds
        $startedAt = now()->subSeconds(45);
        $call = Call::create([
            'conversation_id' => $this->conversation->id,
            'caller_id' => $this->user1->id,
            'call_type' => 'video',
            'status' => Call::STATUS_ACTIVE,
            'started_at' => $startedAt,
        ]);

        CallParticipant::create([
            'call_id' => $call->id,
            'user_id' => $this->user1->id,
            'role' => 'caller',
            'status' => 'accepted',
            'joined_at' => $startedAt,
        ]);

        CallParticipant::create([
            'call_id' => $call->id,
            'user_id' => $this->user2->id,
            'role' => 'callee',
            'status' => 'accepted',
            'joined_at' => $startedAt,
        ]);

        // 1. Caller leaves
        $leaveResp1 = $this->actingAs($this->user1, 'sanctum')->postJson("/api/v1/calls/{$call->id}/leave");
        $leaveResp1->assertStatus(200);

        $call->refresh();
        $this->assertSame(Call::STATUS_ENDED, $call->status);
        $firstDuration = $call->duration_seconds;
        $this->assertGreaterThanOrEqual(45, $firstDuration);

        // Exactly 1 message created
        $callMessages = Message::where('conversation_id', $this->conversation->id)
            ->where('type', 'call')
            ->get();
        $this->assertCount(1, $callMessages);
        $this->assertStringContainsString('ভিডিও কল সম্পন্ন', $callMessages->first()->body);

        // 2. Callee leaves afterwards (simultaneous or delayed hangup)
        $leaveResp2 = $this->actingAs($this->user2, 'sanctum')->postJson("/api/v1/calls/{$call->id}/leave");
        $leaveResp2->assertStatus(200);

        // 3. Caller sends duplicate beacon / pagehide request
        $leaveResp3 = $this->actingAs($this->user1, 'sanctum')->postJson("/api/v1/calls/{$call->id}/leave");
        $leaveResp3->assertStatus(200);

        $call->refresh();
        // Duration must be preserved, NOT reset to 0
        $this->assertSame($firstDuration, $call->duration_seconds);
        $this->assertSame(Call::STATUS_ENDED, $call->status);

        // Messages must NOT be duplicated
        $callMessagesAfter = Message::where('conversation_id', $this->conversation->id)
            ->where('type', 'call')
            ->get();
        $this->assertCount(1, $callMessagesAfter);
    }

    public function test_callee_reject_call_produces_zero_duration_and_single_summary_message(): void
    {
        $call = Call::create([
            'conversation_id' => $this->conversation->id,
            'caller_id' => $this->user1->id,
            'call_type' => 'audio',
            'status' => Call::STATUS_RINGING,
            'started_at' => now(),
        ]);

        CallParticipant::create([
            'call_id' => $call->id,
            'user_id' => $this->user1->id,
            'role' => 'caller',
            'status' => 'accepted',
        ]);

        CallParticipant::create([
            'call_id' => $call->id,
            'user_id' => $this->user2->id,
            'role' => 'callee',
            'status' => 'ringing',
        ]);

        // Callee rejects the call
        $resp = $this->actingAs($this->user2, 'sanctum')->postJson("/api/v1/calls/{$call->id}/respond", [
            'action' => 'reject',
        ]);
        $resp->assertStatus(200);

        $call->refresh();
        $this->assertSame(Call::STATUS_REJECTED, $call->status);
        $this->assertSame(0, $call->duration_seconds);

        // Exactly one message recorded
        $messages = Message::where('conversation_id', $this->conversation->id)
            ->where('type', 'call')
            ->get();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('প্রত্যাখ্যাত', $messages->first()->body);
    }

    public function test_reusing_ringing_call_rebroadcasts_incoming_event_to_recipient(): void
    {
        // First call initiation creates the ringing call
        $resp1 = $this->actingAs($this->user1, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'receiver_id' => $this->user2->id,
            'call_type' => 'video',
        ]);
        $resp1->assertStatus(201);
        $callId1 = $resp1->json('data.id');

        // Second call initiation returns the existing ringing call session
        $resp2 = $this->actingAs($this->user1, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'receiver_id' => $this->user2->id,
            'call_type' => 'video',
        ]);
        $resp2->assertStatus(201);
        $this->assertSame($callId1, $resp2->json('data.id'));

        // Recipient sync stream contains call.incoming event
        $syncResp = $this->actingAs($this->user2, 'sanctum')->getJson('/api/v1/messenger/sync?since_id=0');
        $syncResp->assertStatus(200);
        $events = collect($syncResp->json('data.events'));
        $incomingEvents = $events->where('event_type', 'call.incoming');
        $this->assertNotEmpty($incomingEvents);
    }
}
