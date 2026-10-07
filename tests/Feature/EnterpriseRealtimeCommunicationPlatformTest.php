<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Message;
use App\Models\User;
use App\Services\Calling\CallingService;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterpriseRealtimeCommunicationPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.default' => 'public']);
        Storage::fake('public');
    }

    public function test_user_can_initiate_audio_and_video_calls_and_broadcasts_incoming_event(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        $response = $this->actingAs($userA, 'sanctum')
            ->postJson('/api/v1/calls', [
                'conversation_id' => $conversation->id,
                'call_type' => 'video',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.conversation_id', $conversation->id)
            ->assertJsonPath('data.call_type', 'video')
            ->assertJsonPath('data.status', 'ringing');

        $callId = $response->json('data.id');
        $this->assertDatabaseHas('calls', [
            'id' => $callId,
            'conversation_id' => $conversation->id,
            'caller_id' => $userA->id,
            'call_type' => 'video',
            'status' => 'ringing',
        ]);

        $this->assertDatabaseHas('call_participants', [
            'call_id' => $callId,
            'user_id' => $userA->id,
            'role' => 'caller',
            'status' => 'accepted',
        ]);

        $this->assertDatabaseHas('call_participants', [
            'call_id' => $callId,
            'user_id' => $userB->id,
            'role' => 'callee',
            'status' => 'ringing',
        ]);

        // Assert sync event logged
        $this->assertDatabaseHas('messenger_sync_events', [
            'conversation_id' => $conversation->id,
            'event_type' => 'call.incoming',
        ]);
    }

    public function test_user_can_respond_to_call_accept_and_reject(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        $call = app(CallingService::class)->initiateCall($userA, $conversation, 'audio');

        // Callee accepts
        $acceptResponse = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/calls/{$call->id}/respond", [
                'action' => 'accept',
            ]);

        $acceptResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('call_participants', [
            'call_id' => $call->id,
            'user_id' => $userB->id,
            'status' => 'accepted',
        ]);
    }

    public function test_user_can_reject_call(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        $call = app(CallingService::class)->initiateCall($userA, $conversation, 'video');

        // Callee rejects
        $rejectResponse = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/calls/{$call->id}/respond", [
                'action' => 'reject',
            ]);

        $rejectResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'status' => 'rejected',
        ]);
    }

    public function test_participant_can_leave_call_and_calculates_duration(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        $call = app(CallingService::class)->initiateCall($userA, $conversation, 'audio');
        app(CallingService::class)->respondToCall($userB, $call->id, 'accept');

        // User A leaves call
        $leaveResponse = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/calls/{$call->id}/leave");

        $leaveResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ended');

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'status' => 'ended',
        ]);
    }

    public function test_user_can_transmit_webrtc_signaling(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        $call = app(CallingService::class)->initiateCall($userA, $conversation, 'video');

        // Send SDP offer
        $signalResponse = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/calls/{$call->id}/signal", [
                'signal_type' => 'offer',
                'payload' => [
                    'type' => 'offer',
                    'sdp' => 'v=0\r\no=alice 2890844526 2890842807 IN IP4 127.0.0.1\r\ns=demo',
                ],
                'target_user_id' => $userB->id,
            ]);

        $signalResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.signal_type', 'offer')
            ->assertJsonPath('data.sender_id', $userA->id);
    }

    public function test_user_can_update_call_state_mute_and_camera(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        $call = app(CallingService::class)->initiateCall($userA, $conversation, 'video');

        $response = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/calls/{$call->id}/state", [
                'is_muted' => true,
                'is_camera_off' => true,
                'is_screen_sharing' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_muted', true)
            ->assertJsonPath('data.is_camera_off', true);

        $this->assertDatabaseHas('call_participants', [
            'call_id' => $call->id,
            'user_id' => $userA->id,
            'is_muted' => true,
            'is_camera_off' => true,
        ]);
    }

    public function test_user_can_retrieve_call_history(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        app(CallingService::class)->initiateCall($userA, $conversation, 'audio');

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v1/calls/history');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_voice_message_upload_and_waveform_storage(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        $audioFile = UploadedFile::fake()->create('note.mp3', 250, 'audio/mp3');

        $response = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->id}/voice", [
                'audio' => $audioFile,
                'duration' => 45,
                'waveform' => [10, 25, 45, 80, 95, 60, 30, 15],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'voice')
            ->assertJsonPath('data.metadata.duration', 45);

        $messageId = $response->json('data.id');
        $this->assertDatabaseHas('messages', [
            'id' => $messageId,
            'conversation_id' => $conversation->id,
            'type' => 'voice',
        ]);
    }

    public function test_message_pinning_and_unpinning_in_conversation(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userA->id,
            'body' => 'Important meeting at 4 PM',
            'delivery_status' => 'sent',
            'sent_at' => now(),
        ]);

        // Pin message
        $pinResponse = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->id}/messages/{$message->id}/pin");

        $pinResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message_id', $message->id);

        $this->assertDatabaseHas('pinned_messages', [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'pinned_by_id' => $userA->id,
        ]);

        // List pinned messages
        $listResponse = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/v1/conversations/{$conversation->id}/pins");

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message.id', $message->id);

        // Unpin message
        $unpinResponse = $this->actingAs($userA, 'sanctum')
            ->deleteJson("/api/v1/conversations/{$conversation->id}/messages/{$message->id}/pin");

        $unpinResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('pinned_messages', [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ]);
    }

    public function test_user_can_save_and_unsave_messages(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userA->id,
            'body' => 'Save this critical link: https://example.com',
            'delivery_status' => 'sent',
            'sent_at' => now(),
        ]);

        // Save message
        $saveResponse = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/messages/{$message->id}/save");

        $saveResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message_id', $message->id);

        $this->assertDatabaseHas('saved_messages', [
            'user_id' => $userB->id,
            'message_id' => $message->id,
        ]);

        // List saved messages
        $listResponse = $this->actingAs($userB, 'sanctum')
            ->getJson('/api/v1/saved-messages');

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Unsave message
        $unsaveResponse = $this->actingAs($userB, 'sanctum')
            ->deleteJson("/api/v1/messages/{$message->id}/save");

        $unsaveResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('saved_messages', [
            'user_id' => $userB->id,
            'message_id' => $message->id,
        ]);
    }

    public function test_offline_event_synchronization_and_event_replay(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        // Generate events via messages
        $msg1 = $messengerService->sendMessage($userA, $conversation, ['body' => 'Message 1']);
        $msg2 = $messengerService->sendMessage($userB, $conversation, ['body' => 'Message 2']);

        $syncResponse = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v1/messenger/sync?since_id=0');

        $syncResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'events',
                    'latest_event_id',
                    'has_more',
                ],
            ]);

        $events = $syncResponse->json('data.events');
        $this->assertGreaterThanOrEqual(2, count($events));

        $latestId = $syncResponse->json('data.latest_event_id');

        // Querying with latest ID should return zero new events
        $emptySync = $this->actingAs($userA, 'sanctum')
            ->getJson("/api/v1/messenger/sync?since_id={$latestId}");

        $emptySync->assertStatus(200)
            ->assertJsonCount(0, 'data.events');
    }

    public function test_device_sessions_registration_and_revocation(): void
    {
        $user = User::factory()->create(['name' => 'Alice']);

        // Register device
        $regResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/devices/register', [
                'device_id' => 'mobile_android_abc123',
                'device_name' => 'Pixel 8 Pro',
                'platform' => 'android',
                'app_version' => '2.4.0',
                'push_token' => 'fcm_fake_token_for_push',
            ]);

        $regResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.device_id', 'mobile_android_abc123')
            ->assertJsonPath('data.platform', 'android');

        $deviceId = $regResponse->json('data.id');

        // List sessions
        $listResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/devices');

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Revoke session
        $revokeResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/devices/{$deviceId}");

        $revokeResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('device_sessions', [
            'id' => $deviceId,
            'is_revoked' => true,
        ]);
    }

    public function test_messenger_search_across_messages_media_and_files(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        $messengerService->sendMessage($userA, $conversation, ['body' => 'Secret project code is Antigravity']);
        $messengerService->sendMessage($userB, $conversation, ['body' => 'Regular lunch chat']);

        $searchResponse = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v1/messenger/search?q=Antigravity');

        $searchResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.body', 'Secret project code is Antigravity');
    }

    public function test_security_unauthorized_user_cannot_access_or_signal_call(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);
        $outsider = User::factory()->create(['name' => 'Eve']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        $call = app(CallingService::class)->initiateCall($userA, $conversation, 'video');

        // Eve tries to access call details
        $accessResponse = $this->actingAs($outsider, 'sanctum')
            ->getJson("/api/v1/calls/{$call->id}");

        $accessResponse->assertStatus(403);

        // Eve tries to send WebRTC signal to this call
        $signalResponse = $this->actingAs($outsider, 'sanctum')
            ->postJson("/api/v1/calls/{$call->id}/signal", [
                'signal_type' => 'offer',
                'payload' => ['test' => true],
            ]);

        $signalResponse->assertStatus(403);
    }

    public function test_user_can_retrieve_ice_servers_with_stun_and_ephemeral_turn_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/calls/ice-servers');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'ice_servers' => [
                        '*' => ['urls'],
                    ],
                ],
            ]);
    }

    public function test_client_can_check_app_version_compatibility(): void
    {
        $response = $this->getJson('/api/v1/app/version?platform=android&version=1.0.0');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.platform', 'android')
            ->assertJsonPath('data.client_version', '1.0.0')
            ->assertJsonStructure([
                'data' => [
                    'platform',
                    'client_version',
                    'min_supported_version',
                    'recommended_version',
                    'latest_version',
                    'is_supported',
                    'force_update',
                    'downloads' => [
                        'android',
                        'ios',
                        'macos',
                        'windows',
                        'linux',
                    ],
                ],
            ]);
    }

    public function test_call_state_machine_rejects_accepting_or_signaling_ended_call(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        $call = app(CallingService::class)->initiateCall($userA, $conversation, 'video');

        // Caller hangs up / leaves the call
        $this->actingAs($userA, 'sanctum')->postJson("/api/v1/calls/{$call->id}/leave")->assertStatus(200);

        // Callee attempts to accept an ended call
        $acceptResponse = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/calls/{$call->id}/respond", ['action' => 'accept']);

        $acceptResponse->assertStatus(422)
            ->assertJsonPath('success', false);

        // Callee attempts to signal on ended call
        $signalResponse = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/calls/{$call->id}/signal", [
                'signal_type' => 'candidate',
                'payload' => ['candidate' => 'test'],
            ]);

        $signalResponse->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_call_concurrency_duplicate_accept_handled_idempotently(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        $call = app(CallingService::class)->initiateCall($userA, $conversation, 'video');

        // First accept from Device 1
        $firstAccept = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/calls/{$call->id}/respond", ['action' => 'accept']);
        $firstAccept->assertStatus(200);

        // Simultaneous second accept from Device 2
        $secondAccept = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/v1/calls/{$call->id}/respond", ['action' => 'accept']);
        $secondAccept->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_message_idempotency_offline_outbox_retry_returns_same_message(): void
    {
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        $messengerService = app(MessengerServiceInterface::class);
        $conversation = $messengerService->getOrCreateDirectConversation($userA, $userB->id);

        $clientUuid = 'outbox-uuid-test-999';

        $firstSend = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->id}/messages", [
                'body' => 'Hello from offline outbox',
                'client_uuid' => $clientUuid,
            ]);

        $firstSend->assertStatus(201);
        $messageId1 = $firstSend->json('data.id');

        // Network retry sends same payload with identical client_uuid
        $retrySend = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/conversations/{$conversation->id}/messages", [
                'body' => 'Hello from offline outbox',
                'client_uuid' => $clientUuid,
            ]);

        $retrySend->assertStatus(201);
        $messageId2 = $retrySend->json('data.id');

        $this->assertEquals($messageId1, $messageId2);
        $this->assertEquals(1, Message::where('conversation_id', $conversation->id)->count());
    }
}
