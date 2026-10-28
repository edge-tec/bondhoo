<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Friendship;
use App\Models\User;
use App\Services\Calling\CallingService;
use App\Services\Calling\LiveKitService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupCallingLiveKitAndTurnTest extends TestCase
{
    use RefreshDatabase;

    protected User $caller;

    protected User $friend1;

    protected User $friend2;

    protected User $outsider;

    protected Conversation $groupConversation;

    protected LiveKitService $liveKitService;

    protected CallingService $callingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->caller = User::factory()->create([
            'name' => 'তামিম ইকবাল',
            'username' => 'tamim_iqbal',
        ]);

        $this->friend1 = User::factory()->create([
            'name' => 'সাকিব আল হাসান',
            'username' => 'shakib75',
        ]);

        $this->friend2 = User::factory()->create([
            'name' => 'মুশফিকুর রহিম',
            'username' => 'mushfiq15',
        ]);

        $this->outsider = User::factory()->create([
            'name' => 'অনাহূত ব্যক্তি',
            'username' => 'outsider_user',
        ]);

        // Friendships
        Friendship::create([
            'user_id' => $this->caller->id,
            'friend_id' => $this->friend1->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);
        Friendship::create([
            'user_id' => $this->caller->id,
            'friend_id' => $this->friend2->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // Group Conversation
        $this->groupConversation = Conversation::create([
            'type' => Conversation::TYPE_GROUP,
            'title' => 'টাইগার্স গ্রুপ চ্যাট',
            'creator_id' => $this->caller->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->groupConversation->id,
            'user_id' => $this->caller->id,
            'role' => 'admin',
            'request_status' => 'accepted',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->groupConversation->id,
            'user_id' => $this->friend1->id,
            'role' => 'member',
            'request_status' => 'accepted',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->groupConversation->id,
            'user_id' => $this->friend2->id,
            'role' => 'member',
            'request_status' => 'accepted',
        ]);

        $this->liveKitService = app(LiveKitService::class);
        $this->callingService = app(CallingService::class);
    }

    public function test_livekit_service_generates_and_verifies_cryptographic_jwt(): void
    {
        $roomName = 'room_livekit_test_123';
        $token = $this->liveKitService->generateToken($this->caller, $roomName, [
            'canPublish' => true,
            'canSubscribe' => true,
        ]);

        $this->assertNotEmpty($token);
        $parts = explode('.', $token);
        $this->assertCount(3, $parts, 'LiveKit token must be a valid 3-part JWT');

        $decoded = $this->liveKitService->verifyToken($token);
        $this->assertNotNull($decoded, 'LiveKit token must successfully verify');
        $this->assertEquals((string) $this->caller->id, $decoded['sub']);
        $this->assertEquals($roomName, $decoded['video']['room']);
        $this->assertTrue($decoded['video']['roomJoin']);
        $this->assertTrue($decoded['video']['canPublish']);
        $this->assertTrue($decoded['video']['canSubscribe']);

        // Tampered token test
        $tamperedToken = $parts[0].'.'.$parts[1].'.tampered_signature_xyz';
        $this->assertNull($this->liveKitService->verifyToken($tamperedToken), 'Tampered token must fail signature check');
    }

    public function test_turn_ice_servers_returns_ephemeral_credentials(): void
    {
        $iceServers = $this->callingService->getIceServers($this->caller);
        $this->assertIsArray($iceServers);
        $this->assertNotEmpty($iceServers);

        // Assert presence of STUN URLs
        $stunFound = false;
        foreach ($iceServers as $server) {
            foreach ($server['urls'] as $url) {
                if (str_starts_with($url, 'stun:')) {
                    $stunFound = true;
                    break 2;
                }
            }
        }
        $this->assertTrue($stunFound, 'ICE servers must contain STUN servers');
    }

    public function test_group_call_initiation_configures_livekit_sfu_and_token(): void
    {
        $response = $this->actingAs($this->caller, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->groupConversation->id,
            'call_type' => 'group_video',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'call_type',
                    'room_id',
                    'status',
                    'participants',
                    'metadata',
                ],
            ]);

        $callId = $response->json('data.id');
        $call = Call::findOrFail($callId);

        $this->assertTrue($call->isGroup());
        $this->assertEquals(Call::TYPE_GROUP_VIDEO, $call->call_type);
        $this->assertEquals('livekit', $call->metadata['sfu_provider'] ?? null);
        $this->assertNotEmpty($call->metadata['livekit_token'] ?? null);

        // LiveKit token in metadata must be verifiable
        $verified = $this->liveKitService->verifyToken($call->metadata['livekit_token']);
        $this->assertNotNull($verified);
        $this->assertEquals((string) $this->caller->id, $verified['sub']);
        $this->assertEquals($call->room_id, $verified['video']['room']);
    }

    public function test_participant_can_fetch_livekit_token_for_group_call(): void
    {
        $call = $this->callingService->initiateCall(
            caller: $this->caller,
            conversation: $this->groupConversation,
            callType: Call::TYPE_GROUP_VIDEO
        );

        // Friend1 fetches token
        $response = $this->actingAs($this->friend1, 'sanctum')->getJson("/api/v1/calls/{$call->id}/token");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.call_id', $call->id)
            ->assertJsonPath('data.room_id', $call->room_id)
            ->assertJsonPath('data.sfu_provider', 'livekit')
            ->assertJsonStructure([
                'data' => [
                    'call_id',
                    'room_id',
                    'sfu_provider',
                    'server_url',
                    'token',
                    'ice_servers',
                ],
            ]);

        $friendToken = $response->json('data.token');
        $verifiedFriend = $this->liveKitService->verifyToken($friendToken);
        $this->assertNotNull($verifiedFriend);
        $this->assertEquals((string) $this->friend1->id, $verifiedFriend['sub']);
        $this->assertEquals($call->room_id, $verifiedFriend['video']['room']);
    }

    public function test_outsider_cannot_fetch_livekit_token_for_group_call(): void
    {
        $call = $this->callingService->initiateCall(
            caller: $this->caller,
            conversation: $this->groupConversation,
            callType: Call::TYPE_GROUP_VIDEO
        );

        // Outsider attempts to fetch token
        $response = $this->actingAs($this->outsider, 'sanctum')->getJson("/api/v1/calls/{$call->id}/token");

        $response->assertStatus(403);
    }

    public function test_ice_servers_endpoint_returns_success(): void
    {
        $response = $this->actingAs($this->caller, 'sanctum')->getJson('/api/v1/calls/ice-servers');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'ice_servers',
                ],
            ]);
    }
}
