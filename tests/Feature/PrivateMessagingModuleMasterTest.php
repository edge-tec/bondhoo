<?php

namespace Tests\Feature;

use App\Events\NewMessageEvent;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PrivateMessagingModuleMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Helper to create a user with a profile.
     */
    protected function createTestUser(string $username): User
    {
        static $counter = 800;
        $counter++;

        $user = User::factory()->create([
            'username' => $username,
            'name' => ucfirst($username).' User',
            'email' => "{$username}{$counter}@example.com",
            'phone' => "+880191000{$counter}",
            'status' => 'active',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => ucfirst($username),
            'last_name' => 'User',
            'display_name' => ucfirst($username).' User',
        ]);

        return $user;
    }

    /**
     * Test 1: Direct 1-on-1 conversation creation and inbox listing.
     */
    public function test_direct_conversation_creation_and_membership(): void
    {
        $alice = $this->createTestUser('alicechat');
        $bob = $this->createTestUser('bobchat');

        // Alice creates direct conversation with Bob
        $createResp = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'recipient_id' => $bob->id,
            ]);

        $createResp->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'type' => 'direct',
                ],
            ]);

        $convId = $createResp->json('data.id');

        // Verify both are members of the conversation
        $conversation = Conversation::findOrFail($convId);
        $this->assertTrue($conversation->hasParticipant($alice->id));
        $this->assertTrue($conversation->hasParticipant($bob->id));

        // Alice can view conversation details
        $aliceView = $this->actingAs($alice, 'sanctum')
            ->getJson("/api/v1/conversations/{$convId}");
        $aliceView->assertStatus(200);

        // Bob can view conversation details
        $bobView = $this->actingAs($bob, 'sanctum')
            ->getJson("/api/v1/conversations/{$convId}");
        $bobView->assertStatus(200);

        // Both see it in their conversation list
        $aliceList = $this->actingAs($alice, 'sanctum')
            ->getJson('/api/v1/conversations');
        $aliceList->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'total']]);

        $this->assertEquals($convId, $aliceList->json('data.0.id'));
    }

    /**
     * Test 2: Message sending, storage, and message pagination.
     */
    public function test_message_sending_and_pagination(): void
    {
        $alice = $this->createTestUser('msgalice');
        $bob = $this->createTestUser('msgbob');

        $convResp = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'recipient_id' => $bob->id,
            ]);
        $convId = $convResp->json('data.id');

        // Alice sends 3 messages
        for ($i = 1; $i <= 3; $i++) {
            $msgResp = $this->actingAs($alice, 'sanctum')
                ->postJson("/api/v1/conversations/{$convId}/messages", [
                    'body' => "Message number {$i}",
                ]);

            $msgResp->assertStatus(201)
                ->assertJsonPath('data.body', "Message number {$i}")
                ->assertJsonPath('data.delivery_status', 'sent');
        }

        $this->assertDatabaseCount('messages', 3);

        // Bob fetches paginated messages (2 per page)
        $pagedResp = $this->actingAs($bob, 'sanctum')
            ->getJson("/api/v1/conversations/{$convId}/messages?per_page=2");

        $pagedResp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 2,
                    'per_page' => 2,
                    'total' => 3,
                ],
            ]);

        $this->assertCount(2, $pagedResp->json('data'));
    }

    /**
     * Test 3: Conversation membership authorization prevents non-members from reading or writing.
     */
    public function test_conversation_membership_authorization_prevents_outsider_access(): void
    {
        $alice = $this->createTestUser('authalice');
        $bob = $this->createTestUser('authbob');
        $charlie = $this->createTestUser('outsidercharlie');

        $convResp = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'recipient_id' => $bob->id,
            ]);
        $convId = $convResp->json('data.id');

        // Charlie (non-member) attempts to view conversation details
        $charlieViewConv = $this->actingAs($charlie, 'sanctum')
            ->getJson("/api/v1/conversations/{$convId}");
        $charlieViewConv->assertStatus(403);

        // Charlie attempts to view conversation messages
        $charlieViewMsgs = $this->actingAs($charlie, 'sanctum')
            ->getJson("/api/v1/conversations/{$convId}/messages");
        $charlieViewMsgs->assertStatus(403);

        // Charlie attempts to send message to conversation
        $charlieSendMsg = $this->actingAs($charlie, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/messages", [
                'body' => 'I should not be able to talk here.',
            ]);
        $charlieSendMsg->assertStatus(403);

        // Charlie attempts to mark conversation as read
        $charlieRead = $this->actingAs($charlie, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/read");
        $charlieRead->assertStatus(403);
    }

    /**
     * Test 4: Unread counts tracking and read receipts.
     */
    public function test_unread_counts_and_marking_as_read(): void
    {
        $alice = $this->createTestUser('unAlice');
        $bob = $this->createTestUser('unBob');

        $convResp = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'recipient_id' => $bob->id,
            ]);
        $convId = $convResp->json('data.id');

        // Alice sends 2 messages to Bob
        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/messages", ['body' => 'First ping']);
        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/messages", ['body' => 'Second ping']);

        // Bob checks unread message count
        $countBefore = $this->actingAs($bob, 'sanctum')
            ->getJson('/api/v1/messages/unread-count');

        $countBefore->assertStatus(200)
            ->assertJsonPath('data.unread_count', 2);

        // Bob marks conversation as read
        $readResp = $this->actingAs($bob, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/read");

        $readResp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'conversation_id' => $convId,
                    'messages_marked_read' => 2,
                ],
            ]);

        // Messages should now have status 'seen' and non-null read_at
        $this->assertEquals(0, Message::where('conversation_id', $convId)
            ->where('sender_id', $alice->id)
            ->whereNull('read_at')
            ->count());

        // Bob checks unread message count again -> should be 0
        $countAfter = $this->actingAs($bob, 'sanctum')
            ->getJson('/api/v1/messages/unread-count');

        $countAfter->assertStatus(200)
            ->assertJsonPath('data.unread_count', 0);
    }

    /**
     * Test 5: Realtime broadcasting on message dispatch.
     */
    public function test_realtime_broadcasting_on_message_dispatch(): void
    {
        Event::fake([NewMessageEvent::class]);

        $alice = $this->createTestUser('bcastalice');
        $bob = $this->createTestUser('bcastbob');

        $convResp = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'recipient_id' => $bob->id,
            ]);
        $convId = $convResp->json('data.id');

        $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/messages", [
                'body' => 'Live real-time message payload',
            ])
            ->assertStatus(201);

        Event::assertDispatched(NewMessageEvent::class, function ($event) use ($convId) {
            return (int) $event->conversationId === (int) $convId;
        });
    }

    /**
     * Test 6: Blocked user cannot send messages in direct conversation.
     */
    public function test_blocked_user_cannot_send_messages(): void
    {
        $alice = $this->createTestUser('blockalice');
        $bob = $this->createTestUser('blockbob');

        $convResp = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'recipient_id' => $bob->id,
            ]);
        $convId = $convResp->json('data.id');

        // Bob blocks Alice
        Friendship::create([
            'user_id' => $bob->id,
            'friend_id' => $alice->id,
            'status' => Friendship::STATUS_BLOCKED,
        ]);

        // Alice attempts to send message to Bob
        $blockedSend = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/messages", [
                'body' => 'Trying to message blocked user',
            ]);

        $blockedSend->assertStatus(403);
    }

    /**
     * Test 7: Empty message validation failure.
     */
    public function test_empty_message_validation_failure(): void
    {
        $alice = $this->createTestUser('emptyalice');
        $bob = $this->createTestUser('emptybob');

        $convResp = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'direct',
                'recipient_id' => $bob->id,
            ]);
        $convId = $convResp->json('data.id');

        $emptyResp = $this->actingAs($alice, 'sanctum')
            ->postJson("/api/v1/conversations/{$convId}/messages", [
                'body' => '   ',
            ]);

        $emptyResp->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Message must have text or media attachment.',
            ]);
    }

    /**
     * Test 8: Group conversation creation and membership authorization.
     */
    public function test_group_conversation_creation_and_membership_authorization(): void
    {
        $alice = $this->createTestUser('galice');
        $bob = $this->createTestUser('gbob');
        $charlie = $this->createTestUser('gcharlie');
        $david = $this->createTestUser('gdavid');

        // Alice creates group with Bob and Charlie
        $groupResp = $this->actingAs($alice, 'sanctum')
            ->postJson('/api/v1/conversations', [
                'type' => 'group',
                'title' => 'Project JugaJug Developers',
                'participant_ids' => [$bob->id, $charlie->id],
            ]);

        $groupResp->assertStatus(201)
            ->assertJsonPath('data.type', 'group')
            ->assertJsonPath('data.title', 'Project JugaJug Developers');

        $groupId = $groupResp->json('data.id');

        // David (not in group) cannot read group messages
        $davidRead = $this->actingAs($david, 'sanctum')
            ->getJson("/api/v1/conversations/{$groupId}/messages");
        $davidRead->assertStatus(403);

        // Charlie (member) can read and post
        $charliePost = $this->actingAs($charlie, 'sanctum')
            ->postJson("/api/v1/conversations/{$groupId}/messages", [
                'body' => 'Hello team!',
            ]);
        $charliePost->assertStatus(201);
    }
}
