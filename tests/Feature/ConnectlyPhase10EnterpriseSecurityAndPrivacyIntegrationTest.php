<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConnectlyPhase10EnterpriseSecurityAndPrivacyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    /**
     * 1. Unauthenticated requests to protected endpoints return 401 Unauthorized.
     */
    public function test_unauthenticated_requests_are_rejected_with_401(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
        $this->getJson('/api/v1/devices')->assertStatus(401);
        $this->getJson('/api/v1/profile/privacy')->assertStatus(401);
        $this->postJson('/api/v1/reports')->assertStatus(401);
        $this->postJson('/api/v1/calls')->assertStatus(401);
    }

    /**
     * 2. Server-side RBAC protects administrative moderation endpoints with 403 Forbidden.
     */
    public function test_server_side_rbac_protects_admin_endpoints_with_403(): void
    {
        $regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'regular@connectly.app',
        ]);

        // Regular user attempt
        Sanctum::actingAs($regularUser);
        $this->getJson('/api/v1/admin/reports')->assertStatus(403);
        $this->getJson('/api/v1/admin/users')->assertStatus(403);

        // Admin user attempt
        $adminUser = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@connectly.app',
        ]);
        $adminUser->assignRole('ADMIN');

        Sanctum::actingAs($adminUser);
        $this->getJson('/api/v1/admin/reports')->assertStatus(200);
        $this->getJson('/api/v1/admin/users')->assertStatus(200);
    }

    /**
     * 3. Session revocation and single token logout invalidates the token.
     */
    public function test_session_revocation_and_single_token_logout(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('SecretPass123!'),
        ]);

        $token = $user->createToken('Pixel 9 Pro (Android)')->plainTextToken;

        // Verify valid access before logout
        $validRes = $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$token,
        ]);
        $validRes->assertStatus(200);

        // Perform logout
        $logoutRes = $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);
        $logoutRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Ensure token was deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);

        // Verify token is no longer accepted
        $rejectedRes = $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$token,
        ]);
        $rejectedRes->assertStatus(401);
    }

    /**
     * 4. Global logout invalidates all active tokens and device sessions across all devices.
     */
    public function test_global_logout_invalidates_all_active_tokens_and_device_sessions(): void
    {
        $user = User::factory()->create();

        $tokenPhone = $user->createToken('Android Phone')->plainTextToken;
        $tokenTablet = $user->createToken('Android Tablet')->plainTextToken;
        $tokenWeb = $user->createToken('Web Browser')->plainTextToken;

        $this->assertEquals(3, PersonalAccessToken::where('tokenable_id', $user->id)->count());

        // Perform logout-all from the phone
        $logoutAllRes = $this->postJson('/api/v1/auth/logout-all', [], [
            'Authorization' => 'Bearer '.$tokenPhone,
        ]);
        $logoutAllRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify all personal access tokens are completely purged
        $this->assertEquals(0, PersonalAccessToken::where('tokenable_id', $user->id)->count());

        // Verify all 3 tokens are rejected with 401
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$tokenPhone])->assertStatus(401);
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$tokenTablet])->assertStatus(401);
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$tokenWeb])->assertStatus(401);
    }

    /**
     * 5. Device session registration and secure revocation by owner.
     */
    public function test_device_session_revocation_removes_specific_device(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Sanctum::actingAs($userA);

        // Register device session for user A
        $registerRes = $this->postJson('/api/v1/devices/register', [
            'device_id' => 'android_unique_uuid_9988',
            'device_name' => 'Pixel 9 Pro Fold',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'push_token' => 'fcm_sample_token_abc',
        ]);
        $registerRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $sessionId = $registerRes->json('data.id');

        // User B attempts to delete User A's session -> rejected with 404
        Sanctum::actingAs($userB);
        $this->deleteJson('/api/v1/devices/'.$sessionId)->assertStatus(404);

        // User A revokes their own device session -> succeeds
        Sanctum::actingAs($userA);
        $deleteRes = $this->deleteJson('/api/v1/devices/'.$sessionId);
        $deleteRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * 6. User blocking and unblocking prevents harassment and ensures safety.
     */
    public function test_user_blocking_and_unblocking_workflow(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Sanctum::actingAs($userA);

        // Block user B
        $blockRes = $this->postJson('/api/v1/users/'.$userB->id.'/block');
        $blockRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Unblock user B
        $unblockRes = $this->deleteJson('/api/v1/users/'.$userB->id.'/block');
        $unblockRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * 7. Privacy settings persistence and strict enum validation.
     */
    public function test_privacy_settings_persistence_and_validation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Retrieve existing/default settings
        $getRes = $this->getJson('/api/v1/profile/privacy');
        $getRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // Update with valid privacy values
        $updateRes = $this->putJson('/api/v1/profile/privacy', [
            'profile_visibility' => 'friends',
            'avatar_privacy' => 'followers',
            'about_privacy' => 'only_me',
        ]);
        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.profile_visibility', 'friends')
            ->assertJsonPath('data.avatar_privacy', 'followers')
            ->assertJsonPath('data.about_privacy', 'only_me');

        // Validation rejects unauthorized enum values
        $invalidRes = $this->putJson('/api/v1/profile/privacy', [
            'profile_visibility' => 'invalid_unrestricted_value',
        ]);
        $invalidRes->assertStatus(422)
            ->assertJsonValidationErrors(['profile_visibility']);
    }

    /**
     * 8. Content reporting workflow with spam/duplicate prevention.
     */
    public function test_content_reporting_workflow_and_duplicate_prevention(): void
    {
        $reporter = User::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        Sanctum::actingAs($reporter);

        // Submit report against post
        $reportRes = $this->postJson('/api/v1/reports', [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'spam',
            'details' => 'Repeated automated spam links found.',
        ]);
        $reportRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Report::STATUS_PENDING);

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'spam',
            'status' => Report::STATUS_PENDING,
        ]);

        // Duplicate pending report from same reporter is rejected
        $duplicateRes = $this->postJson('/api/v1/reports', [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'harassment',
        ]);
        $duplicateRes->assertStatus(422)
            ->assertJsonPath('success', false);

        // Non-existent target returns 404
        $notFoundRes = $this->postJson('/api/v1/reports', [
            'reportable_type' => 'post',
            'reportable_id' => 999999,
            'reason' => 'spam',
        ]);
        $notFoundRes->assertStatus(404);
    }

    /**
     * 9. WebRTC signaling authorization barrier rejects non-participants with 403 Forbidden.
     */
    public function test_webrtc_signaling_authorization_barrier_rejects_non_participants_with_403(): void
    {
        $caller = User::factory()->create(['name' => 'Caller User']);
        $callee = User::factory()->create(['name' => 'Callee User']);
        $intruder = User::factory()->create(['name' => 'Intruder User']);

        Sanctum::actingAs($caller);
        $initiateRes = $this->postJson('/api/v1/calls', [
            'receiver_id' => $callee->id,
            'call_type' => 'audio',
        ]);
        $initiateRes->assertStatus(201);
        $callId = $initiateRes->json('data.id');

        // Intruder (non-participant) attempts to inject a WebRTC signal
        Sanctum::actingAs($intruder);
        $intruderRes = $this->postJson('/api/v1/calls/'.$callId.'/signal', [
            'signal_type' => 'candidate',
            'payload' => ['candidate' => 'malicious_injected_candidate'],
        ]);
        $intruderRes->assertStatus(403);

        // Intruder attempts to read call signals
        $intruderSignalsRes = $this->getJson('/api/v1/calls/'.$callId.'/signals');
        $intruderSignalsRes->assertStatus(403);

        // Authorized caller sends signal successfully
        Sanctum::actingAs($caller);
        $callerRes = $this->postJson('/api/v1/calls/'.$callId.'/signal', [
            'signal_type' => 'candidate',
            'payload' => ['candidate' => 'legitimate_ice_candidate_data'],
        ]);
        $callerRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * 10. File upload security rejects executable scripts and dangerous files masquerading as images.
     */
    public function test_file_upload_security_rejects_executable_or_malicious_files(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Malicious PHP file disguised as image
        $maliciousPhp = UploadedFile::fake()->createWithContent('exploit.php', '<?php phpinfo(); ?>');

        $response = $this->postJson('/api/v1/profile/avatar', [
            'file' => $maliciousPhp,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * 11. Rate limiting protects public authentication endpoints against brute force attacks.
     */
    public function test_auth_rate_limiting_protects_against_brute_force(): void
    {
        $targetEmail = 'victim_'.uniqid().'@connectly.app';

        // Throttle auth is set to 5 requests per minute
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'identifier' => $targetEmail,
                'password' => 'WrongPassword!',
                'device_name' => 'Attacker Tool',
            ]);
        }

        // 6th attempt must trigger HTTP 429 Too Many Requests
        $throttledRes = $this->postJson('/api/v1/auth/login', [
            'identifier' => $targetEmail,
            'password' => 'WrongPassword!',
            'device_name' => 'Attacker Tool',
        ]);

        $throttledRes->assertStatus(429);
    }
}
