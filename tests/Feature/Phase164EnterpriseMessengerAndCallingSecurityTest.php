<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Friendship;
use App\Models\Media;
use App\Models\Message;
use App\Models\User;
use App\Services\Calling\CallingService;
use App\Services\Calling\LiveKitService;
use App\Services\Contracts\MessengerServiceInterface;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase164EnterpriseMessengerAndCallingSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;

    protected User $user2;

    protected User $outsider;

    protected Conversation $directConversation;

    protected Conversation $groupConversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test_broadcasting_key',
            'broadcasting.connections.pusher.secret' => 'test_broadcasting_secret',
            'broadcasting.connections.pusher.app_id' => 'test_app_id',
        ]);
        app('Illuminate\Broadcasting\BroadcastManager')->setDefaultDriver('pusher');
        require base_path('routes/channels.php');

        $this->user1 = User::factory()->create([
            'name' => 'Farhana Rahman',
            'username' => 'farhana_r',
            'email' => 'farhana@example.com',
            'status' => 'active',
        ]);

        $this->user2 = User::factory()->create([
            'name' => 'Tanvir Hasan',
            'username' => 'tanvir_h',
            'email' => 'tanvir@example.com',
            'status' => 'active',
        ]);

        $this->outsider = User::factory()->create([
            'name' => 'Intruder User',
            'username' => 'intruder_u',
            'email' => 'intruder@example.com',
            'status' => 'active',
        ]);

        Friendship::create([
            'user_id' => $this->user1->id,
            'friend_id' => $this->user2->id,
            'status' => 'accepted',
        ]);

        $messengerService = app(MessengerServiceInterface::class);
        $this->directConversation = $messengerService->getOrCreateDirectConversation($this->user1, $this->user2->id);

        $this->groupConversation = Conversation::create([
            'type' => Conversation::TYPE_GROUP,
            'title' => 'Alpha Engineers Group',
            'creator_id' => $this->user1->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->groupConversation->id,
            'user_id' => $this->user1->id,
            'role' => 'admin',
            'request_status' => 'accepted',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->groupConversation->id,
            'user_id' => $this->user2->id,
            'role' => 'member',
            'request_status' => 'accepted',
        ]);
    }

    // =========================================================================
    // SECTION 1: WEBSOCKET & CHANNEL AUTHORIZATION SECURITY
    // =========================================================================

    public function test_unauthorized_user_cannot_subscribe_to_other_users_private_channel(): void
    {
        $response = $this->actingAs($this->outsider, 'sanctum')
            ->postJson('/api/v1/broadcasting/auth', [
                'channel_name' => "private-user.{$this->user1->id}",
                'socket_id' => '123456.789012',
            ]);

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_subscribe_to_their_own_private_channel(): void
    {
        $response = $this->actingAs($this->user1, 'sanctum')
            ->postJson('/api/v1/broadcasting/auth', [
                'channel_name' => "private-user.{$this->user1->id}",
                'socket_id' => '123456.789012',
            ]);

        $response->assertStatus(200);
    }

    public function test_non_participant_cannot_subscribe_to_conversation_channel(): void
    {
        $response = $this->actingAs($this->outsider, 'sanctum')
            ->postJson('/api/v1/broadcasting/auth', [
                'channel_name' => "private-conversation.{$this->directConversation->id}",
                'socket_id' => '123456.789012',
            ]);

        $response->assertStatus(403);
    }

    public function test_participant_with_declined_request_cannot_subscribe_to_conversation_channel(): void
    {
        $declinedUser = User::factory()->create();
        ConversationParticipant::create([
            'conversation_id' => $this->groupConversation->id,
            'user_id' => $declinedUser->id,
            'role' => 'member',
            'request_status' => 'declined',
        ]);

        $response = $this->actingAs($declinedUser, 'sanctum')
            ->postJson('/api/v1/broadcasting/auth', [
                'channel_name' => "private-conversation.{$this->groupConversation->id}",
                'socket_id' => '123456.789012',
            ]);

        $response->assertStatus(403);
    }

    public function test_authorized_participant_can_subscribe_to_conversation_channel(): void
    {
        $response = $this->actingAs($this->user2, 'sanctum')
            ->postJson('/api/v1/broadcasting/auth', [
                'channel_name' => "private-conversation.{$this->directConversation->id}",
                'socket_id' => '123456.789012',
            ]);

        $response->assertStatus(200);
    }

    public function test_non_participant_cannot_subscribe_to_call_channel(): void
    {
        $response = $this->actingAs($this->outsider, 'sanctum')
            ->postJson('/api/v1/broadcasting/auth', [
                'channel_name' => "private-call.{$this->directConversation->id}",
                'socket_id' => '123456.789012',
            ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // SECTION 2: MESSAGE DELIVERY, IDEMPOTENCY & PRIVACY
    // =========================================================================

    public function test_message_idempotency_prevents_duplicate_records(): void
    {
        $idempotencyKey = 'client-uuid-'.uniqid();

        // 1st request
        $response1 = $this->actingAs($this->user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$this->directConversation->id}/messages", [
                'body' => 'Synchronized message payload',
                'idempotency_key' => $idempotencyKey,
            ]);

        $response1->assertStatus(201);
        $messageId1 = $response1->json('data.id');

        // 2nd request with exact same idempotency_key (simulating client retry after network blip)
        $response2 = $this->actingAs($this->user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$this->directConversation->id}/messages", [
                'body' => 'Synchronized message payload',
                'idempotency_key' => $idempotencyKey,
            ]);

        $response2->assertStatus(201);
        $messageId2 = $response2->json('data.id');

        // IDs must match and exactly 1 record must exist
        $this->assertEquals($messageId1, $messageId2);
        $this->assertEquals(1, Message::where('conversation_id', $this->directConversation->id)->count());
    }

    public function test_blocked_user_cannot_send_message(): void
    {
        Friendship::where('user_id', $this->user1->id)
            ->where('friend_id', $this->user2->id)
            ->update(['status' => Friendship::STATUS_BLOCKED]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$this->directConversation->id}/messages", [
                'body' => 'Hey, are you still there?',
            ]);

        $response->assertStatus(403);
    }

    public function test_group_admin_only_restriction_blocks_regular_members(): void
    {
        $this->groupConversation->update([
            'settings' => ['can_send_messages' => 'admins'],
        ]);

        // Regular member user2 attempts to send message
        $response = $this->actingAs($this->user2, 'sanctum')
            ->postJson("/api/v1/conversations/{$this->groupConversation->id}/messages", [
                'body' => 'Member message attempt',
            ]);

        $response->assertStatus(403);

        // Admin user1 can send successfully
        $responseAdmin = $this->actingAs($this->user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$this->groupConversation->id}/messages", [
                'body' => 'Admin announcement message',
            ]);

        $responseAdmin->assertStatus(201);
    }

    // =========================================================================
    // SECTION 3: MEDIA & ATTACHMENT SECURITY
    // =========================================================================

    public function test_dangerous_file_extensions_are_blocked(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('malicious.php', 10, 'application/x-php');

        $response = $this->actingAs($this->user1, 'sanctum')
            ->postJson('/api/v1/messages/attachments', [
                'file' => $file,
            ]);

        $response->assertStatus(422);
    }

    public function test_non_participant_cannot_download_conversation_attachment(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');

        $uploadResponse = $this->actingAs($this->user1, 'sanctum')
            ->postJson('/api/v1/messages/attachments', [
                'file' => $file,
            ]);

        $uploadResponse->assertStatus(201);
        $mediaId = $uploadResponse->json('data.id');

        // Link media to message in directConversation
        $msgResponse = $this->actingAs($this->user1, 'sanctum')
            ->postJson("/api/v1/conversations/{$this->directConversation->id}/messages", [
                'body' => 'Please find attached invoice',
                'media_ids' => [$mediaId],
            ]);
        $msgResponse->assertStatus(201);

        // Outsider attempts to download the file -> must be rejected 403
        $downloadResponse = $this->actingAs($this->outsider, 'sanctum')
            ->get("/api/v1/messages/attachments/{$mediaId}/download");

        $downloadResponse->assertStatus(403);
    }

    public function test_path_traversal_is_blocked_on_attachment_endpoints(): void
    {
        $maliciousMedia = Media::create([
            'user_id' => $this->user1->id,
            'collection' => 'message',
            'disk' => 'public',
            'original_path' => '../../../../etc/passwd',
            'mime_type' => 'text/plain',
            'size' => 100,
            'processing_status' => 'ready',
        ]);

        $response = $this->actingAs($this->user1, 'sanctum')
            ->get("/api/v1/messages/attachments/{$maliciousMedia->id}/download");

        $response->assertStatus(403);
    }

    // =========================================================================
    // SECTION 4: WEBRTC CALLING ENGINE & SIGNALS
    // =========================================================================

    public function test_outsider_cannot_initiate_call_in_foreign_conversation(): void
    {
        $response = $this->actingAs($this->outsider, 'sanctum')
            ->postJson('/api/v1/calls', [
                'conversation_id' => $this->directConversation->id,
                'call_type' => 'audio',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_initiate_call_and_retrieve_ice_servers(): void
    {
        // 1. Initiate call
        $response = $this->actingAs($this->user1, 'sanctum')
            ->postJson('/api/v1/calls', [
                'conversation_id' => $this->directConversation->id,
                'receiver_id' => $this->user2->id,
                'call_type' => 'audio',
            ]);

        $response->assertStatus(201);
        $callId = $response->json('data.id');
        $this->assertNotNull($callId);

        // 2. Retrieve ICE servers
        $iceResponse = $this->actingAs($this->user1, 'sanctum')
            ->getJson('/api/v1/calls/ice-servers');

        $iceResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $servers = $iceResponse->json('data.ice_servers');
        $this->assertIsArray($servers);
        $this->assertNotEmpty($servers);

        // Verify STUN is present
        $urls = collect($servers)->pluck('urls')->flatten()->all();
        $this->assertTrue(collect($urls)->contains(fn ($u) => str_starts_with($u, 'stun:')));
    }

    public function test_livekit_sfu_token_contains_valid_cryptographic_grants_for_group_call(): void
    {
        $callingService = app(CallingService::class);
        $call = $callingService->initiateCall(
            caller: $this->user1,
            conversation: $this->groupConversation,
            callType: 'group_video',
            receiverIds: [$this->user2->id]
        );

        $response = $this->actingAs($this->user1, 'sanctum')
            ->getJson("/api/v1/calls/{$call->id}/token");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // Verify cryptographic token integrity with LiveKitService
        $liveKitService = app(LiveKitService::class);
        $payload = $liveKitService->verifyToken($token);

        $this->assertNotNull($payload);
        $this->assertEquals((string) $this->user1->id, $payload['sub']);
        $this->assertTrue($payload['video']['roomJoin']);
        $this->assertTrue($payload['video']['canPublish']);
        $this->assertTrue($payload['video']['canSubscribe']);
    }

    public function test_callee_can_respond_and_caller_can_leave_call_cleanly(): void
    {
        $callingService = app(CallingService::class);
        $call = $callingService->initiateCall(
            caller: $this->user1,
            conversation: $this->directConversation,
            callType: 'video',
            receiverId: $this->user2->id
        );

        // Callee accepts
        $acceptResponse = $this->actingAs($this->user2, 'sanctum')
            ->postJson("/api/v1/calls/{$call->id}/respond", [
                'action' => 'accept',
            ]);

        $acceptResponse->assertStatus(200);

        // Caller leaves/ends call
        $leaveResponse = $this->actingAs($this->user1, 'sanctum')
            ->postJson("/api/v1/calls/{$call->id}/leave");

        $leaveResponse->assertStatus(200);

        $freshCall = Call::find($call->id);
        $this->assertEquals('ended', $freshCall->status);
        $this->assertNotNull($freshCall->ended_at);
    }
}
