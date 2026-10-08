<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\CallParticipant;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebRTCCallingWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;

    protected User $user2;

    protected User $stranger;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->user1 = User::factory()->create([
            'name' => 'মমিনুল হক',
            'username' => 'mominul',
        ]);

        $this->user2 = User::factory()->create([
            'name' => 'সাকিব আল হাসান',
            'username' => 'sakib',
        ]);

        $this->stranger = User::factory()->create([
            'name' => 'অপরিচিত ইউজার',
            'username' => 'stranger',
        ]);

        $this->conversation = Conversation::create([
            'type' => Conversation::TYPE_DIRECT,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user1->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user2->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_call_route(): void
    {
        $response = $this->get("/call/{$this->conversation->id}?type=audio");
        $response->assertRedirect('/login');
    }

    public function test_unauthorized_user_cannot_access_call_room(): void
    {
        $response = $this->actingAs($this->stranger)->get("/call/{$this->conversation->id}?type=audio");
        $response->assertStatus(403);
    }

    public function test_non_existent_conversation_returns_404(): void
    {
        $response = $this->actingAs($this->user1)->get('/call/99999?type=audio');
        $response->assertStatus(404);
    }

    public function test_participant_can_open_audio_call_room_successfully(): void
    {
        $response = $this->actingAs($this->user1)->get("/call/{$this->conversation->id}?type=audio");

        $response->assertStatus(200);
        $response->assertSee('সাকিব আল হাসান');
        $response->assertSee('অডিও কল');
        $response->assertSee('btnToggleMic', false);
        $response->assertSee('btnToggleCam', false);
        $response->assertSee('audioVisualizer', false);
    }

    public function test_participant_can_open_video_call_room_successfully(): void
    {
        $response = $this->actingAs($this->user2)->get("/call/{$this->conversation->id}?type=video");

        $response->assertStatus(200);
        $response->assertSee('মমিনুল হক');
        $response->assertSee('ভিডিও কল');
        $response->assertSee('remoteVideo', false);
        $response->assertSee('localVideo', false);
    }

    public function test_participant_can_send_call_signals_via_conversation_api(): void
    {
        $response = $this->actingAs($this->user1)->postJson("/api/v1/conversations/{$this->conversation->id}/call/signal", [
            'signal_type' => 'offer',
            'call_type' => 'video',
            'payload' => [
                'type' => 'offer',
                'sdp' => 'v=0\r\no=mock 12345 2 IN IP4 127.0.0.1\r\ns=-\r\n',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.signal_type', 'offer');
    }

    public function test_callee_can_open_video_call_room_with_answer_parameter(): void
    {
        $call = Call::create([
            'conversation_id' => $this->conversation->id,
            'caller_id' => $this->user1->id,
            'call_type' => 'video',
            'status' => Call::STATUS_RINGING,
            'started_at' => now(),
        ]);

        CallParticipant::create([
            'call_id' => $call->id,
            'user_id' => $this->user2->id,
            'role' => 'callee',
            'status' => 'ringing',
        ]);

        $response = $this->actingAs($this->user2)->get("/call/{$this->conversation->id}?type=video&answer=1&call_id={$call->id}");

        $response->assertStatus(200);
        $response->assertSee('মমিনুল হক');
        $response->assertSee('ভিডিও কল');
        $response->assertSee('remoteVideo', false);
        $response->assertSee('localVideo', false);
    }
}
