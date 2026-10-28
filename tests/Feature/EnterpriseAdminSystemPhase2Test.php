<?php

namespace Tests\Feature;

use App\Models\AdminAlert;
use App\Models\AuditLog;
use App\Models\PlatformSetting;
use App\Models\Post;
use App\Models\Report;
use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseAdminSystemPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $adminUser;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['description' => 'Super Administrator']);
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN'], ['description' => 'Administrator']);
        Role::firstOrCreate(['name' => 'USER'], ['description' => 'Regular User']);

        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@jugajug.com',
            'status' => 'active',
        ]);
        $this->superAdmin->roles()->attach($superAdminRole);

        $this->adminUser = User::factory()->create([
            'email' => 'admin@jugajug.com',
            'status' => 'active',
        ]);
        $this->adminUser->roles()->attach($adminRole);

        $this->regularUser = User::factory()->create([
            'email' => 'member@jugajug.com',
            'status' => 'active',
        ]);
    }

    /**
     * Test Executive Intelligence Analytics API returns metrics for different time periods.
     */
    public function test_executive_intelligence_analytics_returns_retention_and_dau_metrics(): void
    {
        $response = $this->actingAs($this->superAdmin, 'web')
            ->getJson('/api/v2/admin/enterprise/intelligence?range=14d');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period' => ['range', 'days', 'start_date'],
                    'users' => ['total', 'new_period', 'growth_pct', 'dau', 'wau', 'mau', 'stickiness_pct'],
                    'content' => ['total_posts', 'total_comments', 'total_reactions', 'total_groups', 'total_pages'],
                    'monetization' => ['total_ad_revenue', 'total_payout_approved'],
                    'moderation' => ['pending', 'ai_flagged_count'],
                    'infrastructure' => ['db_latency_ms', 'cache_status'],
                ],
            ]);
    }

    /**
     * Test Streamed CSV Export of Executive Intelligence.
     */
    public function test_streamed_csv_export_downloads_valid_csv(): void
    {
        $response = $this->actingAs($this->superAdmin, 'web')
            ->get('/api/v2/admin/enterprise/intelligence/export?range=30d');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="analytics-intelligence-', (string) $response->headers->get('Content-Disposition'));
    }

    /**
     * Test Bulk Users dry-run preview identifies affected users and detects Super Admin presence.
     */
    public function test_bulk_users_preview_detects_super_admin_and_returns_summary(): void
    {
        $user1 = User::factory()->create(['status' => 'active']);
        $user2 = User::factory()->create(['status' => 'active']);

        // Without super admin
        $response = $this->actingAs($this->superAdmin, 'web')
            ->postJson('/api/v2/admin/enterprise/users/bulk/preview', [
                'user_ids' => [$user1->id, $user2->id],
                'action' => 'suspend',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_affected', 2)
            ->assertJsonPath('data.super_admin_detected', false);

        // With super admin in the list
        $responseWithAdmin = $this->actingAs($this->superAdmin, 'web')
            ->postJson('/api/v2/admin/enterprise/users/bulk/preview', [
                'user_ids' => [$user1->id, $this->superAdmin->id],
                'action' => 'suspend',
            ]);

        $responseWithAdmin->assertOk()
            ->assertJsonPath('data.super_admin_detected', true)
            ->assertJsonPath('data.super_admin_count', 1);
    }

    /**
     * Test Super Admin demotion protection prevents suspending or demoting the last Super Admin.
     */
    public function test_super_admin_demotion_protection_prevents_lockout(): void
    {
        // Try suspending the only super admin
        $response = $this->actingAs($this->superAdmin, 'web')
            ->postJson("/api/v2/admin/enterprise/users/{$this->superAdmin->id}/action", [
                'action' => 'suspend',
                'duration_days' => 7,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        // Ensure super admin was NOT suspended
        $this->superAdmin->refresh();
        $this->assertNotEquals('suspended', $this->superAdmin->status);
    }

    /**
     * Test AI Moderation content scanning heuristic detection and scoring.
     */
    public function test_ai_moderation_scans_content_and_generates_evidence(): void
    {
        $post = Post::create([
            'user_id' => $this->regularUser->id,
            'content' => 'Claim your bKash lottery free recharge now! Click here to earn 5000 daily!',
            'status' => 'published',
        ]);

        $report = Report::create([
            'reporter_id' => $this->adminUser->id,
            'reportable_id' => $post->id,
            'reportable_type' => Post::class,
            'reason' => 'scam_phishing',
            'details' => 'Suspicious phishing message',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->superAdmin, 'web')
            ->postJson("/api/v2/admin/enterprise/moderation/{$report->id}/scan");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ai_flagged', true);

        $report->refresh();
        $this->assertTrue((bool) $report->ai_flagged);
        $this->assertGreaterThan(0.8, (float) $report->ai_confidence);
        $this->assertNotEmpty($report->ai_evidence);
    }

    /**
     * Test Moderation appeal submission and resolution (Overturn).
     */
    public function test_moderation_appeal_submission_and_resolution(): void
    {
        $post = Post::create([
            'user_id' => $this->regularUser->id,
            'content' => 'Sample post for appeal review',
            'status' => 'hidden',
        ]);

        $report = Report::create([
            'reporter_id' => $this->adminUser->id,
            'reportable_id' => $post->id,
            'reportable_type' => Post::class,
            'reason' => 'misunderstood_content',
            'status' => 'actioned',
        ]);

        // 1. Submit appeal
        $appealRes = $this->actingAs($this->adminUser, 'web')
            ->postJson("/api/v2/admin/enterprise/moderation/{$report->id}/appeal", [
                'reason' => 'My post did not violate community standards.',
            ]);

        $appealRes->assertOk();
        $report->refresh();
        $this->assertEquals(Report::APPEAL_REQUESTED, $report->appeal_status);

        // 2. Resolve appeal (Overturn & restore content)
        $resolveRes = $this->actingAs($this->superAdmin, 'web')
            ->postJson("/api/v2/admin/enterprise/moderation/{$report->id}/resolve-appeal", [
                'decision' => 'overturn',
                'notes' => 'Content verified as safe.',
            ]);

        $resolveRes->assertOk();
        $report->refresh();
        $this->assertEquals(Report::APPEAL_OVERTURNED, $report->appeal_status);

        $post->refresh();
        $this->assertEquals('published', $post->status);
    }

    /**
     * Test Security Operations Center (SOC) overview and threat incident resolution.
     */
    public function test_soc_overview_and_incident_resolution(): void
    {
        $event = SecurityEvent::create([
            'user_id' => $this->regularUser->id,
            'event_type' => 'excessive_failed_logins',
            'severity' => SecurityEvent::SEVERITY_HIGH,
            'ip_address' => '192.168.1.100',
            'resolved' => false,
        ]);

        // 1. Get SOC Overview
        $overviewRes = $this->actingAs($this->superAdmin, 'web')
            ->getJson('/api/v2/admin/enterprise/security/incidents');

        $overviewRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.high_count', 1);

        // 2. Resolve Incident
        $resolveRes = $this->actingAs($this->superAdmin, 'web')
            ->postJson("/api/v2/admin/enterprise/security/incidents/{$event->id}/resolve", [
                'notes' => 'IP was investigated and verified as legitimate user error.',
            ]);

        $resolveRes->assertOk();
        $event->refresh();
        $this->assertTrue((bool) $event->resolved);
        $this->assertEquals($this->superAdmin->id, $event->resolved_by);
        $this->assertNotNull($event->resolved_at);
    }

    /**
     * Test Platform Setting Instant Rollback.
     */
    public function test_platform_setting_instant_rollback_restores_previous_value(): void
    {
        // 1. Set initial value
        PlatformSetting::set('site_name', 'Initial Bondhoo', $this->superAdmin->id);

        // 2. Update value (which records history)
        PlatformSetting::set('site_name', 'New Updated Name', $this->superAdmin->id);
        $this->assertEquals('New Updated Name', PlatformSetting::get('site_name'));

        // 3. Trigger rollback via API
        $rollbackRes = $this->actingAs($this->superAdmin, 'web')
            ->postJson('/api/v2/admin/enterprise/settings/rollback', [
                'key' => 'site_name',
            ]);

        $rollbackRes->assertOk()
            ->assertJsonPath('success', true);

        // 4. Verify value was rolled back to initial
        $this->assertEquals('Initial Bondhoo', PlatformSetting::get('site_name'));
    }

    /**
     * Test Admin Alerts retrieval and mark as read.
     */
    public function test_admin_alerts_feed_and_marking_read(): void
    {
        $alert = AdminAlert::trigger(
            type: 'system',
            title: 'High CPU Utilization',
            message: 'Server CPU exceeded 90%',
            severity: AdminAlert::SEVERITY_WARNING
        );

        $this->assertNotNull($alert);

        // 1. Get alerts
        $alertsRes = $this->actingAs($this->superAdmin, 'web')
            ->getJson('/api/v2/admin/enterprise/alerts');

        $alertsRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread_count', 1);

        // 2. Mark single alert as read
        $readRes = $this->actingAs($this->superAdmin, 'web')
            ->postJson("/api/v2/admin/enterprise/alerts/{$alert->id}/read");

        $readRes->assertOk();
        $alert->refresh();
        $this->assertNotNull($alert->read_at);

        // 3. Mark all read
        $readAllRes = $this->actingAs($this->superAdmin, 'web')
            ->postJson('/api/v2/admin/enterprise/alerts/read-all');

        $readAllRes->assertOk();
    }

    /**
     * Test Cryptographic Tamper-Evident Audit Chain integrity verification.
     */
    public function test_cryptographic_tamper_evident_audit_chain_verification(): void
    {
        // Create an audit log entry (which automatically calculates checksum and previous_hash)
        AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'admin_system_health_check',
            'entity_type' => User::class,
            'entity_id' => $this->superAdmin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($this->superAdmin, 'web')
            ->getJson('/api/v2/admin/enterprise/audit/verify-integrity');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.verified', true);

        $this->assertGreaterThanOrEqual(1, (int) $response->json('data.total_checked'));
    }
}
