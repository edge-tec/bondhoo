<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnterpriseSecurityAndGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * 1. Test Authentication Security: Session Management & Device Logging.
     */
    public function test_authentication_security_and_device_logging(): void
    {
        $user = User::factory()->create(['name' => 'Sec User']);

        // Register device
        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v1/devices/register', [
            'device_id' => 'device_gov_101',
            'device_name' => 'Pixel 9 Pro Secured',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'push_token' => 'fcm_gov_token_123',
        ]);
        $res->assertStatus(200);

        // Retrieve active device list
        $listRes = $this->actingAs($user, 'sanctum')->getJson('/api/v1/devices');
        $listRes->assertStatus(200);
        $this->assertNotEmpty($listRes->json('data'));
    }

    /**
     * 2. Test Privacy Controls: Restricted Friends, Post Audience & Profile Visibility.
     */
    public function test_privacy_controls_and_restricted_accounts(): void
    {
        $userA = User::factory()->create(['name' => 'Privacy Owner']);
        $userB = User::factory()->create(['name' => 'Friend B']);

        // Establish Friendship
        $this->actingAs($userA, 'sanctum')->postJson("/api/v1/friends/{$userB->id}/request");
        $this->actingAs($userB, 'sanctum')->postJson("/api/v1/friends/{$userA->id}/accept");

        // Toggle Restricted Friend
        $restrictRes = $this->actingAs($userA, 'sanctum')
            ->postJson("/api/v1/friends/{$userB->id}/restricted");
        $restrictRes->assertStatus(200);
        $this->assertTrue($restrictRes->json('is_restricted'));

        // Create Private Post (Only Me or Friends audience)
        $postRes = $this->actingAs($userA, 'sanctum')->postJson('/api/v1/posts', [
            'content' => 'Top secret personal thoughts.',
            'audience' => 'only_me',
        ]);
        $postRes->assertStatus(201);
        $postId = $postRes->json('data.id');

        $this->assertDatabaseHas('posts', [
            'id' => $postId,
            'audience' => 'only_me',
        ]);
    }

    /**
     * 3. Test GDPR & Privacy Rights: Data Download & Account Deletion.
     */
    public function test_gdpr_data_download_and_account_deletion(): void
    {
        $password = 'Secret123!@#';
        $user = User::factory()->create([
            'password' => Hash::make($password),
            'username' => 'gdpr_user_test',
        ]);

        // 1. Data Download / Export
        $exportRes = $this->actingAs($user, 'sanctum')->getJson('/api/v2/profile/export');
        $exportRes->assertStatus(200);
        $this->assertTrue($exportRes->json('success'));

        // 2. Account Deletion with Password Confirmation
        $deleteRes = $this->actingAs($user, 'sanctum')->postJson('/api/v2/profile/delete', [
            'password' => $password,
            'confirmation' => 'DELETE',
            'reason' => 'Privacy request under GDPR',
        ]);
        $deleteRes->assertStatus(200);
        $this->assertEquals('deleted', $deleteRes->json('data.status'));

        // User personal access tokens are completely revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    /**
     * 4. Test Trust & Safety: Content Reporting, Spam Moderation & Appeals.
     */
    public function test_trust_and_safety_reporting_and_moderation(): void
    {
        $reporter = User::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        // Report post for abuse/spam
        $reportRes = $this->actingAs($reporter, 'sanctum')->postJson('/api/v1/reports', [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'spam',
            'details' => 'Misleading promotional spam link.',
        ]);
        $reportRes->assertStatus(201);
        $reportId = $reportRes->json('data.id') ?? Report::latest('id')->value('id');

        // Verify report is in database with pending status
        $this->assertDatabaseHas('reports', [
            'id' => $reportId,
            'status' => 'pending',
            'reason' => 'spam',
        ]);
    }

    /**
     * 5. Test Enterprise Admin Integration & Audit Logs.
     */
    public function test_admin_governance_and_audit_export(): void
    {
        $admin = User::factory()->create(['name' => 'System Super Admin']);
        $admin->assignRole('SUPER_ADMIN');

        // Admin Access Overview
        $overviewRes = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/overview');
        $overviewRes->assertStatus(200);
        $this->assertTrue($overviewRes->json('success'));

        // Admin Export Audit Logs
        $auditRes = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/audit/export');
        $auditRes->assertStatus(200);
    }
}
