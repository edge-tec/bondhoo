<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Friendship;
use App\Models\PrivacySetting;
use App\Models\User;
use App\Services\Calling\CallingService;
use App\Services\Contracts\CacheServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessengerActiveFriendsAndMultiChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_retrieve_active_friends_list_with_presence(): void
    {
        $user = User::factory()->create(['name' => 'Current User']);
        $friend1 = User::factory()->create(['name' => 'Alice Online']);
        $friend2 = User::factory()->create(['name' => 'Bob Offline']);

        // Establish friendships
        Friendship::create(['user_id' => $user->id, 'friend_id' => $friend1->id, 'status' => 'accepted']);
        Friendship::create(['user_id' => $user->id, 'friend_id' => $friend2->id, 'status' => 'accepted']);

        // Make friend1 online via cacheService
        $cacheService = app(CacheServiceInterface::class);
        $cacheService->registerSessionHeartbeat($friend1->id, 'session-alice', 60);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/presence/friends/active');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Friend 1 should be first because she is online
        $this->assertEquals($friend1->id, $data[0]['id']);
        $this->assertTrue($data[0]['online']);
        $this->assertEquals('Active now', $data[0]['last_seen_display']);

        // Friend 2 should be second because he is offline
        $this->assertEquals($friend2->id, $data[1]['id']);
        $this->assertFalse($data[1]['online']);
    }

    public function test_user_appearing_offline_is_shown_as_offline_in_active_friends(): void
    {
        $user = User::factory()->create(['name' => 'User']);
        $friend = User::factory()->create(['name' => 'Hidden Friend']);

        Friendship::create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => 'accepted']);

        // Friend is actually online in cache
        $cacheService = app(CacheServiceInterface::class);
        $cacheService->registerSessionHeartbeat($friend->id, 'session-hidden', 60);

        // But friend set privacy setting to appear offline
        PrivacySetting::create([
            'user_id' => $friend->id,
            'show_online_status' => false,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/presence/friends/active');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertFalse($data[0]['online'], 'Friend who chose Appear Offline must not be shown as online to others');
    }

    public function test_user_can_toggle_visibility_to_appear_offline(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Toggle to appear offline
        $resOffline = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/presence/visibility', ['appear_offline' => true]);

        $resOffline->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'appear_offline' => true,
                    'show_online_status' => false,
                ],
            ]);

        $this->assertDatabaseHas('privacy_settings', [
            'user_id' => $user->id,
            'show_online_status' => false,
        ]);

        // Toggle back to online
        $resOnline = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/presence/visibility', ['appear_offline' => false]);

        $resOnline->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'appear_offline' => false,
                    'show_online_status' => true,
                ],
            ]);

        $this->assertDatabaseHas('privacy_settings', [
            'user_id' => $user->id,
            'show_online_status' => true,
        ]);
    }

    public function test_direct_conversation_id_is_batch_mapped_for_active_friends(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        Friendship::create(['user_id' => $user->id, 'friend_id' => $friend->id, 'status' => 'accepted']);

        // Create direct conversation
        $conversation = Conversation::create([
            'type' => Conversation::TYPE_DIRECT,
            'creator_id' => $user->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $friend->id,
            'role' => 'member',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/presence/friends/active');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($conversation->id, $data[0]['conversation_id']);
    }

    public function test_user_can_search_active_friends_by_name_or_username(): void
    {
        $user = User::factory()->create();
        $friendA = User::factory()->create(['name' => 'Sakib Al Hasan', 'username' => 'sakib75']);
        $friendB = User::factory()->create(['name' => 'Tamim Iqbal', 'username' => 'tamim28']);

        Friendship::create(['user_id' => $user->id, 'friend_id' => $friendA->id, 'status' => 'accepted']);
        Friendship::create(['user_id' => $user->id, 'friend_id' => $friendB->id, 'status' => 'accepted']);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/presence/friends/active?q=tamim');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($friendB->id, $data[0]['id']);
    }

    public function test_group_conversation_creation_and_member_roles(): void
    {
        $creator = User::factory()->create(['name' => 'Group Admin']);
        $member1 = User::factory()->create(['name' => 'Member One']);
        $member2 = User::factory()->create(['name' => 'Member Two']);

        $token = $creator->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/conversations', [
                'type' => 'group',
                'title' => 'Project Alpha',
                'description' => 'Official group for Project Alpha',
                'participant_ids' => [$member1->id, $member2->id],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'type' => 'group',
                    'title' => 'Project Alpha',
                ],
            ]);

        $convId = $response->json('data.id');

        $this->assertDatabaseHas('conversations', [
            'id' => $convId,
            'title' => 'Project Alpha',
            'type' => Conversation::TYPE_GROUP,
            'creator_id' => $creator->id,
        ]);

        // Creator is admin
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $convId,
            'user_id' => $creator->id,
            'role' => 'admin',
        ]);

        // Members have member role
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $convId,
            'user_id' => $member1->id,
            'role' => 'member',
        ]);
    }

    public function test_group_audio_and_video_call_initiation(): void
    {
        $creator = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        $conversation = Conversation::create([
            'type' => Conversation::TYPE_GROUP,
            'title' => 'Developers Hub',
            'creator_id' => $creator->id,
        ]);

        ConversationParticipant::create(['conversation_id' => $conversation->id, 'user_id' => $creator->id, 'role' => 'admin']);
        ConversationParticipant::create(['conversation_id' => $conversation->id, 'user_id' => $member1->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conversation->id, 'user_id' => $member2->id, 'role' => 'member']);

        $callingService = app(CallingService::class);
        $call = $callingService->initiateCall($creator, $conversation, 'video');

        $this->assertEquals(Call::TYPE_GROUP_VIDEO, $call->call_type);
        $this->assertEquals(Call::STATUS_RINGING, $call->status);
        $this->assertEquals($creator->id, $call->caller_id);

        // All participants should be registered in call_participants
        $this->assertDatabaseHas('call_participants', [
            'call_id' => $call->id,
            'user_id' => $creator->id,
            'role' => 'caller',
            'status' => 'accepted',
        ]);

        $this->assertDatabaseHas('call_participants', [
            'call_id' => $call->id,
            'user_id' => $member1->id,
            'status' => 'ringing',
        ]);

        $this->assertDatabaseHas('call_participants', [
            'call_id' => $call->id,
            'user_id' => $member2->id,
            'status' => 'ringing',
        ]);
    }

    public function test_user_can_initiate_multi_friend_call_with_multiple_receivers(): void
    {
        $caller = User::factory()->create();
        $friend1 = User::factory()->create();
        $friend2 = User::factory()->create();

        $response = $this->actingAs($caller)->postJson('/api/v1/calls', [
            'receiver_ids' => [$friend1->id, $friend2->id],
            'call_type' => 'video',
        ]);

        $response->assertStatus(201);
        $callId = $response->json('data.id');

        $this->assertDatabaseHas('calls', [
            'id' => $callId,
            'caller_id' => $caller->id,
            'call_type' => Call::TYPE_GROUP_VIDEO,
            'status' => Call::STATUS_RINGING,
        ]);

        $this->assertDatabaseHas('call_participants', [
            'call_id' => $callId,
            'user_id' => $friend1->id,
            'status' => 'ringing',
        ]);

        $this->assertDatabaseHas('call_participants', [
            'call_id' => $callId,
            'user_id' => $friend2->id,
            'status' => 'ringing',
        ]);
    }

    public function test_user_can_invite_friend_to_ongoing_call_in_realtime(): void
    {
        $caller = User::factory()->create(['name' => 'মমিনুল হক']);
        $callee = User::factory()->create(['name' => 'সাকিব']);
        $newFriend = User::factory()->create(['name' => 'মুশফিক']);

        $conversation = Conversation::create([
            'type' => Conversation::TYPE_DIRECT,
        ]);
        ConversationParticipant::create(['conversation_id' => $conversation->id, 'user_id' => $caller->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conversation->id, 'user_id' => $callee->id, 'role' => 'member']);

        $callingService = app(CallingService::class);
        $call = $callingService->initiateCall($caller, $conversation, 'audio');

        // Caller invites newFriend to the ongoing call
        $response = $this->actingAs($caller)->postJson("/api/v1/calls/{$call->id}/invite", [
            'friend_id' => $newFriend->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.invited.0.id', $newFriend->id);

        // Call should be upgraded to group audio
        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'call_type' => Call::TYPE_GROUP_AUDIO,
        ]);

        // Conversation should be upgraded to group
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'type' => Conversation::TYPE_GROUP,
        ]);

        // New friend must be added as participant with status ringing
        $this->assertDatabaseHas('call_participants', [
            'call_id' => $call->id,
            'user_id' => $newFriend->id,
            'status' => 'ringing',
        ]);

        // New friend must be able to access the call route
        $callRoomResponse = $this->actingAs($newFriend)->get("/call/{$conversation->id}?type=audio&answer=1&call_id={$call->id}");
        $callRoomResponse->assertStatus(200);
    }

    public function test_stranger_cannot_invite_friends_to_call(): void
    {
        $caller = User::factory()->create();
        $callee = User::factory()->create();
        $stranger = User::factory()->create();
        $targetFriend = User::factory()->create();

        $conversation = Conversation::create(['type' => Conversation::TYPE_DIRECT]);
        ConversationParticipant::create(['conversation_id' => $conversation->id, 'user_id' => $caller->id]);
        ConversationParticipant::create(['conversation_id' => $conversation->id, 'user_id' => $callee->id]);

        $call = app(CallingService::class)->initiateCall($caller, $conversation, 'audio');

        $response = $this->actingAs($stranger)->postJson("/api/v1/calls/{$call->id}/invite", [
            'friend_id' => $targetFriend->id,
        ]);

        $response->assertStatus(403);
    }
}
