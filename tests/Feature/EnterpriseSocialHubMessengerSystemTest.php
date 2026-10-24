<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EnterpriseSocialHubMessengerSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $recipient;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Sarah Wilson',
            'username' => 'sarahwilson',
        ]);
        UserProfile::create([
            'user_id' => $this->user->id,
            'occupation' => 'Works at Tech Solutions',
            'location' => 'Lives in Dhaka, Bangladesh',
        ]);

        $this->recipient = User::factory()->create([
            'name' => 'James Miller',
            'username' => 'jamesmiller',
        ]);
        UserProfile::create([
            'user_id' => $this->recipient->id,
            'occupation' => 'Lead Engineer',
            'location' => 'Dhaka, Bangladesh',
        ]);

        $messengerService = app(MessengerServiceInterface::class);
        $this->conversation = $messengerService->getOrCreateDirectConversation($this->user, $this->recipient->id);
    }

    /**
     * Test unauthenticated access to /app/messages redirects to login.
     */
    public function test_unauthenticated_access_to_app_messages_redirects_to_login(): void
    {
        $response = $this->get('/app/messages');
        $response->assertRedirect(route('login'));
    }

    /**
     * Test authenticated user can access /app/messages and renders Messenger Inertia page.
     */
    public function test_authenticated_user_can_access_app_messages_and_renders_inertia_messenger(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->get('/app/messages');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Messenger')
            ->has('conversations')
            ->has('initialActiveConversation')
            ->has('initialMessages')
            ->has('otherUserProfile')
            ->has('friends')
        );
    }

    /**
     * Test authenticated user can access /messenger alias and renders Messenger page.
     */
    public function test_authenticated_user_can_access_messenger_alias(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->get('/messenger');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Messenger')
            ->has('conversations')
            ->has('initialActiveConversation')
        );
    }

    /**
     * Test passing conversation_id selects specific conversation and marks it read.
     */
    public function test_selecting_conversation_by_id_query_param(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->get("/app/messages?conversation_id={$this->conversation->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Messenger')
            ->where('initialActiveConversation.id', $this->conversation->id)
        );
    }

    /**
     * Test passing user_id query param finds or creates direct conversation.
     */
    public function test_selecting_conversation_by_user_id_query_param(): void
    {
        $otherUser = User::factory()->create(['name' => 'Emma Davis']);
        UserProfile::create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'web')
            ->get("/app/messages?user_id={$otherUser->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Messenger')
            ->has('initialActiveConversation')
        );
    }

    /**
     * Test sending message via API stores in database and returns 201.
     */
    public function test_sending_message_via_api_persists_in_database(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
                'body' => 'Hello from SocialHub Messenger test!',
                'client_message_id' => 'msg_test_101',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Message sent successfully.',
            ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->user->id,
            'body' => 'Hello from SocialHub Messenger test!',
        ]);
    }

    /**
     * Test uploading attachment via API stores media record.
     */
    public function test_uploading_chat_attachment_via_api(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('test_chat_photo.jpg', 600, 600);

        $response = $this->actingAs($this->user, 'web')
            ->post('/api/v1/messages/attachments', [
                'file' => $file,
                'collection' => 'message',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Attachment uploaded successfully.',
            ]);

        $this->assertDatabaseHas('media', [
            'user_id' => $this->user->id,
            'collection' => 'message',
        ]);
    }

    /**
     * Test muting conversation toggles is_muted state.
     */
    public function test_toggling_mute_conversation(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/conversations/{$this->conversation->id}/mute");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
            'is_muted' => true,
        ]);
    }

    /**
     * Test clearing chat history updates participant cleared_at.
     */
    public function test_clearing_conversation_history(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/conversations/{$this->conversation->id}/clear");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $participant = $this->conversation->participants()->where('user_id', $this->user->id)->first();
        $this->assertNotNull($participant->cleared_at);
    }

    /**
     * Test blocking recipient user via API.
     */
    public function test_blocking_user_via_api(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->postJson("/api/v1/users/{$this->recipient->id}/block");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('blocked_users', [
            'user_id' => $this->user->id,
            'identifier' => (string) $this->recipient->id,
        ]);
    }

    /**
     * Test WebRTC audio and video calling routes accessible to participant.
     */
    public function test_webrtc_call_screen_accessible_to_participants(): void
    {
        $audioResponse = $this->actingAs($this->user, 'web')
            ->get("/call/{$this->conversation->id}?type=audio");

        $audioResponse->assertStatus(200);

        $videoResponse = $this->actingAs($this->user, 'web')
            ->get("/call/{$this->conversation->id}?type=video");

        $videoResponse->assertStatus(200);
    }
}
