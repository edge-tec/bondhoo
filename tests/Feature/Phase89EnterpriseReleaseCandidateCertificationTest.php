<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Post;
use App\Models\Reel;
use App\Models\User;
use App\Services\BackupService;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase89EnterpriseReleaseCandidateCertificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $peer;

    protected User $outsider;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Release Candidate User',
            'username' => 'rc_user_89',
            'email' => 'rc89@example.com',
        ]);

        $this->peer = User::factory()->create([
            'name' => 'Peer Colleague',
            'username' => 'peer_89',
            'email' => 'peer89@example.com',
        ]);

        $this->outsider = User::factory()->create([
            'name' => 'Outsider Attacker',
            'username' => 'outsider_89',
            'email' => 'outsider89@example.com',
        ]);

        $messengerService = app(MessengerServiceInterface::class);
        $this->conversation = $messengerService->getOrCreateDirectConversation($this->user, $this->peer->id);
    }

    /**
     * Test 1: Core Social Networking — Post creation, feed visibility, reaction and comment.
     */
    public function test_core_social_networking_workflow(): void
    {
        // 1. Create post
        $postRes = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'JUGAJUG Release Candidate 8.9 Social Post',
            'privacy' => 'public',
        ]);
        $postRes->assertStatus(201);
        $postId = $postRes->json('data.id');
        $this->assertNotNull($postId);

        // 2. React to post
        $reactRes = $this->actingAs($this->peer, 'sanctum')->postJson("/api/v1/posts/{$postId}/react", [
            'type' => 'like',
        ]);
        $reactRes->assertStatus(200);

        // 3. Comment on post
        $commentRes = $this->actingAs($this->peer, 'sanctum')->postJson("/api/v1/posts/{$postId}/comments", [
            'body' => 'Congratulations on the Release Candidate!',
        ]);
        $commentRes->assertStatus(201);
    }

    /**
     * Test 2: Reels Engine — Feed pagination, public visibility, and privacy boundary.
     */
    public function test_reels_engine_privacy_and_feed(): void
    {
        Reel::create([
            'user_id' => $this->user->id,
            'caption' => 'Public Showcase Reel',
            'video_url' => 'https://example.com/videos/reel1.mp4',
            'thumbnail_url' => 'https://example.com/videos/reel1_thumb.webp',
            'duration' => 15,
            'privacy' => 'public',
            'status' => 'ready',
        ]);

        Reel::create([
            'user_id' => $this->user->id,
            'caption' => 'Private Draft Reel',
            'video_url' => 'https://example.com/videos/reel2.mp4',
            'thumbnail_url' => 'https://example.com/videos/reel2_thumb.webp',
            'duration' => 10,
            'privacy' => 'only_me',
            'status' => 'ready',
        ]);

        $feedRes = $this->actingAs($this->peer, 'sanctum')->getJson('/api/v2/reels?per_page=10');
        $feedRes->assertStatus(200);

        $captions = collect($feedRes->json('data'))->pluck('caption')->all();
        $this->assertContains('Public Showcase Reel', $captions);
        $this->assertNotContains('Private Draft Reel', $captions, 'Private only_me reel must not appear in peer feed');
    }

    /**
     * Test 3: Messenger Real-Time & Secure Message Identifier Verification.
     */
    public function test_messenger_secure_identifier_and_idempotency(): void
    {
        $secureClientUuid = 'msg_'.Str::uuid()->toString();

        // 1. Initial message send
        $sendRes = $this->actingAs($this->user, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Release candidate real-time verification',
            'client_message_id' => $secureClientUuid,
        ]);
        $sendRes->assertStatus(201);
        $msgId = $sendRes->json('data.id');

        // 2. Retry with same UUID (flaky connection)
        $retryRes = $this->actingAs($this->user, 'sanctum')->postJson("/api/v1/conversations/{$this->conversation->id}/messages", [
            'body' => 'Release candidate real-time verification',
            'client_message_id' => $secureClientUuid,
        ]);
        $retryRes->assertStatus(201);
        $this->assertEquals($msgId, $retryRes->json('data.id'), 'Idempotent retry must return the exact same message ID');

        // 3. Verify exactly 1 message in database
        $this->assertEquals(
            1,
            Message::where('conversation_id', $this->conversation->id)
                ->where('metadata->client_message_id', $secureClientUuid)
                ->count()
        );
    }

    /**
     * Test 4: WebRTC Calling Authorization Enforcement.
     */
    public function test_webrtc_calling_participant_authorization(): void
    {
        // Outsider cannot initiate call in user & peer conversation
        $unauthRes = $this->actingAs($this->outsider, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'call_type' => 'video',
        ]);
        $unauthRes->assertStatus(403);

        // Authorized user can initiate call
        $authRes = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/calls', [
            'conversation_id' => $this->conversation->id,
            'receiver_id' => $this->peer->id,
            'call_type' => 'video',
        ]);
        $authRes->assertStatus(201);
    }

    /**
     * Test 5: Infrastructure & Health Check Verification.
     */
    public function test_infrastructure_health_check_endpoint(): void
    {
        $response = $this->getJson('/api/v1/health');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'status',
                'services' => [
                    'database' => ['status'],
                    'storage' => ['status'],
                    'queue' => ['status'],
                ],
                'timestamp',
            ],
        ]);
    }

    /**
     * Test 6: Disaster Recovery Key Rotation & Restore Integrity.
     */
    public function test_disaster_recovery_certification(): void
    {
        $backupService = app(BackupService::class);
        $drill = $backupService->runKeyRotationAndRecoveryDrill();

        $this->assertTrue($drill['passed']);
        $this->assertEquals('AUTOMATED_AND_DRILL_VERIFIED', $drill['gate_d_status']);
    }
}
