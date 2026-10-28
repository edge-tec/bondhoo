<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Backup;
use App\Models\PlatformSetting;
use App\Models\PrivacyRequest;
use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\Admin\EnterpriseAccessControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterpriseAdminSystemPhase5ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $standardAdmin;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        app(EnterpriseAccessControlService::class)->syncStandardPermissions();

        $superAdminRole = Role::where('name', 'SUPER_ADMIN')->firstOrFail();
        $adminRole = Role::where('name', 'ADMIN')->firstOrFail();

        $this->superAdmin = User::factory()->create([
            'name' => 'Phase 5 Super Admin',
            'email' => 'superadmin-p5@jugajug.com',
            'username' => 'superadmin_p5',
            'status' => 'active',
            'two_factor_enabled' => true,
        ]);
        $this->superAdmin->roles()->attach($superAdminRole);

        $this->standardAdmin = User::factory()->create([
            'name' => 'Phase 5 Standard Admin',
            'email' => 'admin-p5@jugajug.com',
            'username' => 'admin_p5',
            'status' => 'active',
            'two_factor_enabled' => false,
        ]);
        $this->standardAdmin->roles()->attach($adminRole);

        $this->regularUser = User::factory()->create([
            'name' => 'Phase 5 Regular User',
            'email' => 'user-p5@jugajug.com',
            'username' => 'user_p5',
            'status' => 'active',
        ]);
    }

    public function test_production_readiness_audit_detects_unsafe_config_and_never_leaks_secrets(): void
    {
        $secretAppKey = (string) config('app.key');
        config([
            'app.env' => 'testing',
            'app.debug' => true,
            'database.connections.sqlite.password' => 'SUPER_SECRET_DB_PASSWORD_NEVER_LEAK',
        ]);

        $res = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/production-readiness/run');

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.overall_status', 'fail');

        $checks = collect($res->json('data.checks'))->keyBy('id');

        // Unsafe APP_ENV and APP_DEBUG=true must be reported as critical failures
        $this->assertSame('fail', $checks['app_env_production']['status']);
        $this->assertSame('fail', $checks['app_debug_disabled']['status']);

        // APP_KEY check must pass without exposing the actual APP_KEY or DB password in the payload
        $this->assertSame('pass', $checks['app_key_valid']['status']);
        $rawBody = $res->getContent();
        $this->assertStringNotContainsString($secretAppKey, (string) $rawBody);
        $this->assertStringNotContainsString('SUPER_SECRET_DB_PASSWORD_NEVER_LEAK', (string) $rawBody);

        // Artisan command also executes and outputs structured JSON
        $this->artisan('admin:production-readiness', ['--json' => true])
            ->assertExitCode(1);
    }

    public function test_disaster_recovery_backup_creation_verification_and_isolated_restore_drill(): void
    {
        // 1. Before any backup/drill, platform must NOT claim dr_ready = true
        $initialDr = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/disaster-recovery');
        $initialDr->assertStatus(200)
            ->assertJsonPath('data.dr_ready', false);

        // 2. Create a real encrypted database backup
        $createRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/backups', [
                'type' => 'db',
                'disk' => 'local',
            ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.cipher', 'AES-256-CBC+HMAC-SHA256');

        $backupId = (int) $createRes->json('data.id');
        $this->assertNotEmpty($createRes->json('data.backup_uid'));
        $this->assertNotEmpty($createRes->json('data.checksum'));

        // Still not dr_ready because no restore drill has been run yet
        $midDr = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/disaster-recovery');
        $midDr->assertJsonPath('data.dr_ready', false);

        // 3. Verify backup checksum & cryptographic envelope
        $verifyRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/disaster-recovery/backups/{$backupId}/verify");
        $verifyRes->assertStatus(200)
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('data.checksum_match', true)
            ->assertJsonPath('data.decryption_ok', true)
            ->assertJsonPath('data.manifest_ok', true);

        // 4. Execute Isolated Restore Drill (restores into isolated temporary SQLite DB, verifies schema/counts/PRAGMA, cleans up temp files)
        $liveUsersBefore = User::count();
        $drillRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/disaster-recovery/backups/{$backupId}/restore-drill");

        $drillRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'passed')
            ->assertJsonPath('data.checksum_verified', true)
            ->assertJsonPath('data.decryption_verified', true)
            ->assertJsonPath('data.schema_verified', true)
            ->assertJsonPath('data.relationships_verified', true)
            ->assertJsonPath('data.isolated_resources_cleaned', true);

        $this->assertGreaterThan(0, (int) $drillRes->json('data.restored_tables_count'));
        $this->assertGreaterThan(0, (int) $drillRes->json('data.restored_records_count'));
        $this->assertSame($liveUsersBefore, User::count());

        // 5. Now that a verified backup exists, RPO is met, AND a real restore drill passed, dr_ready MUST be true!
        $finalDr = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/disaster-recovery');
        $finalDr->assertStatus(200)
            ->assertJsonPath('data.dr_ready', true)
            ->assertJsonPath('data.compliance.rpo_compliant', true)
            ->assertJsonPath('data.compliance.rto_compliant', true)
            ->assertJsonPath('data.compliance.has_passed_restore_drill', true);

        // 6. CLI command verification (`admin:backup-verify --latest --drill`)
        $this->artisan('admin:backup-verify', ['--latest' => true, '--drill' => true])
            ->assertExitCode(0);
    }

    public function test_corrupted_backup_detection_prevents_verification_and_preserves_live_data(): void
    {
        $createRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/backups', [
                'type' => 'db',
                'disk' => 'local',
            ]);
        $backupId = (int) $createRes->json('data.id');
        $filename = (string) $createRes->json('data.filename');

        // Inject corruption into the stored encrypted backup artifact
        Storage::disk('local')->put($filename, 'CORRUPTED_CIPHERTEXT_PAYLOAD_TAMPERED');

        // Verification must fail (422) and mark backup as corrupted
        $verifyRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/disaster-recovery/backups/{$backupId}/verify");
        $verifyRes->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.verified', false)
            ->assertJsonPath('data.backup.verification_status', Backup::VERIFICATION_CORRUPTED);

        // Restore drill on corrupted backup must fail (422) without touching live data
        $liveUsersBefore = User::count();
        $drillRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/disaster-recovery/backups/{$backupId}/restore-drill");
        $drillRes->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.isolated_resources_cleaned', true);

        $this->assertSame($liveUsersBefore, User::count());
        $this->assertSame(Backup::VERIFICATION_CORRUPTED, Backup::findOrFail($backupId)->verification_status);
    }

    public function test_duplicate_concurrent_backup_prevention_and_backup_authorization_controls(): void
    {
        // 1. Simulate an active running backup lock
        $lock = Cache::lock('jugajug_backup_running_db', 60);
        $this->assertTrue($lock->get());

        try {
            $dupRes = $this->actingAs($this->superAdmin, 'sanctum')
                ->postJson('/api/v2/admin/enterprise/disaster-recovery/backups', [
                    'type' => 'db',
                    'disk' => 'local',
                ]);
            $dupRes->assertStatus(409)
                ->assertJsonPath('success', false);
        } finally {
            $lock->release();
        }

        // 2. Create a valid backup as SUPER_ADMIN
        $backup = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/backups', ['type' => 'db'])
            ->json('data');

        // 3. Standard ADMIN (not SUPER_ADMIN) cannot run restore drills, download backups, delete backups, or change RPO/RTO objectives
        $this->actingAs($this->standardAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/disaster-recovery/backups/{$backup['id']}/restore-drill")
            ->assertStatus(403);

        $this->actingAs($this->standardAdmin, 'sanctum')
            ->get("/api/v2/admin/enterprise/disaster-recovery/backups/{$backup['id']}/download")
            ->assertStatus(403);

        $this->actingAs($this->standardAdmin, 'sanctum')
            ->deleteJson("/api/v2/admin/enterprise/disaster-recovery/backups/{$backup['id']}", [
                'reason' => 'Unauthorized delete attempt',
            ])
            ->assertStatus(403);

        $this->actingAs($this->standardAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/objectives', [
                'rpo_minutes' => 15,
            ])
            ->assertStatus(403);

        // 4. SUPER_ADMIN can download and delete with mandatory reason (and both are audited)
        $this->actingAs($this->superAdmin, 'sanctum')
            ->get("/api/v2/admin/enterprise/disaster-recovery/backups/{$backup['id']}/download")
            ->assertStatus(200);

        $this->actingAs($this->superAdmin, 'sanctum')
            ->deleteJson("/api/v2/admin/enterprise/disaster-recovery/backups/{$backup['id']}", [
                'reason' => 'Routine retention cleanup after drill verification',
            ])
            ->assertStatus(200);

        $this->assertDatabaseMissing('backups', ['id' => $backup['id']]);
    }

    public function test_mfa_enforcement_for_privileged_operations_and_v1_role_escalation_prevention(): void
    {
        // Enable mandatory admin MFA for privileged operations
        PlatformSetting::set('security_require_admin_mfa', 'true', $this->superAdmin->id);

        $superWithoutMfa = User::factory()->create([
            'email' => 'super-no-mfa@jugajug.com',
            'status' => 'active',
            'two_factor_enabled' => false,
        ]);
        $superWithoutMfa->assignRole('SUPER_ADMIN');

        // 1. SUPER_ADMIN without 2FA enabled is blocked (403 MFA_REQUIRED) from high-impact privileged endpoints
        $this->actingAs($superWithoutMfa, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/roles/elevate', [
                'user_id' => $this->regularUser->id,
                'elevated_role' => 'ADMIN',
                'duration_minutes' => 30,
                'reason' => 'Testing MFA gate',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'MFA_REQUIRED');

        $this->actingAs($superWithoutMfa, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/governance/legal-hold', [
                'user_id' => $this->regularUser->id,
                'is_legal_hold' => true,
                'reason' => 'Testing MFA gate on legal hold',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'MFA_REQUIRED');

        // 2. Enabling MFA on the account immediately permits the privileged operation
        $superWithoutMfa->update(['two_factor_enabled' => true]);
        $this->actingAs($superWithoutMfa->fresh(), 'sanctum')
            ->postJson('/api/v2/admin/enterprise/governance/legal-hold', [
                'user_id' => $this->regularUser->id,
                'is_legal_hold' => true,
                'reason' => 'MFA enabled and verified',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.is_legal_hold', true);

        // 3. Verify legacy /api/v1/admin/users/{id}/roles blocks privilege escalation by standard ADMIN
        $this->actingAs($this->standardAdmin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$this->standardAdmin->id}/roles", [
                'role' => 'SUPER_ADMIN',
            ])
            ->assertStatus(403);

        $this->actingAs($this->standardAdmin, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$this->superAdmin->id}/roles", [
                'role' => 'SUPER_ADMIN',
            ])
            ->assertStatus(403);
    }

    public function test_audit_log_immutability_and_audit_verify_cli_command(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'immutable_audit_record_test',
            'entity_type' => User::class,
            'entity_id' => $this->superAdmin->id,
            'ip_address' => '127.0.0.1',
        ]);

        // Attempting to modify an existing AuditLog via Eloquent must throw InvalidArgumentException
        $updateBlocked = false;
        try {
            $log->update(['action' => 'tampered_action']);
        } catch (\InvalidArgumentException) {
            $updateBlocked = true;
        }
        $this->assertTrue($updateBlocked, 'AuditLog must prevent Eloquent updates.');

        // Attempting to delete an existing AuditLog via Eloquent must throw InvalidArgumentException
        $deleteBlocked = false;
        try {
            $log->delete();
        } catch (\InvalidArgumentException) {
            $deleteBlocked = true;
        }
        $this->assertTrue($deleteBlocked, 'AuditLog must prevent Eloquent deletion.');

        // Verify CLI command admin:audit-verify --checkpoint
        $this->artisan('admin:audit-verify', ['--limit' => 50, '--checkpoint' => true])
            ->assertExitCode(0);
    }

    public function test_soc_incident_lifecycle_scheduler_heartbeat_and_privacy_export_expiration(): void
    {
        // 1. Scheduler Heartbeat detection
        Cache::forget('scheduler_last_heartbeat_at');
        $socBefore = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/security');
        $socBefore->assertStatus(200)
            ->assertJsonPath('data.operational_telemetry.scheduler_missed_heartbeat', true);

        $this->artisan('admin:scheduler-heartbeat')->assertExitCode(0);

        $socAfter = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/security');
        $socAfter->assertStatus(200)
            ->assertJsonPath('data.operational_telemetry.scheduler_missed_heartbeat', false);

        // 2. SOC Incident Lifecycle Transitions (open -> acknowledged -> investigating -> escalated -> resolved -> reopened)
        $incident = SecurityEvent::create([
            'user_id' => $this->regularUser->id,
            'event_type' => 'suspicious_token_replay',
            'severity' => SecurityEvent::SEVERITY_HIGH,
            'ip_address' => '203.0.113.55',
            'resolved' => false,
            'status' => SecurityEvent::STATUS_OPEN,
        ]);

        foreach (['acknowledged', 'investigating', 'escalated', 'resolved', 'reopened'] as $nextStatus) {
            $transRes = $this->actingAs($this->superAdmin, 'sanctum')
                ->postJson("/api/v2/admin/enterprise/security/incidents/{$incident->id}/transition", [
                    'status' => $nextStatus,
                    'notes' => "Transitioned to {$nextStatus}",
                    'evidence_refs' => ["EV-{$nextStatus}"],
                ]);
            $transRes->assertStatus(200)
                ->assertJsonPath('data.status', $nextStatus);
        }

        $updatedIncident = $incident->fresh();
        $this->assertCount(5, $updatedIncident->timeline);
        $this->assertContains('EV-escalated', $updatedIncident->evidence_refs);

        // 3. Privacy Export Access & Expiration Enforcement
        $exportPath = 'privacy-exports/user_export_test.json';
        Storage::disk('local')->put($exportPath, '{"user_id":'.$this->regularUser->id.'}');

        $privacyReq = PrivacyRequest::create([
            'request_number' => 'PRV-EXP-001',
            'user_id' => $this->regularUser->id,
            'request_type' => PrivacyRequest::TYPE_EXPORT,
            'status' => PrivacyRequest::STATUS_COMPLETED,
            'export_file_path' => $exportPath,
            'export_expires_at' => now()->addHours(2),
        ]);

        // Valid (non-expired) export can be downloaded by authorized admin
        $this->actingAs($this->superAdmin, 'sanctum')
            ->get("/api/v2/admin/enterprise/governance/privacy-requests/{$privacyReq->id}/download")
            ->assertStatus(200);

        // Once expired, download returns 410 Gone and deletes the temporary export file
        $privacyReq->update(['export_expires_at' => now()->subMinutes(5)]);
        $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v2/admin/enterprise/governance/privacy-requests/{$privacyReq->id}/download")
            ->assertStatus(410)
            ->assertJsonPath('error_code', 'EXPORT_EXPIRED');

        $this->assertFalse(Storage::disk('local')->exists($exportPath));
    }
}
