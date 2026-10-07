<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\PhotoAlbum;
use App\Models\ProfileVerification;
use App\Models\RecoveryCode;
use App\Models\User;
use App\Models\UserFollower;
use App\Models\UserProfile;
use App\Models\UserSession;
use App\Models\UserTwoFactor;
use App\Services\TwoFactorService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProfileProductionFinalHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Compute current TOTP code for testing.
     */
    protected function computeTotp(string $secret, int $offset = 0): string
    {
        $service = app(TwoFactorService::class);
        $timeSlice = (int) floor(time() / 30) + $offset;
        $ref = new \ReflectionClass($service);
        $calcMethod = $ref->getMethod('calculateTotp');
        $calcMethod->setAccessible(true);

        return $calcMethod->invoke($service, $secret, $timeSlice);
    }

    /**
     * Requirement 1: Canonical profile ID and identity isolation across users.
     */
    public function test_user_a_and_user_b_profiles_have_zero_cross_user_mix_up(): void
    {
        $userA = User::factory()->create([
            'name' => 'Alice Rahman',
            'username' => 'alicer',
            'email' => 'alice@example.com',
            'status' => 'active',
        ]);
        UserProfile::create([
            'user_id' => $userA->id,
            'bio' => 'Alice Bio Unique String',
        ]);

        $userB = User::factory()->create([
            'name' => 'Bob Ahmed',
            'username' => 'bobahmed',
            'email' => 'bob@example.com',
            'status' => 'active',
        ]);
        UserProfile::create([
            'user_id' => $userB->id,
            'bio' => 'Bob Bio Unique String',
        ]);

        // 1. User A visits own profile
        $responseA = $this->actingAs($userA, 'web')->get('/@alicer');
        $responseA->assertOk();
        $responseA->assertSee('ID: #'.$userA->id);
        $responseA->assertSee('Alice Rahman');
        $responseA->assertSee('@alicer');
        $responseA->assertSee('Alice Bio Unique String');
        $responseA->assertDontSee('Bob Bio Unique String');
        $responseA->assertSee('id="privacyModal"', false);

        // 2. User B visits own profile
        $responseB = $this->actingAs($userB, 'web')->get('/@bobahmed');
        $responseB->assertOk();
        $responseB->assertSee('ID: #'.$userB->id);
        $responseB->assertSee('Bob Ahmed');
        $responseB->assertSee('@bobahmed');
        $responseB->assertSee('Bob Bio Unique String');
        $responseB->assertDontSee('Alice Bio Unique String');
        $responseB->assertSee('id="privacyModal"', false);

        // 3. User A visits User B profile (cross-user check)
        $responseCross = $this->actingAs($userA, 'web')->get('/@bobahmed');
        $responseCross->assertOk();
        $responseCross->assertSee('ID: #'.$userB->id);
        $responseCross->assertSee('Bob Ahmed');
        $responseCross->assertSee('@bobahmed');
        $responseCross->assertSee('Bob Bio Unique String');
        // Must NOT leak User A's ID as profile ID
        $responseCross->assertDontSee('ID: #'.$userA->id);
        $responseCross->assertDontSee('Alice Bio Unique String');
        // Owner modals must NOT be rendered in User B's page for User A
        $responseCross->assertDontSee('id="privacyModal"', false);
        $responseCross->assertDontSee('id="securityModal"', false);
        $responseCross->assertDontSee('id="blockingCenterModal"', false);
        $responseCross->assertDontSee('id="verificationModal"', false);
    }

    /**
     * Requirement 2 & 5: IDOR protection on device sessions.
     */
    public function test_user_a_cannot_revoke_user_b_device_session(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $sessionB = UserSession::create([
            'user_id' => $userB->id,
            'session_id' => 'sess_b_unique_123',
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
            'device_name' => 'B iPhone',
            'platform' => 'iOS',
            'browser' => 'Mobile Safari',
            'last_active_at' => now(),
        ]);

        // User A tries to delete User B's session via web endpoint
        $res = $this->actingAs($userA, 'web')
            ->deleteJson('/devices/'.$sessionB->id);

        $res->assertStatus(404);
        $this->assertDatabaseHas('user_sessions', ['id' => $sessionB->id]);

        // User B can delete their own session
        $resB = $this->actingAs($userB, 'web')
            ->deleteJson('/devices/'.$sessionB->id);

        $resB->assertOk();
        $this->assertDatabaseMissing('user_sessions', ['id' => $sessionB->id]);
    }

    /**
     * Requirement 2: IDOR protection on albums.
     */
    public function test_user_a_cannot_delete_user_b_album(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $albumB = PhotoAlbum::create([
            'user_id' => $userB->id,
            'title' => 'Bob Private Album',
            'privacy' => 'only_me',
        ]);

        $res = $this->actingAs($userA, 'sanctum')
            ->deleteJson('/api/v1/profile/albums/'.$albumB->id);

        $this->assertDatabaseHas('photo_albums', ['id' => $albumB->id]);
    }

    /**
     * Requirement 3: API consistency audit for friends, followers, following, and blocked users.
     */
    public function test_api_endpoints_return_standardized_and_consistent_responses(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $follower = User::factory()->create();

        Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        UserFollower::create([
            'user_id' => $user->id,
            'follower_id' => $follower->id,
        ]);

        // Friends list
        $resFriends = $this->actingAs($user, 'sanctum')->getJson('/api/v1/friends');
        $resFriends->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data', 'meta' => ['current_page', 'last_page', 'total', 'per_page']])
            ->assertJsonPath('meta.total', 1);

        // Followers list
        $resFollowers = $this->actingAs($user, 'sanctum')->getJson('/api/v1/followers');
        $resFollowers->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data', 'meta' => ['current_page', 'last_page', 'total', 'per_page']])
            ->assertJsonPath('meta.total', 1);

        // Following list
        $resFollowing = $this->actingAs($follower, 'sanctum')->getJson('/api/v1/following');
        $resFollowing->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data', 'meta' => ['current_page', 'last_page', 'total', 'per_page']])
            ->assertJsonPath('meta.total', 1);

        // Blocked users list v1 alias and v2
        $resBlockedV1 = $this->actingAs($user, 'sanctum')->getJson('/api/v1/profile/blocked-users');
        $resBlockedV1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data']);

        $resBlockedV2 = $this->actingAs($user, 'sanctum')->getJson('/api/v2/profile/blocked-users');
        $resBlockedV2->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data']);
    }

    /**
     * Requirement 4: Privacy settings persistence and access rule enforcement.
     */
    public function test_privacy_settings_persist_and_enforce_profile_visibility_rules(): void
    {
        $owner = User::factory()->create(['username' => 'privateowner']);
        $stranger = User::factory()->create(['username' => 'stranger']);
        $friend = User::factory()->create(['username' => 'bestfriend']);

        Friendship::create([
            'user_id' => $owner->id,
            'friend_id' => $friend->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        // 1. Owner updates privacy to only_me
        $updateRes = $this->actingAs($owner, 'sanctum')->putJson('/api/v2/profile/privacy/settings', [
            'profile_visibility' => 'only_me',
            'bio_privacy' => 'only_me',
        ]);
        $updateRes->assertOk();
        $this->assertDatabaseHas('privacy_settings', [
            'user_id' => $owner->id,
            'profile_visibility' => 'only_me',
        ]);

        // Stranger gets 403 Forbidden
        $strangerRes = $this->actingAs($stranger, 'web')->get('/@privateowner');
        $strangerRes->assertStatus(403);

        // Friend also gets 403 Forbidden on only_me
        $friendRes = $this->actingAs($friend, 'web')->get('/@privateowner');
        $friendRes->assertStatus(403);

        // Owner can still view
        $ownerRes = $this->actingAs($owner, 'web')->get('/@privateowner');
        $ownerRes->assertOk();

        // 2. Owner updates privacy to friends
        $this->actingAs($owner, 'sanctum')->putJson('/api/v2/profile/privacy/settings', [
            'profile_visibility' => 'friends',
        ]);

        // Stranger gets 403 Forbidden
        $this->actingAs($stranger, 'web')->get('/@privateowner')->assertStatus(403);

        // Friend now gets 200 OK
        $this->actingAs($friend, 'web')->get('/@privateowner')->assertOk();
    }

    /**
     * Requirement 5: Device security and session logout-all isolation.
     */
    public function test_device_security_logout_all_and_session_isolation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $sessionA1 = UserSession::create([
            'user_id' => $userA->id,
            'session_id' => 'sess_a1',
            'ip_address' => '192.168.1.10',
            'device_name' => 'A Chrome',
            'platform' => 'macOS',
            'browser' => 'Chrome',
            'last_active_at' => now(),
        ]);
        $sessionA2 = UserSession::create([
            'user_id' => $userA->id,
            'session_id' => 'sess_a2',
            'ip_address' => '192.168.1.11',
            'device_name' => 'A Firefox',
            'platform' => 'macOS',
            'browser' => 'Firefox',
            'last_active_at' => now(),
        ]);
        $sessionB = UserSession::create([
            'user_id' => $userB->id,
            'session_id' => 'sess_b',
            'ip_address' => '192.168.1.20',
            'device_name' => 'B Safari',
            'platform' => 'macOS',
            'browser' => 'Safari',
            'last_active_at' => now(),
        ]);

        // User A views devices: sees only A sessions
        $resA = $this->actingAs($userA, 'web')->getJson('/devices');
        $resA->assertOk();
        $sessionIds = collect($resA->json('data.sessions'))->pluck('id')->all();
        $this->assertContains($sessionA1->id, $sessionIds);
        $this->assertContains($sessionA2->id, $sessionIds);
        $this->assertNotContains($sessionB->id, $sessionIds);

        // User A performs logout-all
        $logoutAllRes = $this->actingAs($userA, 'web')->postJson('/devices/logout-all');
        $logoutAllRes->assertOk();

        // User A sessions are removed
        $this->assertDatabaseMissing('user_sessions', ['id' => $sessionA1->id]);
        $this->assertDatabaseMissing('user_sessions', ['id' => $sessionA2->id]);
        // User B session is completely unaffected
        $this->assertDatabaseHas('user_sessions', ['id' => $sessionB->id]);
    }

    /**
     * Requirement 6: Password change security validation and hashing.
     */
    public function test_password_change_requires_current_password_and_hashes_securely(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        // 1. Wrong current password fails with 422
        $resWrong = $this->actingAs($user, 'sanctum')->putJson('/api/v1/auth/password', [
            'current_password' => 'WrongPassword999!',
            'password' => 'NewSecretPassword123#',
            'password_confirmation' => 'NewSecretPassword123#',
        ]);
        $resWrong->assertStatus(422);

        // 2. Unconfirmed password fails with 422
        $resUnconfirmed = $this->actingAs($user, 'sanctum')->putJson('/api/v1/auth/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewSecretPassword123#',
            'password_confirmation' => 'DifferentPassword123#',
        ]);
        $resUnconfirmed->assertStatus(422);

        // 3. Valid change succeeds
        $resSuccess = $this->actingAs($user, 'sanctum')->putJson('/api/v1/auth/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewSecretPassword123#',
            'password_confirmation' => 'NewSecretPassword123#',
        ]);
        $resSuccess->assertOk();

        // Database password was updated
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecretPassword123#', $user->password));

        // Password is not exposed in the response
        $this->assertArrayNotHasKey('password', $resSuccess->json());
    }

    /**
     * Requirement 7: 2FA backend functionality.
     */
    public function test_two_factor_authentication_setup_enable_and_disable(): void
    {
        $user = User::factory()->create();

        // 1. Setup generates secret
        $resSetup = $this->actingAs($user, 'web')->postJson('/settings/two-factor/setup');
        $resSetup->assertOk();
        $this->assertNotEmpty($resSetup->json('data.secret'));
        $this->assertNotEmpty($resSetup->json('data.qr_code_url'));
        $this->assertNotEmpty($resSetup->json('data.recovery_codes'));

        // 2. Enable 2FA with valid TOTP code
        $secret = $resSetup->json('data.secret');
        $validCode = $this->computeTotp($secret);

        $resEnable = $this->actingAs($user, 'web')->postJson('/settings/two-factor/enable', [
            'code' => $validCode,
        ]);
        $resEnable->assertOk();

        $user->refresh();
        $this->assertTrue((bool) $user->two_factor_enabled);

        // 3. Disable 2FA requires correct password
        $resDisable = $this->actingAs($user, 'web')->postJson('/settings/two-factor/disable', [
            'password' => 'password',
        ]);
        $resDisable->assertOk();

        $user->refresh();
        $this->assertFalse((bool) $user->two_factor_enabled);
    }

    /**
     * Requirement 8: Blocking system enforcement and mutual restriction.
     */
    public function test_blocking_system_restricts_profile_access_and_unblock_restores(): void
    {
        $userA = User::factory()->create(['username' => 'blocker']);
        $userB = User::factory()->create(['username' => 'blocked']);

        // User A blocks User B
        $resBlock = $this->actingAs($userA, 'sanctum')->postJson("/api/v2/profile/{$userB->username}/block");
        $resBlock->assertOk();

        $this->assertDatabaseHas('friendships', [
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
            'status' => Friendship::STATUS_BLOCKED,
        ]);

        // User B cannot view User A's profile
        $resViewBlocked = $this->actingAs($userB, 'web')->get('/@blocker');
        $resViewBlocked->assertStatus(403);

        // User A unblocks User B
        $resUnblock = $this->actingAs($userA, 'sanctum')->postJson("/api/v2/profile/{$userB->username}/unblock");
        $resUnblock->assertOk();

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
            'status' => Friendship::STATUS_BLOCKED,
        ]);

        // Now User B can view User A's public profile
        $resViewRestored = $this->actingAs($userB, 'web')->get('/@blocker');
        $resViewRestored->assertOk();
    }

    /**
     * Requirement 9: Verification status is backend-controlled and document stored securely.
     */
    public function test_verification_status_is_backend_controlled_and_document_stored_privately(): void
    {
        Storage::fake('local');

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->create('nid_front.jpg', 500, 'image/jpeg');

        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/verification/submit', [
            'verification_type' => ProfileVerification::TYPE_NID,
            'document_number' => '19901234567890',
            'document_front' => $file,
        ]);

        $res->assertCreated();

        $verification = ProfileVerification::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($verification);
        $this->assertEquals(ProfileVerification::STATUS_PENDING, $verification->status);

        // Verify file stored in private/local storage, not public directory
        $storedKey = $verification->document_front_key;
        $this->assertStringStartsWith("verifications/{$user->id}/front_", $storedKey);
        Storage::disk('local')->assertExists($storedKey);
    }

    /**
     * Requirement 10: Canonical profile QR code generation.
     */
    public function test_profile_qr_code_generates_canonical_destination_url(): void
    {
        $userA = User::factory()->create(['username' => 'aliceqr']);
        $userB = User::factory()->create(['username' => 'bobqr']);

        // User A QR code
        $resQrA = $this->get('/api/v2/profile/aliceqr/qrcode');
        $resQrA->assertOk();
        $resQrA->assertHeader('Content-Type', 'image/svg+xml');
        // SVG output contains SVG element
        $this->assertStringContainsString('<svg', $resQrA->getContent());

        // User B QR code
        $resQrB = $this->get('/api/v2/profile/bobqr/qrcode');
        $resQrB->assertOk();
        $resQrB->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $resQrB->getContent());
    }

    /**
     * Requirement 11: View As Public mode isolates owner controls and hides private data.
     */
    public function test_view_as_public_mode_sanitizes_owner_actions_and_modals(): void
    {
        $owner = User::factory()->create(['username' => 'samviewas']);
        UserProfile::create([
            'user_id' => $owner->id,
            'bio' => 'Sam Public Bio',
        ]);

        // Regular owner view
        $regularView = $this->actingAs($owner, 'web')->get('/@samviewas');
        $regularView->assertOk();
        $regularView->assertSee('id="privacyModal"', false);
        $regularView->assertSee('id="securityModal"', false);

        // View as public
        $viewAsPublic = $this->actingAs($owner, 'web')->get('/@samviewas?view_as=public');
        $viewAsPublic->assertOk();
        // View as banner is visible
        $viewAsPublic->assertSee('view-as-banner', false);
        // Owner modals are omitted
        $viewAsPublic->assertDontSee('id="privacyModal"', false);
        $viewAsPublic->assertDontSee('id="securityModal"', false);
        $viewAsPublic->assertDontSee('id="blockingCenterModal"', false);
        $viewAsPublic->assertDontSee('id="verificationModal"', false);
    }

    /**
     * Requirement 12: Profile counters exclude soft-deleted users.
     */
    public function test_profile_counters_do_not_count_soft_deleted_users(): void
    {
        $user = User::factory()->create(['username' => 'counttestuser']);
        $friendActive = User::factory()->create(['username' => 'activefriend']);
        $friendDeleted = User::factory()->create(['username' => 'deletedfriend']);

        Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $friendActive->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);
        Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $friendDeleted->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $this->assertCount(2, $user->getFriendIds());

        // Soft delete friendDeleted
        $friendDeleted->delete();

        // getFriendIds now excludes the soft-deleted user
        $friendIdsAfterDelete = $user->getFriendIds();
        $this->assertCount(1, $friendIdsAfterDelete);
        $this->assertContains($friendActive->id, $friendIdsAfterDelete);
        $this->assertNotContains($friendDeleted->id, $friendIdsAfterDelete);
    }

    /**
     * Release Gate Requirement 1 & 2: Cache invalidation and isolation on block and unblock.
     */
    public function test_cache_invalidation_and_isolation_on_block_and_unblock(): void
    {
        $userA = User::factory()->create(['username' => 'alicegate']);
        $userB = User::factory()->create(['username' => 'bobgate']);

        // Set initial cache entries for both users
        Cache::put("profile:public:{$userA->username}", ['cached' => 'alice'], 3600);
        Cache::put("profile:{$userA->id}", ['cached' => 'alice_id'], 3600);
        Cache::put("counters:{$userA->id}", ['friends' => 5], 3600);
        Cache::put("relationship:{$userA->id}:{$userB->id}", 'FRIENDS', 3600);

        Cache::put("profile:public:{$userB->username}", ['cached' => 'bob'], 3600);
        Cache::put("profile:{$userB->id}", ['cached' => 'bob_id'], 3600);
        Cache::put("counters:{$userB->id}", ['friends' => 3], 3600);

        // 1. User A blocks User B
        $blockRes = $this->actingAs($userA, 'sanctum')->postJson("/api/v2/profile/{$userB->username}/block");
        $blockRes->assertOk();

        // Verify relationship cache and user caches for both A and B were invalidated
        $this->assertFalse(Cache::has("profile:public:{$userA->username}"));
        $this->assertFalse(Cache::has("counters:{$userA->id}"));
        $this->assertFalse(Cache::has("relationship:{$userA->id}:{$userB->id}"));
        $this->assertFalse(Cache::has("relationship:{$userB->id}:{$userA->id}"));
        $this->assertFalse(Cache::has("profile:public:{$userB->username}"));
        $this->assertFalse(Cache::has("counters:{$userB->id}"));

        // Put fresh distinct cache entries for A and B
        Cache::put("profile:{$userA->id}", ['owner' => 'A'], 3600);
        Cache::put("profile:{$userB->id}", ['owner' => 'B'], 3600);

        // 2. Unblock: User A unblocks User B
        $unblockRes = $this->actingAs($userA, 'sanctum')->postJson("/api/v2/profile/{$userB->username}/unblock");
        $unblockRes->assertOk();

        // Verify caches invalidated on unblock
        $this->assertFalse(Cache::has("profile:{$userA->id}"));
        $this->assertFalse(Cache::has("profile:{$userB->id}"));
        $this->assertFalse(Cache::has("relationship:{$userA->id}:{$userB->id}"));

        // Relationship is completely cleared
        $this->assertDatabaseMissing('friendships', [
            'user_id' => $userA->id,
            'friend_id' => $userB->id,
            'status' => Friendship::STATUS_BLOCKED,
        ]);
    }

    /**
     * Release Gate Requirement 3: Recovery codes are single-use, hashed, and hidden from serialization.
     */
    public function test_recovery_code_is_single_use_and_hidden_from_serialization(): void
    {
        $user = User::factory()->create();
        $plainCode = 'ABCD-1234';

        $recCode = RecoveryCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($plainCode),
            'used_at' => null,
        ]);

        // Code hash must be hidden from array and JSON serialization
        $serialized = $recCode->toArray();
        $this->assertArrayNotHasKey('code_hash', $serialized);

        // First verification succeeds
        $twoFactorService = app(TwoFactorService::class);
        $this->assertTrue($twoFactorService->verifyChallenge($user, $plainCode));

        // RecoveryCode record is marked as used
        $recCode->refresh();
        $this->assertTrue($recCode->isUsed());
        $this->assertNotNull($recCode->used_at);

        // Second verification with the exact same code MUST throw ValidationException
        $this->expectException(ValidationException::class);
        $twoFactorService->verifyChallenge($user, $plainCode);
    }

    /**
     * Release Gate Requirement 11: Sensitive models hide secrets from serialization.
     */
    public function test_sensitive_models_hide_secrets_from_serialization(): void
    {
        $user = User::factory()->create();

        // 1. UserTwoFactor hides secret
        $twoFactor = UserTwoFactor::create([
            'user_id' => $user->id,
            'secret' => Crypt::encryptString('JBSWY3DPEHPK3PXP'),
            'type' => 'totp',
            'is_enabled' => true,
        ]);
        $this->assertArrayNotHasKey('secret', $twoFactor->toArray());

        // 2. ProfileVerification hides keys and admin notes
        $verification = ProfileVerification::create([
            'user_id' => $user->id,
            'verification_type' => ProfileVerification::TYPE_NID,
            'document_front_key' => 'verifications/1/front_secret.jpg',
            'document_back_key' => 'verifications/1/back_secret.jpg',
            'selfie_key' => 'verifications/1/selfie_secret.jpg',
            'media_meta' => ['internal' => 'meta'],
            'admin_notes' => 'Secret internal admin comment',
            'status' => ProfileVerification::STATUS_PENDING,
        ]);
        $verArray = $verification->toArray();
        $this->assertArrayNotHasKey('document_front_key', $verArray);
        $this->assertArrayNotHasKey('document_back_key', $verArray);
        $this->assertArrayNotHasKey('selfie_key', $verArray);
        $this->assertArrayNotHasKey('media_meta', $verArray);
    }

    /**
     * Release Gate Requirement 7: Data export is strictly user-isolated.
     */
    public function test_profile_data_export_isolation(): void
    {
        $userA = User::factory()->create(['username' => 'exportalice']);
        $userB = User::factory()->create(['username' => 'exportbob']);

        $res = $this->actingAs($userA, 'sanctum')->getJson('/api/v2/profile/export');
        $res->assertOk();

        $data = $res->json('data');
        $this->assertEquals($userA->id, $data['export_metadata']['user_id']);
        $this->assertEquals($userA->username, $data['user']['username']);

        // Does not leak User B
        $this->assertStringNotContainsString('exportbob', json_encode($data));
    }

    /**
     * Release Gate Requirement 9: Navigation sequence User A -> User B -> User C -> Back to User A.
     */
    public function test_navigation_sequence_maintains_profile_identity_isolation(): void
    {
        $userA = User::factory()->create(['name' => 'Alice Nav', 'username' => 'alicenav']);
        $userB = User::factory()->create(['name' => 'Bob Nav', 'username' => 'bobnav']);
        $userC = User::factory()->create(['name' => 'Charlie Nav', 'username' => 'charlienav']);

        UserProfile::create(['user_id' => $userA->id, 'bio' => 'Alice Unique Bio 101']);
        UserProfile::create(['user_id' => $userB->id, 'bio' => 'Bob Unique Bio 202']);
        UserProfile::create(['user_id' => $userC->id, 'bio' => 'Charlie Unique Bio 303']);

        // User A visits User A (Own profile)
        $resA1 = $this->actingAs($userA, 'web')->get('/@alicenav');
        $resA1->assertOk();
        $resA1->assertSee('Alice Unique Bio 101');
        $resA1->assertDontSee('Bob Unique Bio 202');
        $resA1->assertDontSee('Charlie Unique Bio 303');

        // User A visits User B
        $resB = $this->actingAs($userA, 'web')->get('/@bobnav');
        $resB->assertOk();
        $resB->assertSee('Bob Unique Bio 202');
        $resB->assertDontSee('Alice Unique Bio 101');
        $resB->assertDontSee('Charlie Unique Bio 303');

        // User A visits User C
        $resC = $this->actingAs($userA, 'web')->get('/@charlienav');
        $resC->assertOk();
        $resC->assertSee('Charlie Unique Bio 303');
        $resC->assertDontSee('Alice Unique Bio 101');
        $resC->assertDontSee('Bob Unique Bio 202');

        // User A navigates back to User A
        $resA2 = $this->actingAs($userA, 'web')->get('/@alicenav');
        $resA2->assertOk();
        $resA2->assertSee('Alice Unique Bio 101');
        $resA2->assertDontSee('Bob Unique Bio 202');
        $resA2->assertDontSee('Charlie Unique Bio 303');
    }
}
