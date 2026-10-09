<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessengerMobileMultiChatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test navigating to /messages opens the inbox without locking into any single conversation.
     */
    public function test_navigating_to_messages_opens_inbox_with_all_conversations_unlocked(): void
    {
        $user = User::factory()->create(['name' => 'Main User']);
        UserProfile::create(['user_id' => $user->id]);

        $friend1 = User::factory()->create(['name' => 'Rahim Ahmed']);
        UserProfile::create(['user_id' => $friend1->id]);

        $friend2 = User::factory()->create(['name' => 'Karim Hasan']);
        UserProfile::create(['user_id' => $friend2->id]);

        $friend3 = User::factory()->create(['name' => 'Sumona Akter']);
        UserProfile::create(['user_id' => $friend3->id]);

        $messengerService = app(MessengerServiceInterface::class);

        $conv1 = $messengerService->getOrCreateDirectConversation($user, $friend1->id);
        $conv2 = $messengerService->getOrCreateDirectConversation($user, $friend2->id);
        $conv3 = $messengerService->getOrCreateDirectConversation($user, $friend3->id);

        $messengerService->sendMessage($friend1, $conv1, ['body' => 'Hello from Rahim']);
        $messengerService->sendMessage($friend2, $conv2, ['body' => 'Meeting today?']);
        $messengerService->sendMessage($friend3, $conv3, ['body' => 'Check the design']);

        $response = $this->actingAs($user)->get(route('messages.index'));

        $response->assertStatus(200);
        $response->assertViewHas('activeConversation', null);
        $response->assertViewHas('conversations');

        $viewConversations = $response->viewData('conversations');
        $this->assertCount(3, $viewConversations);

        // Verify inbox displays friend names and message previews
        $response->assertSee('Rahim Ahmed');
        $response->assertSee('Karim Hasan');
        $response->assertSee('Sumona Akter');
        $response->assertSee('Hello from Rahim');
        $response->assertSee('Meeting today?');
        $response->assertSee('Check the design');

        // Back button is rendered for mobile view when in chat
        $response->assertSee('btn-mobile-back');
    }

    /**
     * Test selecting a specific conversation via ?conversation_id= sets activeConversation
     * and keeps all conversations available in the inbox pane.
     */
    public function test_selecting_specific_conversation_activates_chat_while_retaining_inbox(): void
    {
        $user = User::factory()->create(['name' => 'Main User']);
        UserProfile::create(['user_id' => $user->id]);

        $friend1 = User::factory()->create(['name' => 'Friend One']);
        UserProfile::create(['user_id' => $friend1->id]);

        $friend2 = User::factory()->create(['name' => 'Friend Two']);
        UserProfile::create(['user_id' => $friend2->id]);

        $messengerService = app(MessengerServiceInterface::class);

        $conv1 = $messengerService->getOrCreateDirectConversation($user, $friend1->id);
        $conv2 = $messengerService->getOrCreateDirectConversation($user, $friend2->id);

        $messengerService->sendMessage($friend1, $conv1, ['body' => 'Msg 1']);
        $messengerService->sendMessage($friend2, $conv2, ['body' => 'Msg 2']);

        $response = $this->actingAs($user)->get(route('messages.index', ['conversation_id' => $conv1->id]));

        $response->assertStatus(200);
        $response->assertViewHas('activeConversation');
        $this->assertEquals($conv1->id, $response->viewData('activeConversation')->id);

        // Both conversations remain listed in conversations view data
        $viewConversations = $response->viewData('conversations');
        $this->assertCount(2, $viewConversations);
        $response->assertSee('Friend One');
        $response->assertSee('Friend Two');
    }

    /**
     * Test selecting a conversation via show route /messages/{id}.
     */
    public function test_messages_show_route_activates_conversation_and_preserves_inbox(): void
    {
        $user = User::factory()->create(['name' => 'Main User']);
        UserProfile::create(['user_id' => $user->id]);

        $friend = User::factory()->create(['name' => 'Friend Bob']);
        UserProfile::create(['user_id' => $friend->id]);

        $messengerService = app(MessengerServiceInterface::class);
        $conv = $messengerService->getOrCreateDirectConversation($user, $friend->id);
        $messengerService->sendMessage($friend, $conv, ['body' => 'How are you?']);

        $response = $this->actingAs($user)->get(route('messages.show', $conv->id));

        $response->assertStatus(200);
        $response->assertViewHas('activeConversation');
        $this->assertEquals($conv->id, $response->viewData('activeConversation')->id);
        $response->assertSee('Friend Bob');
        $response->assertSee('How are you?');
    }

    /**
     * Test that user cannot access another user's private conversation.
     */
    public function test_unauthorized_user_cannot_access_private_conversation(): void
    {
        $user1 = User::factory()->create(['name' => 'User One']);
        $user2 = User::factory()->create(['name' => 'User Two']);
        $outsider = User::factory()->create(['name' => 'Outsider']);

        $messengerService = app(MessengerServiceInterface::class);
        $privateConv = $messengerService->getOrCreateDirectConversation($user1, $user2->id);

        // Outsider visiting the web route
        $response = $this->actingAs($outsider)->get(route('messages.show', $privateConv->id));
        $response->assertRedirect(route('messages.index'));

        // Outsider calling the API
        $token = $outsider->createToken('test')->plainTextToken;
        $apiResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/conversations/{$privateConv->id}");
        $apiResponse->assertStatus(403);
    }

    /**
     * Test conversation search API filters by participant name.
     */
    public function test_conversation_api_search_filters_by_participant_name(): void
    {
        $user = User::factory()->create(['name' => 'Main User']);
        $friendA = User::factory()->create(['name' => 'Tanvir Alam']);
        $friendB = User::factory()->create(['name' => 'Shakil Khan']);

        $messengerService = app(MessengerServiceInterface::class);
        $convA = $messengerService->getOrCreateDirectConversation($user, $friendA->id);
        $convB = $messengerService->getOrCreateDirectConversation($user, $friendB->id);

        $messengerService->sendMessage($friendA, $convA, ['body' => 'Hello Tanvir']);
        $messengerService->sendMessage($friendB, $convB, ['body' => 'Hello Shakil']);

        $token = $user->createToken('test')->plainTextToken;

        // Search for Tanvir
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/conversations?search=Tanvir');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($convA->id, $data[0]['id']);

        // Search for Shakil
        $response2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/conversations?search=Shakil');

        $response2->assertStatus(200);
        $data2 = $response2->json('data');
        $this->assertCount(1, $data2);
        $this->assertEquals($convB->id, $data2[0]['id']);
    }

    /**
     * Test conversation API pagination returns expected metadata without dropping conversations.
     */
    public function test_conversation_api_pagination_works_correctly(): void
    {
        $user = User::factory()->create(['name' => 'Main User']);
        $messengerService = app(MessengerServiceInterface::class);

        for ($i = 1; $i <= 5; $i++) {
            $f = User::factory()->create(['name' => "Friend {$i}"]);
            $c = $messengerService->getOrCreateDirectConversation($user, $f->id);
            $messengerService->sendMessage($f, $c, ['body' => "Message {$i}"]);
        }

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/conversations?per_page=2&page=1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

        $this->assertCount(2, $response->json('data'));
        $this->assertEquals(5, $response->json('meta.total'));
        $this->assertEquals(3, $response->json('meta.last_page'));
    }

    /**
     * Test opening chat via ?user={friend_id} initiates conversation and marks it active.
     */
    public function test_direct_user_query_param_initiates_and_activates_chat(): void
    {
        $user = User::factory()->create(['name' => 'Main User']);
        UserProfile::create(['user_id' => $user->id]);

        $targetUser = User::factory()->create(['name' => 'New Friend']);
        UserProfile::create(['user_id' => $targetUser->id]);

        $response = $this->actingAs($user)->get(route('messages.index', ['user' => $targetUser->id]));

        $response->assertStatus(200);
        $response->assertViewHas('activeConversation');
        $activeConv = $response->viewData('activeConversation');
        $this->assertNotNull($activeConv);
        $this->assertTrue($activeConv->hasParticipant($user->id));
        $this->assertTrue($activeConv->hasParticipant($targetUser->id));
    }
}
