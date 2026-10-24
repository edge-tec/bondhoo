<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use App\Services\Calling\CallingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase4RealtimeCallsVoiceAndSoundSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['label' => 'Super Admin', 'description' => 'Full access']);
        Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Admin', 'description' => 'Staff administrator']);
        Role::firstOrCreate(['name' => 'USER'], ['label' => 'Regular User', 'description' => 'Platform user']);
        Role::firstOrCreate(['name' => 'VERIFIED_USER'], ['label' => 'Verified User', 'description' => 'Identity verified citizen']);
    }

    protected function createUser(string $name, string $username): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'username' => $username,
            'email' => "{$username}@example.com",
            'status' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => 'Bio for '.$name,
                'avatar_url' => '/images/default-avatar.svg',
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'who_can_see_posts' => 'public',
                'who_can_send_friend_requests' => 'everyone',
                'messenger_sounds_enabled' => true,
                'incoming_call_sound' => 'bondhoo_ring',
                'incoming_call_volume' => 80,
            ]
        );

        return $user;
    }

    protected function createDirectConversation(User $userA, User $userB): Conversation
    {
        $conversation = Conversation::create([
            'type' => Conversation::TYPE_DIRECT,
            'creator_id' => $userA->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $userA->id,
            'role' => 'member',
            'request_status' => 'accepted',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $userB->id,
            'role' => 'member',
            'request_status' => 'accepted',
        ]);

        return $conversation;
    }

    /**
     * Test web call screen renders for conversation participant.
     */
    public function test_call_screen_renders_for_conversation_participant(): void
    {
        $alice = $this->createUser('Alice', 'alice_call_test');
        $bob = $this->createUser('Bob', 'bob_call_test');
        $conversation = $this->createDirectConversation($alice, $bob);

        // Web route: /call/{id}
        $res = $this->actingAs($alice, 'web')->get("/call/{$conversation->id}?type=audio");
        $res->assertStatus(200);
        $res->assertViewIs('calls.show');
        $res->assertViewHas('conversation');
        $res->assertViewHas('peerUser');

        // Web route: /calls/{id} alias
        $resAlias = $this->actingAs($alice, 'web')->get("/calls/{$conversation->id}?type=video");
        $resAlias->assertStatus(200);
        $resAlias->assertViewIs('calls.show');
    }

    /**
     * Test call screen works when passing a Call ID instead of Conversation ID.
     */
    public function test_call_screen_resolves_via_call_id(): void
    {
        $alice = $this->createUser('Alice', 'alice_call_test2');
        $bob = $this->createUser('Bob', 'bob_call_test2');
        $conversation = $this->createDirectConversation($alice, $bob);

        $call = app(CallingService::class)->initiateCall($alice, $conversation, 'audio');

        $res = $this->actingAs($bob, 'web')->get("/calls/{$call->id}");
        $res->assertStatus(200);
        $res->assertViewIs('calls.show');
    }

    /**
     * Test non-participant is forbidden from joining the call.
     */
    public function test_non_participant_forbidden_from_joining_call(): void
    {
        $alice = $this->createUser('Alice', 'alice_call_test3');
        $bob = $this->createUser('Bob', 'bob_call_test3');
        $charlie = $this->createUser('Charlie', 'charlie_intruder');
        $conversation = $this->createDirectConversation($alice, $bob);

        $res = $this->actingAs($charlie, 'web')->get("/call/{$conversation->id}");
        $res->assertStatus(403);
    }

    /**
     * Test call initiation, acceptance, and termination via API.
     */
    public function test_call_lifecycle_via_api(): void
    {
        $alice = $this->createUser('Alice', 'alice_api_caller');
        $bob = $this->createUser('Bob', 'bob_api_callee');
        $conversation = $this->createDirectConversation($alice, $bob);

        Sanctum::actingAs($alice);

        // 1. Initiate audio call
        $initRes = $this->postJson('/api/v1/calls', [
            'conversation_id' => $conversation->id,
            'call_type' => 'audio',
        ]);

        $initRes->assertStatus(201)
            ->assertJsonPath('success', true);

        $callId = $initRes->json('data.id');
        $this->assertNotNull($callId);

        // 2. Bob accepts call
        Sanctum::actingAs($bob);
        $acceptRes = $this->postJson("/api/v1/calls/{$callId}/respond", [
            'action' => 'accept',
        ]);
        $acceptRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');

        // 3. Bob leaves/ends call
        $leaveRes = $this->postJson("/api/v1/calls/{$callId}/leave");
        $leaveRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 4. Check call history API
        $historyRes = $this->getJson('/api/v1/calls/history');
        $historyRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test WebRTC ICE servers configuration endpoint.
     */
    public function test_call_ice_servers_api(): void
    {
        $alice = $this->createUser('Alice', 'alice_ice_test');
        Sanctum::actingAs($alice);

        $res = $this->getJson('/api/v1/calls/ice-servers');
        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'ice_servers',
                ],
            ]);
    }

    /**
     * Test voice note upload and streaming.
     */
    public function test_user_can_send_voice_audio_note_and_stream(): void
    {
        Storage::fake('public');

        $alice = $this->createUser('Alice', 'alice_voice_test');
        $bob = $this->createUser('Bob', 'bob_voice_test');
        $conversation = $this->createDirectConversation($alice, $bob);

        Sanctum::actingAs($alice);

        // Generate synthetic mp3 audio content
        $fakeAudio = UploadedFile::fake()->create('note.mp3', 256, 'audio/mpeg');

        $res = $this->postJson("/api/v1/conversations/{$conversation->id}/voice", [
            'audio' => $fakeAudio,
            'duration' => 12,
            'waveform' => [10, 25, 45, 80, 50, 30, 15],
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'voice');

        $messageId = $res->json('data.id');
        $this->assertNotNull($messageId);

        // Test stream endpoint
        $streamRes = $this->get("/messages/{$messageId}/voice");
        $streamRes->assertStatus(200);
    }

    /**
     * Test Messenger Sound Settings web tab, API retrieval, update, and reset.
     */
    public function test_messenger_sound_settings_web_and_api(): void
    {
        $alice = $this->createUser('Alice', 'alice_sound_test');

        // 1. Web /settings?tab=sounds and /settings/sounds shortcut
        $webRes = $this->actingAs($alice, 'web')->get('/settings/sounds');
        $webRes->assertStatus(200);
        $webRes->assertSee('মেসেঞ্জার সাউন্ড ও রিংটোন সেটিংস');
        $webRes->assertSee('Bondhoo Ring (ডিফল্ট)');

        // 2. API: GET /api/v1/settings/messenger-sounds
        Sanctum::actingAs($alice);
        $getRes = $this->getJson('/api/v1/settings/messenger-sounds');
        $getRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'messenger_sounds_enabled',
                    'incoming_call_sound',
                    'incoming_call_volume',
                    'incoming_message_sound',
                    'outgoing_message_sound',
                    'sound_library',
                ],
            ]);

        // 3. API: PUT /api/v1/settings/messenger-sounds (Update)
        $putRes = $this->putJson('/api/v1/settings/messenger-sounds', [
            'messenger_sounds_enabled' => true,
            'incoming_call_sound' => 'digital_bell',
            'incoming_call_volume' => 95,
            'incoming_message_sound' => 'clear_ding',
            'incoming_message_volume' => 75,
            'outgoing_message_sound' => 'soft_swoosh',
            'outgoing_message_volume' => 55,
        ]);

        $putRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.incoming_call_sound', 'digital_bell')
            ->assertJsonPath('data.incoming_call_volume', 95)
            ->assertJsonPath('data.incoming_message_sound', 'clear_ding');

        // 4. API: POST /api/v1/settings/messenger-sounds/reset (Reset to defaults)
        $resetRes = $this->postJson('/api/v1/settings/messenger-sounds/reset');
        $resetRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.incoming_call_sound', 'bondhoo_ring')
            ->assertJsonPath('data.incoming_call_volume', 80);
    }
}
