<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Conversation;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnterpriseSecurityAndPrivacyHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. Test Unauthenticated Requests are Strictly Blocked (Sanctum Protection).
     */
    public function test_unauthenticated_requests_are_strictly_blocked(): void
    {
        $this->postJson('/api/v1/posts', ['content' => 'Test'])->assertStatus(401);
        $this->getJson('/api/v1/profile')->assertStatus(401);
        $this->getJson('/api/v1/conversations')->assertStatus(401);
        $this->getJson('/api/v1/devices')->assertStatus(401);
        $this->getJson('/api/v1/notifications')->assertStatus(401);
    }

    /**
     * 2. Test Administrator Endpoints Forbid Regular Clients (Role & Permission Protection).
     */
    public function test_admin_endpoints_forbid_regular_clients(): void
    {
        $regularUser = User::factory()->create();

        // Regular user accessing admin routes gets 403 Forbidden
        $response = $this->actingAs($regularUser, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/overview');

        $this->assertTrue(in_array($response->status(), [403, 401], true));
    }

    /**
     * 3. Test Token and Session Revocation on Account Logout.
     */
    public function test_token_and_session_revocation_on_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('android_auth_token');
        $plainToken = $token->plainTextToken;

        // Register device
        $deviceRes = $this->withHeader('Authorization', "Bearer {$plainToken}")
            ->postJson('/api/v1/devices/register', [
                'device_id' => 'device_sec_test_123',
                'device_name' => 'Secured Pixel',
                'platform' => 'android',
                'push_token' => 'fcm_token_sec',
            ]);
        $deviceRes->assertStatus(200);

        // Perform Logout
        $logoutRes = $this->withHeader('Authorization', "Bearer {$plainToken}")
            ->postJson('/api/v1/auth/logout');
        $logoutRes->assertStatus(200);

        // Token is deleted from DB
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    /**
     * 4. Test User Blocking & Privacy Isolation.
     */
    public function test_user_blocking_and_privacy_isolation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // User A blocks User B
        $blockRes = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/friends/{$userB->id}/block");
        $blockRes->assertStatus(200);

        // User B searches or inspects relationship status
        $statusRes = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/v1/friends/status/{$userA->id}");
        $statusRes->assertStatus(200);
        $this->assertTrue($statusRes->json('is_blocked'));
    }

    /**
     * 5. Test File Upload Validation & Malicious Extension Rejection.
     */
    public function test_file_upload_validation_rejects_malicious_files(): void
    {
        $user = User::factory()->create();

        // Try uploading a malicious shell script as avatar photo
        $fakeScript = UploadedFile::fake()->create('malicious.sh', 50, 'application/x-sh');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile/avatar', [
                'file' => $fakeScript,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    /**
     * 6. Test Content Reporting & Deduplication Against Abuse.
     */
    public function test_content_reporting_and_deduplication(): void
    {
        $reporter = User::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        // Submit Report
        $reportRes = $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'spam',
                'details' => 'Unsolicited promotional spam.',
            ]);
        $reportRes->assertStatus(201);
        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_id' => $post->id,
            'reason' => 'spam',
        ]);

        // Duplicate report returns 422
        $dupRes = $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/v1/reports', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'spam',
            ]);
        $dupRes->assertStatus(422);
    }

    /**
     * 7. Test WebRTC Signaling Rejects Unauthorized Non-Participants.
     */
    public function test_webrtc_signaling_rejects_unauthorized_parties(): void
    {
        $caller = User::factory()->create();
        $receiver = User::factory()->create();
        $intruder = User::factory()->create();

        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $caller->id,
        ]);

        $call = Call::create([
            'room_id' => 'room_sec_test',
            'conversation_id' => $conversation->id,
            'caller_id' => $caller->id,
            'call_type' => 'video',
            'status' => 'ringing',
        ]);

        $call->participants()->create([
            'user_id' => $caller->id,
            'role' => 'caller',
            'status' => 'joined',
        ]);
        $call->participants()->create([
            'user_id' => $receiver->id,
            'role' => 'receiver',
            'status' => 'ringing',
        ]);

        // Intruder attempts to send signaling data into call
        $intruderRes = $this->actingAs($intruder, 'sanctum')
            ->postJson("/api/v1/calls/{$call->id}/signal", [
                'signal_type' => 'offer',
                'payload' => ['sdp' => 'fake_sdp_payload'],
            ]);

        $this->assertTrue(in_array($intruderRes->status(), [403, 422], true));
    }
}
