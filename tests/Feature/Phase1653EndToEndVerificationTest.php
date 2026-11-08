<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1653EndToEndVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected User $userB;

    protected User $userC;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'name' => 'Aileen Schaden',
            'username' => 'dixie.mann',
            'email' => 'dixie.mann@example.com',
        ]);
        $this->userA->profile()->create([
            'bio' => 'Dream Big • Work Hard • Be Happy',
            'headline' => 'Software Engineer',
            'location' => 'Dhaka, Bangladesh',
        ]);

        $this->userB = User::factory()->create([
            'name' => 'Prof. Julien Lubowitz I',
            'username' => 'qkuvalis',
            'email' => 'qkuvalis@example.com',
        ]);
        $this->userB->profile()->create([
            'bio' => 'Senior Researcher & Mentor',
            'headline' => 'Lead Scientist',
            'location' => 'Dhaka, Bangladesh',
        ]);

        $this->userC = User::factory()->create([
            'name' => 'Tanvir Hasan',
            'username' => 'tanvir',
            'email' => 'tanvir@example.com',
        ]);
    }

    // =========================================================================
    // 1. PROFILE DASHBOARD & PERSONAL DETAILS VERIFICATION
    // =========================================================================

    public function test_auth_me_returns_profile_information_correctly(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.username', 'dixie.mann')
            ->assertJsonPath('data.name', 'Aileen Schaden')
            ->assertJsonPath('data.bio', 'Dream Big • Work Hard • Be Happy');
    }

    public function test_profile_about_returns_bio_and_character_limits(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')
            ->getJson('/api/v1/profile/about');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.username', 'dixie.mann')
            ->assertJsonPath('data.bio', 'Dream Big • Work Hard • Be Happy')
            ->assertJsonStructure([
                'data' => [
                    'user_id',
                    'username',
                    'name',
                    'bio',
                    'headline',
                    'intro',
                    'about',
                    'character_limits' => ['bio_max', 'headline_max', 'about_max'],
                    'is_profile_locked',
                ],
            ]);
    }

    public function test_profile_analytics_returns_28_day_metrics(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')
            ->getJson('/api/v1/profile/analytics');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'total_views',
                    'views_today',
                    'views_this_week',
                    'unique_viewers_count',
                    'timeframe',
                    'views_trend',
                    'device_breakdown',
                    'discovery_sources',
                ],
            ]);
    }

    public function test_user_posts_endpoint_returns_published_posts(): void
    {
        Post::create([
            'user_id' => $this->userA->id,
            'content' => 'Excited to connect with friends on Bondhoo!',
            'audience' => 'public',
            'type' => 'text',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->userA, 'sanctum')
            ->getJson("/api/v1/users/{$this->userA->username}/posts");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Excited to connect with friends on Bondhoo!');
    }

    public function test_profile_update_persists_changes(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')
            ->putJson('/api/v1/auth/profile', [
                'bio' => 'Engineering Lead @ Bondhoo',
                'location' => 'Dhaka, Bangladesh',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->userA->id,
            'bio' => 'Engineering Lead @ Bondhoo',
        ]);
    }

    // =========================================================================
    // 2. FRIENDS & SOCIAL RELATIONSHIP VERIFICATION
    // =========================================================================

    public function test_friend_request_lifecycle_and_acceptance(): void
    {
        // 1. Send request
        $sendRes = $this->actingAs($this->userA, 'sanctum')
            ->postJson("/api/v1/friends/{$this->userB->id}/request");
        $sendRes->assertStatus(201);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $this->userA->id,
            'friend_id' => $this->userB->id,
            'status' => 'pending',
        ]);

        // 2. Accept request as User B
        $acceptRes = $this->actingAs($this->userB, 'sanctum')
            ->postJson("/api/v1/friends/{$this->userA->id}/accept");
        $acceptRes->assertStatus(200);

        $this->assertDatabaseHas('friendships', [
            'user_id' => $this->userA->id,
            'friend_id' => $this->userB->id,
            'status' => 'accepted',
        ]);
    }

    public function test_user_cannot_send_friend_request_to_themselves(): void
    {
        $response = $this->actingAs($this->userA, 'sanctum')
            ->postJson("/api/v1/friends/{$this->userA->id}/request");

        $this->assertTrue(in_array($response->getStatusCode(), [400, 422]));
    }

    // =========================================================================
    // 3. MESSENGER HUB & CONVERSATIONS VERIFICATION (ALL 7 FILTERS)
    // =========================================================================

    public function test_conversations_all_filter(): void
    {
        $conv = Conversation::create(['type' => 'direct', 'creator_id' => $this->userA->id]);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $this->userA->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $this->userB->id, 'role' => 'member']);

        $response = $this->actingAs($this->userA, 'sanctum')
            ->getJson('/api/v1/conversations?filter=all');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_conversations_unread_filter(): void
    {
        $conv = Conversation::create(['type' => 'direct', 'creator_id' => $this->userB->id]);
        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'user_id' => $this->userA->id,
            'role' => 'member',
            'last_read_message_id' => 0,
            'is_archived' => false,
        ]);
        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'user_id' => $this->userB->id,
            'role' => 'member',
            'last_read_message_id' => 0,
            'is_archived' => false,
        ]);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $this->userB->id,
            'body' => 'Unread message for User A',
            'type' => 'text',
        ]);
        $conv->update(['last_message_id' => $msg->id]);

        $response = $this->actingAs($this->userA, 'sanctum')
            ->getJson('/api/v1/conversations?filter=unread');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_conversations_groups_filter(): void
    {
        $group = Conversation::create([
            'type' => 'group',
            'title' => 'Bondhoo Core Architecture',
            'creator_id' => $this->userA->id,
        ]);
        ConversationParticipant::create(['conversation_id' => $group->id, 'user_id' => $this->userA->id, 'role' => 'admin']);
        ConversationParticipant::create(['conversation_id' => $group->id, 'user_id' => $this->userB->id, 'role' => 'member']);

        $response = $this->actingAs($this->userA, 'sanctum')
            ->getJson('/api/v1/conversations?filter=groups');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'group');
    }

    public function test_conversation_pin_toggle(): void
    {
        $conv = Conversation::create(['type' => 'direct', 'creator_id' => $this->userA->id]);
        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'user_id' => $this->userA->id,
            'role' => 'member',
            'is_pinned' => false,
        ]);

        $response = $this->actingAs($this->userA, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/pin");

        $response->assertStatus(200);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conv->id,
            'user_id' => $this->userA->id,
            'is_pinned' => true,
        ]);
    }

    public function test_conversation_archive_toggle(): void
    {
        $conv = Conversation::create(['type' => 'direct', 'creator_id' => $this->userA->id]);
        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'user_id' => $this->userA->id,
            'role' => 'member',
            'is_archived' => false,
        ]);

        $response = $this->actingAs($this->userA, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/archive");

        $response->assertStatus(200);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conv->id,
            'user_id' => $this->userA->id,
            'is_archived' => true,
        ]);
    }

    public function test_conversation_mute_toggle(): void
    {
        $conv = Conversation::create(['type' => 'direct', 'creator_id' => $this->userA->id]);
        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'user_id' => $this->userA->id,
            'role' => 'member',
            'is_muted' => false,
        ]);

        $response = $this->actingAs($this->userA, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/mute", ['duration' => '8h']);

        $response->assertStatus(200);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conv->id,
            'user_id' => $this->userA->id,
            'is_muted' => true,
        ]);
    }

    // =========================================================================
    // 4. WEBRTC CALLING & GROUP SIGNALING VERIFICATION
    // =========================================================================

    public function test_call_initiation_and_session_lifecycle(): void
    {
        $conv = Conversation::create(['type' => 'direct', 'creator_id' => $this->userA->id]);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $this->userA->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $this->userB->id, 'role' => 'member']);

        // 1. Initiate Audio Call
        $initRes = $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/v1/calls', [
                'conversation_id' => $conv->id,
                'call_type' => 'audio',
                'target_user_ids' => [$this->userB->id],
            ]);

        $initRes->assertStatus(201);
        $callId = $initRes->json('data.id');
        $this->assertNotNull($callId);

        // 2. Exchange WebRTC Signaling (Offer)
        $signalRes = $this->actingAs($this->userA, 'sanctum')
            ->postJson("/api/v1/calls/{$callId}/signal", [
                'signal_type' => 'offer',
                'payload' => ['type' => 'offer', 'sdp' => 'v=0\r\no=alice 1234 5678...'],
                'target_user_id' => $this->userB->id,
            ]);
        $signalRes->assertStatus(200);

        // 3. Exchange WebRTC Signaling (ICE restart renegotiate)
        $renegotiateRes = $this->actingAs($this->userA, 'sanctum')
            ->postJson("/api/v1/calls/{$callId}/signal", [
                'signal_type' => 'renegotiate',
                'payload' => ['type' => 'renegotiate'],
                'target_user_id' => $this->userB->id,
            ]);
        $renegotiateRes->assertStatus(200);

        // 4. End Call / Leave Call
        $endRes = $this->actingAs($this->userA, 'sanctum')
            ->postJson("/api/v1/calls/{$callId}/leave");
        $endRes->assertStatus(200);
    }

    // =========================================================================
    // 5. SECURITY & AUTHORIZATION ISOLATION
    // =========================================================================

    public function test_non_participant_cannot_access_private_conversation(): void
    {
        $conv = Conversation::create(['type' => 'direct', 'creator_id' => $this->userA->id]);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $this->userA->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $this->userB->id, 'role' => 'member']);

        // User C is NOT a participant
        $response = $this->actingAs($this->userC, 'sanctum')
            ->getJson("/api/v1/conversations/{$conv->id}");

        $response->assertStatus(403);
    }

    public function test_non_participant_cannot_send_messages_to_conversation(): void
    {
        $conv = Conversation::create(['type' => 'direct', 'creator_id' => $this->userA->id]);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $this->userA->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conv->id, 'user_id' => $this->userB->id, 'role' => 'member']);

        $response = $this->actingAs($this->userC, 'sanctum')
            ->postJson("/api/v1/conversations/{$conv->id}/messages", [
                'body' => 'Intruder message',
                'type' => 'text',
            ]);

        $response->assertStatus(403);
    }
}
