<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ProfileVerification;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Step15ProfileVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['label' => 'Super Admin', 'description' => 'Full access']);
        Role::firstOrCreate(['name' => 'ADMIN'], ['label' => 'Admin', 'description' => 'Staff administrator']);
        Role::firstOrCreate(['name' => 'USER'], ['label' => 'Regular User', 'description' => 'Platform user']);
        Role::firstOrCreate(['name' => 'VERIFIED_USER'], ['label' => 'Verified User', 'description' => 'Identity verified citizen']);
    }

    protected function createUser(array $attributes = []): User
    {
        static $counter = 100;
        $counter++;

        $user = User::factory()->create(array_merge([
            'username' => "user_{$counter}",
            'name' => "User {$counter}",
            'email' => "user_{$counter}@example.com",
            'phone' => "+880171100{$counter}",
            'status' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ], $attributes));

        UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'bio' => 'Sample bio',
                'about' => 'Sample about information',
                'is_locked' => false,
            ]
        );

        UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'profile_visibility' => 'public',
            ]
        );

        return $user;
    }

    protected function createAdmin(): User
    {
        $admin = $this->createUser([
            'username' => 'system_admin',
            'email' => 'admin@jugajug.com',
        ]);
        $adminRole = Role::where('name', 'ADMIN')->first();
        $admin->assignRole($adminRole);

        return $admin;
    }

    public function test_user_eligibility_unverified_email_phone_is_ineligible(): void
    {
        $user = $this->createUser([
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/profile/verification/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'eligible' => false,
                    'is_verified' => false,
                    'current_status' => ProfileVerification::STATUS_UNVERIFIED,
                ],
            ]);

        $this->assertStringContainsString('Email or phone must be verified', json_encode($response->json()));
    }

    public function test_user_eligibility_suspended_account_is_ineligible(): void
    {
        $user = $this->createUser([
            'status' => 'suspended',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/profile/verification/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'eligible' => false,
                    'is_verified' => false,
                ],
            ]);

        $this->assertStringContainsString('Account must be active', json_encode($response->json()));
    }

    public function test_active_and_verified_user_is_eligible_for_verification(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/profile/verification/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'eligible' => true,
                    'is_verified' => false,
                    'current_status' => ProfileVerification::STATUS_UNVERIFIED,
                    'can_resubmit' => true,
                ],
            ]);
    }

    public function test_user_can_submit_verification_request_with_documents(): void
    {
        $user = $this->createUser();

        $front = UploadedFile::fake()->image('front_nid.jpg', 800, 600);
        $back = UploadedFile::fake()->image('back_nid.jpg', 800, 600);
        $selfie = UploadedFile::fake()->image('selfie.jpg', 400, 400);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/verification/submit', [
                'verification_type' => ProfileVerification::TYPE_NID,
                'document_number' => '19901234567890',
                'document_front' => $front,
                'document_back' => $back,
                'selfie' => $selfie,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'verification_type' => ProfileVerification::TYPE_NID,
                    'document_number' => '19901234567890',
                    'status' => ProfileVerification::STATUS_PENDING,
                ],
            ]);

        $this->assertDatabaseHas('profile_verifications', [
            'user_id' => $user->id,
            'verification_type' => ProfileVerification::TYPE_NID,
            'status' => ProfileVerification::STATUS_PENDING,
            'document_number' => '19901234567890',
        ]);

        // Assert AuditLog created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'profile.verification_submitted',
            'entity_type' => ProfileVerification::class,
        ]);
    }

    public function test_user_cannot_submit_duplicate_pending_verification(): void
    {
        $user = $this->createUser();

        // First submission
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/verification/submit', [
                'verification_type' => ProfileVerification::TYPE_PASSPORT,
                'document_number' => 'A12345678',
            ])->assertStatus(201);

        // Second submission while pending must fail
        $secondResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/verification/submit', [
                'verification_type' => ProfileVerification::TYPE_PASSPORT,
                'document_number' => 'A12345678',
            ]);

        $secondResponse->assertStatus(422)
            ->assertJsonValidationErrors(['verification']);
    }

    public function test_user_cannot_manually_set_verified_status(): void
    {
        $user = $this->createUser();

        // User attempts to inject status => verified
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/profile/verification/submit', [
                'verification_type' => ProfileVerification::TYPE_NID,
                'document_number' => 'NID-99999',
                'status' => ProfileVerification::STATUS_VERIFIED,
            ]);

        $response->assertStatus(201);
        $this->assertEquals(ProfileVerification::STATUS_PENDING, $response->json('data.status'));

        $this->assertDatabaseHas('profile_verifications', [
            'user_id' => $user->id,
            'status' => ProfileVerification::STATUS_PENDING,
        ]);

        // User has not been verified
        $this->assertFalse($user->fresh()->hasVerifiedProfile());
    }

    public function test_profile_verified_badge_only_appears_when_status_is_verified(): void
    {
        $user = $this->createUser();

        // Unverified user profile check
        $profileRes = $this->getJson("/api/v2/profile/{$user->username}");
        $profileRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'is_verified' => false,
                    ],
                ],
            ]);

        // Create an approved verification
        $admin = $this->createAdmin();
        $verification = ProfileVerification::create([
            'user_id' => $user->id,
            'verification_type' => ProfileVerification::TYPE_NID,
            'status' => ProfileVerification::STATUS_VERIFIED,
            'admin_id' => $admin->id,
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now(),
        ]);

        // Now public profile displays is_verified => true
        $verifiedProfileRes = $this->getJson("/api/v2/profile/{$user->username}");
        $verifiedProfileRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'is_verified' => true,
                        'verification_status' => ProfileVerification::STATUS_VERIFIED,
                    ],
                ],
            ]);
    }

    public function test_non_admin_cannot_access_admin_verification_endpoints(): void
    {
        $user = $this->createUser();

        // Calling index
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/admin/verifications')
            ->assertStatus(403);

        // Calling approve
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/admin/verifications/1/approve')
            ->assertStatus(403);

        // Calling reject
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v2/admin/verifications/1/reject', [
                'rejection_reason' => 'Invalid document format',
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_list_and_view_verification_requests(): void
    {
        $admin = $this->createAdmin();
        $user1 = $this->createUser(['username' => 'applicant_one']);
        $user2 = $this->createUser(['username' => 'applicant_two']);

        $v1 = ProfileVerification::create([
            'user_id' => $user1->id,
            'verification_type' => ProfileVerification::TYPE_NID,
            'document_number' => 'NID-1111',
            'status' => ProfileVerification::STATUS_PENDING,
            'submitted_at' => now()->subHours(2),
        ]);

        $v2 = ProfileVerification::create([
            'user_id' => $user2->id,
            'verification_type' => ProfileVerification::TYPE_PASSPORT,
            'document_number' => 'PASS-2222',
            'status' => ProfileVerification::STATUS_VERIFIED,
            'submitted_at' => now()->subDay(),
        ]);

        // List pending
        $res = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v2/admin/verifications?status=pending');

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $res->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals($v1->id, $data[0]['id']);

        // Show single verification
        $detailRes = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v2/admin/verifications/{$v1->id}");

        $detailRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $v1->id,
                    'document_number' => 'NID-1111',
                ],
            ]);
    }

    public function test_admin_can_approve_verification_request_and_grant_badge(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $verification = ProfileVerification::create([
            'user_id' => $user->id,
            'verification_type' => ProfileVerification::TYPE_NID,
            'document_number' => '123456789',
            'status' => ProfileVerification::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $this->assertFalse($user->hasVerifiedProfile());

        $res = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v2/admin/verifications/{$verification->id}/approve", [
                'admin_notes' => 'Official NID matched citizen database record.',
            ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => ProfileVerification::STATUS_VERIFIED,
                    'admin_id' => $admin->id,
                ],
            ]);

        // User now has verified role & profile badge
        $this->assertTrue($user->fresh()->hasRole('VERIFIED_USER'));
        $this->assertTrue($user->fresh()->hasVerifiedProfile());

        // AuditLog check
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'profile.verification_approved',
            'entity_type' => ProfileVerification::class,
            'entity_id' => $verification->id,
        ]);
    }

    public function test_admin_can_reject_verification_request_with_reason(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $verification = ProfileVerification::create([
            'user_id' => $user->id,
            'verification_type' => ProfileVerification::TYPE_NID,
            'document_number' => '123456789',
            'status' => ProfileVerification::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        // Rejection without reason must fail validation
        $failRes = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v2/admin/verifications/{$verification->id}/reject", []);

        $failRes->assertStatus(422)
            ->assertJsonValidationErrors(['rejection_reason']);

        // Valid rejection
        $res = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v2/admin/verifications/{$verification->id}/reject", [
                'rejection_reason' => 'The provided National ID image is blurred and illegible.',
                'admin_notes' => 'Requested clearer scan.',
            ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => ProfileVerification::STATUS_REJECTED,
                    'rejection_reason' => 'The provided National ID image is blurred and illegible.',
                    'admin_id' => $admin->id,
                ],
            ]);

        $this->assertFalse($user->fresh()->hasVerifiedProfile());

        // AuditLog check
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'profile.verification_rejected',
            'entity_type' => ProfileVerification::class,
            'entity_id' => $verification->id,
        ]);

        // User can now re-submit
        $statusRes = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/profile/verification/status');

        $statusRes->assertStatus(200)
            ->assertJson([
                'data' => [
                    'eligible' => true,
                    'can_resubmit' => true,
                    'current_status' => ProfileVerification::STATUS_REJECTED,
                ],
            ]);
    }

    public function test_admin_can_request_additional_information(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $verification = ProfileVerification::create([
            'user_id' => $user->id,
            'verification_type' => ProfileVerification::TYPE_PASSPORT,
            'document_number' => 'P998877',
            'status' => ProfileVerification::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $res = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v2/admin/verifications/{$verification->id}/request-info", [
                'instructions' => 'Please provide a clear selfie holding your passport signature page.',
            ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => ProfileVerification::STATUS_NEEDS_INFO,
                    'admin_notes' => 'Please provide a clear selfie holding your passport signature page.',
                ],
            ]);

        // AuditLog check
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'profile.verification_info_requested',
            'entity_type' => ProfileVerification::class,
            'entity_id' => $verification->id,
        ]);

        // User sees needs_info in status and can re-submit
        $statusRes = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/profile/verification/status');

        $statusRes->assertStatus(200)
            ->assertJson([
                'data' => [
                    'eligible' => true,
                    'can_resubmit' => true,
                    'current_status' => ProfileVerification::STATUS_NEEDS_INFO,
                ],
            ]);
    }

    public function test_admin_can_revoke_previously_verified_badge(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $verification = ProfileVerification::create([
            'user_id' => $user->id,
            'verification_type' => ProfileVerification::TYPE_NID,
            'document_number' => '123456789',
            'status' => ProfileVerification::STATUS_VERIFIED,
            'admin_id' => $admin->id,
            'submitted_at' => now()->subDays(5),
            'reviewed_at' => now()->subDays(2),
        ]);

        $user->assignRole(Role::where('name', 'VERIFIED_USER')->first());
        $this->assertTrue($user->fresh()->hasVerifiedProfile());

        $res = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v2/admin/verifications/{$verification->id}/revoke", [
                'revocation_reason' => 'Suspected account compromise and fraudulent document reported.',
            ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => ProfileVerification::STATUS_UNVERIFIED,
                ],
            ]);

        $this->assertFalse($user->fresh()->hasVerifiedProfile());
        $this->assertFalse($user->fresh()->hasRole('VERIFIED_USER'));

        // AuditLog check
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'profile.verification_revoked',
            'entity_type' => ProfileVerification::class,
            'entity_id' => $verification->id,
        ]);
    }
}
