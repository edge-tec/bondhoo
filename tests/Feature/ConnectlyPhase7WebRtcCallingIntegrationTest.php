<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConnectlyPhase7WebRtcCallingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. 1-to-1 Audio call initiation and ringing state detection.
     */
    public function test_audio_call_initiation_and_active_incoming_call(): void
    {
        $caller = User::factory()->create(['name' => 'Tanvir Hasan']);
        $callee = User::factory()->create(['name' => 'Sadia Islam']);

        Sanctum::actingAs($caller);

        $initiateRes = $this->postJson('/api/v1/calls', [
            'receiver_id' => $callee->id,
            'call_type' => 'audio',
        ]);

        $initiateRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.call_type', 'audio')
            ->assertJsonPath('data.status', 'ringing')
            ->assertJsonPath('data.caller.id', $caller->id);

        $callId = $initiateRes->json('data.id');
        $this->assertDatabaseHas('calls', [
            'id' => $callId,
            'caller_id' => $caller->id,
            'call_type' => 'audio',
            'status' => 'ringing',
        ]);

        // Callee checks for active incoming call
        Sanctum::actingAs($callee);
        $activeRes = $this->getJson('/api/v1/calls/active');
        $activeRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $callId)
            ->assertJsonPath('data.status', 'ringing')
            ->assertJsonPath('data.caller.id', $caller->id);
    }

    /**
     * 2. 1-to-1 Video call initiation.
     */
    public function test_video_call_initiation(): void
    {
        $caller = User::factory()->create(['name' => 'Naimur Rahman']);
        $callee = User::factory()->create(['name' => 'Anika Tabassum']);

        Sanctum::actingAs($caller);

        $response = $this->postJson('/api/v1/calls', [
            'receiver_id' => $callee->id,
            'call_type' => 'video',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.call_type', 'video')
            ->assertJsonPath('data.status', 'ringing');

        $this->assertDatabaseHas('calls', [
            'id' => $response->json('data.id'),
            'call_type' => 'video',
            'status' => 'ringing',
        ]);
    }

    /**
     * 3. WebRTC SDP Offer dispatch and persistence.
     */
    public function test_webrtc_sdp_offer_signaling(): void
    {
        $caller = User::factory()->create();
        $callee = User::factory()->create();

        Sanctum::actingAs($caller);
        $callRes = $this->postJson('/api/v1/calls', [
            'receiver_id' => $callee->id,
            'call_type' => 'audio',
        ]);
        $callId = $callRes->json('data.id');

        $offerSdp = "v=0\r\no=- 123456 2 IN IP4 127.0.0.1\r\ns=-\r\nt=0 0\r\nm=audio 9 UDP/TLS/RTP/SAVPF 111";

        $signalRes = $this->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'offer',
            'payload' => [
                'type' => 'offer',
                'sdp' => $offerSdp,
            ],
            'target_user_id' => $callee->id,
        ]);

        $signalRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.signal_type', 'offer')
            ->assertJsonPath('data.payload.type', 'offer')
            ->assertJsonPath('data.target_user_id', $callee->id);

        // Callee reads signals backlog
        Sanctum::actingAs($callee);
        $signalsRes = $this->getJson("/api/v1/calls/{$callId}/signals");
        $signalsRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $signals = $signalsRes->json('data');
        $this->assertNotEmpty($signals);
        $this->assertEquals('offer', $signals[0]['signal_type']);
        $this->assertEquals($offerSdp, $signals[0]['payload']['sdp']);
    }

    /**
     * 4. Call response (accept) and WebRTC SDP Answer signaling.
     */
    public function test_call_acceptance_and_sdp_answer(): void
    {
        $caller = User::factory()->create();
        $callee = User::factory()->create();

        Sanctum::actingAs($caller);
        $callRes = $this->postJson('/api/v1/calls', [
            'receiver_id' => $callee->id,
            'call_type' => 'video',
        ]);
        $callId = $callRes->json('data.id');

        // Callee accepts the call
        Sanctum::actingAs($callee);
        $respondRes = $this->postJson("/api/v1/calls/{$callId}/respond", [
            'action' => 'accept',
        ]);

        $respondRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('calls', [
            'id' => $callId,
            'status' => 'active',
        ]);

        // Callee sends SDP answer
        $answerSdp = "v=0\r\no=- 654321 2 IN IP4 127.0.0.1\r\ns=-\r\nt=0 0\r\nm=audio 9 UDP/TLS/RTP/SAVPF 111";
        $answerRes = $this->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'answer',
            'payload' => [
                'type' => 'answer',
                'sdp' => $answerSdp,
            ],
            'target_user_id' => $caller->id,
        ]);

        $answerRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.signal_type', 'answer')
            ->assertJsonPath('data.payload.type', 'answer');
    }

    /**
     * 5. WebRTC ICE Candidate exchange between caller and callee.
     */
    public function test_ice_candidate_exchange(): void
    {
        $caller = User::factory()->create();
        $callee = User::factory()->create();

        Sanctum::actingAs($caller);
        $callRes = $this->postJson('/api/v1/calls', [
            'receiver_id' => $callee->id,
            'call_type' => 'audio',
        ]);
        $callId = $callRes->json('data.id');

        // Callee accepts
        Sanctum::actingAs($callee);
        $this->postJson("/api/v1/calls/{$callId}/respond", ['action' => 'accept']);

        // Caller sends ICE candidate
        Sanctum::actingAs($caller);
        $candidatePayload = [
            'candidate' => 'candidate:1 1 UDP 2130706431 192.168.1.50 50000 typ host',
            'sdpMid' => '0',
            'sdpMLineIndex' => '0',
        ];

        $candidateRes = $this->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'candidate',
            'payload' => $candidatePayload,
            'target_user_id' => $callee->id,
        ]);

        $candidateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.signal_type', 'candidate')
            ->assertJsonPath('data.payload.candidate', $candidatePayload['candidate']);

        // Callee retrieves candidate from signals backlog
        Sanctum::actingAs($callee);
        $signalsRes = $this->getJson("/api/v1/calls/{$callId}/signals");
        $signalsRes->assertStatus(200);

        $candidates = collect($signalsRes->json('data'))->where('signal_type', 'candidate');
        $this->assertNotEmpty($candidates);
        $this->assertEquals($candidatePayload['candidate'], $candidates->first()['payload']['candidate']);
    }

    /**
     * 6. Participant state updates (mute & camera off).
     */
    public function test_participant_state_updates(): void
    {
        $caller = User::factory()->create();
        $callee = User::factory()->create();

        Sanctum::actingAs($caller);
        $callRes = $this->postJson('/api/v1/calls', [
            'receiver_id' => $callee->id,
            'call_type' => 'video',
        ]);
        $callId = $callRes->json('data.id');

        // Caller mutes microphone and turns off camera
        $stateRes = $this->postJson("/api/v1/calls/{$callId}/state", [
            'is_muted' => true,
            'is_camera_off' => true,
        ]);

        $stateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_muted', true)
            ->assertJsonPath('data.is_camera_off', true);

        $this->assertDatabaseHas('call_participants', [
            'call_id' => $callId,
            'user_id' => $caller->id,
            'is_muted' => true,
            'is_camera_off' => true,
        ]);
    }

    /**
     * 7. Call rejection and busy status handling.
     */
    public function test_call_rejection_and_busy(): void
    {
        $caller = User::factory()->create();
        $callee = User::factory()->create();

        Sanctum::actingAs($caller);
        $callRes = $this->postJson('/api/v1/calls', [
            'receiver_id' => $callee->id,
            'call_type' => 'audio',
        ]);
        $callId = $callRes->json('data.id');

        // Callee rejects call
        Sanctum::actingAs($callee);
        $rejectRes = $this->postJson("/api/v1/calls/{$callId}/respond", [
            'action' => 'reject',
        ]);

        $rejectRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('calls', [
            'id' => $callId,
            'status' => 'rejected',
        ]);
    }

    /**
     * 8. Call termination (leave call), duration calculation and message recording.
     */
    public function test_call_leave_and_conversation_message_recording(): void
    {
        $caller = User::factory()->create();
        $callee = User::factory()->create();

        Sanctum::actingAs($caller);
        $callRes = $this->postJson('/api/v1/calls', [
            'receiver_id' => $callee->id,
            'call_type' => 'audio',
        ]);
        $callId = $callRes->json('data.id');
        $convId = $callRes->json('data.conversation_id');

        // Callee accepts
        Sanctum::actingAs($callee);
        $this->postJson("/api/v1/calls/{$callId}/respond", ['action' => 'accept']);

        // Caller leaves/ends call
        Sanctum::actingAs($caller);
        $leaveRes = $this->postJson("/api/v1/calls/{$callId}/leave");

        $leaveRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('calls', [
            'id' => $callId,
            'status' => 'ended',
        ]);

        // Verify call summary message is recorded in the conversation
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $convId,
            'type' => 'call',
        ]);
    }

    /**
     * 9. WebRTC STUN/TURN ICE servers configuration.
     */
    public function test_ice_servers_endpoint(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $res = $this->getJson('/api/v1/calls/ice-servers');

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'ice_servers' => [
                        '*' => ['urls'],
                    ],
                ],
            ]);

        $servers = $res->json('data.ice_servers');
        $this->assertNotEmpty($servers);

        // Verify Google STUN server exists in list
        $hasStun = false;
        foreach ($servers as $server) {
            foreach ($server['urls'] as $url) {
                if (str_contains($url, 'stun.l.google.com')) {
                    $hasStun = true;
                    break 2;
                }
            }
        }
        $this->assertTrue($hasStun, 'Google STUN server should be present in ICE servers list');
    }

    /**
     * 10. Call history pagination.
     */
    public function test_call_history_pagination(): void
    {
        $caller = User::factory()->create();
        $callee = User::factory()->create();

        Sanctum::actingAs($caller);
        $callRes = $this->postJson('/api/v1/calls', [
            'receiver_id' => $callee->id,
            'call_type' => 'audio',
        ]);
        $callId = $callRes->json('data.id');

        Sanctum::actingAs($callee);
        $this->postJson("/api/v1/calls/{$callId}/respond", ['action' => 'accept']);

        Sanctum::actingAs($caller);
        $this->postJson("/api/v1/calls/{$callId}/leave");

        // Fetch history
        $historyRes = $this->getJson('/api/v1/calls/history');

        $historyRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'call_type',
                        'status',
                        'caller',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'total',
                ],
            ]);

        $items = $historyRes->json('data');
        $this->assertNotEmpty($items);
        $this->assertEquals($callId, $items[0]['id']);
    }

    /**
     * 11. Cross-platform Web & Android interoperability verification.
     */
    public function test_web_and_android_interoperability_payload_structure(): void
    {
        $caller = User::factory()->create();
        $callee = User::factory()->create();

        Sanctum::actingAs($caller);
        $callRes = $this->postJson('/api/v1/calls', [
            'receiver_id' => $callee->id,
            'call_type' => 'video',
        ]);
        $callId = $callRes->json('data.id');

        // Test web-formatted SDP offer
        $webOfferPayload = [
            'type' => 'offer',
            'sdp' => "v=0\r\no=web-client 100 2 IN IP4 0.0.0.0\r\ns=-\r\nt=0 0\r\nm=video 9 UDP/TLS/RTP/SAVPF 96",
        ];
        $offerRes = $this->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'offer',
            'payload' => $webOfferPayload,
            'target_user_id' => $callee->id,
        ]);
        $offerRes->assertStatus(200);

        // Test android-formatted SDP answer
        Sanctum::actingAs($callee);
        $androidAnswerPayload = [
            'type' => 'answer',
            'sdp' => "v=0\r\no=android-client 200 2 IN IP4 0.0.0.0\r\ns=-\r\nt=0 0\r\nm=video 9 UDP/TLS/RTP/SAVPF 96",
        ];
        $answerRes = $this->postJson("/api/v1/calls/{$callId}/signal", [
            'signal_type' => 'answer',
            'payload' => $androidAnswerPayload,
            'target_user_id' => $caller->id,
        ]);
        $answerRes->assertStatus(200);

        // Signals can be read seamlessly by either client
        $signalsRes = $this->getJson("/api/v1/calls/{$callId}/signals");
        $signalsRes->assertStatus(200);

        $signalTypes = collect($signalsRes->json('data'))->pluck('signal_type')->all();
        $this->assertContains('offer', $signalTypes);
        $this->assertContains('answer', $signalTypes);
    }
}
