<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseCallingAndWebRtcIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. Test 1-on-1 Audio Call Initiation and Session Creation.
     */
    public function test_initiate_audio_call(): void
    {
        $caller = User::factory()->create(['name' => 'Mizanur Rahman']);
        $receiver = User::factory()->create(['name' => 'Sadia Islam']);

        $res = $this->actingAs($caller)->postJson('/api/v1/calls', [
            'receiver_id' => $receiver->id,
            'call_type' => 'audio',
        ]);

        $res->assertStatus(201);
        $callId = $res->json('data.id');
        $this->assertNotNull($callId);
        $this->assertEquals('audio', $res->json('data.call_type'));
    }

    /**
     * 2. Test 1-on-1 Video Call Initiation.
     */
    public function test_initiate_video_call(): void
    {
        $caller = User::factory()->create(['name' => 'Caller']);
        $receiver = User::factory()->create(['name' => 'Receiver']);

        $res = $this->actingAs($caller)->postJson('/api/v1/calls', [
            'receiver_id' => $receiver->id,
            'call_type' => 'video',
        ]);

        $res->assertStatus(201);
        $this->assertEquals('video', $res->json('data.call_type'));
    }

    /**
     * 3. Test STUN/TURN ICE Servers Retrieval.
     */
    public function test_get_ice_servers(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->getJson('/api/v1/calls/ice-servers');
        $res->assertStatus(200);
        $this->assertArrayHasKey('ice_servers', $res->json('data'));
    }

    /**
     * 4. Test WebRTC Signaling Exchange (Offer, Answer, ICE Candidate).
     */
    public function test_webrtc_signaling_exchange(): void
    {
        $caller = User::factory()->create();
        $receiver = User::factory()->create();

        $callRes = $this->actingAs($caller)->postJson('/api/v1/calls', [
            'receiver_id' => $receiver->id,
            'call_type' => 'video',
        ]);
        $callId = $callRes->json('data.id');

        // Caller transmits SDP Offer
        $offerRes = $this->actingAs($caller)->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'offer',
            'payload' => ['sdp' => 'v=0...fake-sdp-offer...', 'type' => 'offer'],
            'target_user_id' => $receiver->id,
        ]);
        $offerRes->assertStatus(200);

        // Receiver responds with SDP Answer
        $answerRes = $this->actingAs($receiver)->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'answer',
            'payload' => ['sdp' => 'v=0...fake-sdp-answer...', 'type' => 'answer'],
            'target_user_id' => $caller->id,
        ]);
        $answerRes->assertStatus(200);

        // ICE Candidate exchange
        $iceRes = $this->actingAs($caller)->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'candidate',
            'payload' => ['candidate' => 'candidate:1 1 UDP 2130706431...', 'sdpMid' => '0', 'sdpMLineIndex' => 0],
            'target_user_id' => $receiver->id,
        ]);
        $iceRes->assertStatus(200);
    }

    /**
     * 5. Test Call Acceptance, State Update (Mute/Camera), and End Call.
     */
    public function test_call_lifecycle_accept_state_update_and_leave(): void
    {
        $caller = User::factory()->create();
        $receiver = User::factory()->create();

        $callRes = $this->actingAs($caller)->postJson('/api/v1/calls', [
            'receiver_id' => $receiver->id,
            'call_type' => 'audio',
        ]);
        $callId = $callRes->json('data.id');

        // Receiver accepts call
        $acceptRes = $this->actingAs($receiver)->postJson("/api/v1/calls/{$callId}/respond", [
            'action' => 'accept',
        ]);
        $acceptRes->assertStatus(200);

        // Participant toggles mute
        $stateRes = $this->actingAs($receiver)->postJson("/api/v1/calls/{$callId}/state", [
            'is_muted' => true,
            'is_camera_off' => false,
        ]);
        $stateRes->assertStatus(200);

        // Caller leaves call
        $leaveRes = $this->actingAs($caller)->postJson("/api/v1/calls/{$callId}/leave");
        $leaveRes->assertStatus(200);
    }

    /**
     * 6. Test Call History Retrieval.
     */
    public function test_call_history_listing(): void
    {
        $caller = User::factory()->create();
        $receiver = User::factory()->create();

        // Initiate and end a call
        $callRes = $this->actingAs($caller)->postJson('/api/v1/calls', [
            'receiver_id' => $receiver->id,
            'call_type' => 'audio',
        ]);
        $callId = $callRes->json('data.id');
        $this->actingAs($caller)->postJson("/api/v1/calls/{$callId}/leave");

        // Fetch history
        $historyRes = $this->actingAs($caller)->getJson('/api/v1/calls/history');
        $historyRes->assertStatus(200);
        $this->assertNotEmpty($historyRes->json('data'));
    }
}
