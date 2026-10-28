<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\AutomatedResponseAction;
use App\Models\ComplianceControl;
use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\ThreatDetection;
use App\Models\User;
use App\Models\ZeroTrustPolicy;
use App\Services\Admin\EnterpriseAccessControlService;
use App\Services\Audit\TamperEvidentAuditService;
use App\Services\Security\ThreatDetectionAndResponseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterpriseAdminSystemPhase6ZeroTrustTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $secondSuperAdmin;

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
            'name' => 'Phase 6 Primary Super Admin',
            'email' => 'superadmin1-p6@jugajug.com',
            'username' => 'superadmin1_p6',
            'status' => 'active',
            'two_factor_enabled' => true,
        ]);
        $this->superAdmin->roles()->attach($superAdminRole);

        $this->secondSuperAdmin = User::factory()->create([
            'name' => 'Phase 6 Secondary Super Admin',
            'email' => 'superadmin2-p6@jugajug.com',
            'username' => 'superadmin2_p6',
            'status' => 'active',
            'two_factor_enabled' => true,
        ]);
        $this->secondSuperAdmin->roles()->attach($superAdminRole);

        $this->standardAdmin = User::factory()->create([
            'name' => 'Phase 6 Standard Admin',
            'email' => 'admin-p6@jugajug.com',
            'username' => 'admin_p6',
            'status' => 'active',
            'two_factor_enabled' => false,
        ]);
        $this->standardAdmin->roles()->attach($adminRole);

        $this->regularUser = User::factory()->create([
            'name' => 'Phase 6 Regular User',
            'email' => 'user-p6@jugajug.com',
            'username' => 'user_p6',
            'status' => 'active',
        ]);
    }

    public function test_zero_trust_policy_engine_enforces_deny_by_default_mfa_risk_and_dual_approval(): void
    {
        // 1. Overview seeds default versioned Zero-Trust policies
        $overviewRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/zero-trust/overview');

        $overviewRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertGreaterThanOrEqual(5, $overviewRes->json('data.summary.total_policies'));

        // 2. Unmapped resource/action fails closed with DENY_BY_DEFAULT_NO_MATCHING_POLICY
        $unmappedRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/zero-trust/evaluate', [
                'resource' => 'unmapped_secret_vault',
                'action' => 'purge_everything',
                'context' => ['mfa_verified' => true],
            ]);

        $unmappedRes->assertStatus(200)
            ->assertJsonPath('data.allowed', false)
            ->assertJsonPath('data.decision', 'DENY')
            ->assertJsonPath('data.reason_code', 'DENY_BY_DEFAULT_NO_MATCHING_POLICY');

        // 3. Standard admin without MFA attempting a policy requiring MFA triggers STEP_UP_MFA_REQUIRED
        $mfaRes = $this->actingAs($this->standardAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/zero-trust/evaluate', [
                'resource' => 'security',
                'action' => 'policy_manage',
                'context' => ['mfa_verified' => false],
            ]);

        $mfaRes->assertStatus(200)
            ->assertJsonPath('data.allowed', false);

        // 4. Super Admin with high risk score (> max_risk_score) is denied
        $riskRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/zero-trust/evaluate', [
                'resource' => 'security',
                'action' => 'policy_manage',
                'context' => [
                    'mfa_verified' => true,
                    'jit_active' => true,
                    'auth_age_minutes' => 2,
                    'risk_score' => 95,
                ],
            ]);

        $riskRes->assertStatus(200)
            ->assertJsonPath('data.allowed', false)
            ->assertJsonPath('data.decision', 'DENY')
            ->assertJsonPath('data.reason_code', 'RISK_THRESHOLD_EXCEEDED');

        // 5. Dual-approval policy without second approver returns APPROVAL_REQUIRED; self-approval is rejected
        $selfApproveRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/zero-trust/evaluate', [
                'resource' => 'disaster_recovery',
                'action' => 'destructive_change',
                'context' => [
                    'mfa_verified' => true,
                    'jit_active' => true,
                    'auth_age_minutes' => 1,
                    'risk_score' => 5,
                    'approved_by_user_id' => $this->superAdmin->id,
                ],
            ]);

        $selfApproveRes->assertStatus(200)
            ->assertJsonPath('data.allowed', false)
            ->assertJsonPath('data.decision', 'APPROVAL_REQUIRED')
            ->assertJsonPath('data.reason_code', 'DUAL_APPROVAL_REQUIRED');

        // 6. Dual-approval policy WITH independent second Super Admin approver is ALLOWED
        $dualApprovedRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/zero-trust/evaluate', [
                'resource' => 'disaster_recovery',
                'action' => 'destructive_change',
                'context' => [
                    'mfa_verified' => true,
                    'jit_active' => true,
                    'auth_age_minutes' => 1,
                    'risk_score' => 5,
                    'approved_by_user_id' => $this->secondSuperAdmin->id,
                ],
            ]);

        $dualApprovedRes->assertStatus(200)
            ->assertJsonPath('data.allowed', true)
            ->assertJsonPath('data.decision', 'ALLOW')
            ->assertJsonPath('data.reason_code', 'POLICY_SATISFIED');

        // 7. Policy update increments version and records audit trail
        $policy = ZeroTrustPolicy::where('policy_key', 'ZT-AUDIT-EXPORT-01')->firstOrFail();
        $initialVersion = $policy->version;

        $updateRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->putJson("/api/v2/admin/enterprise/zero-trust/policies/{$policy->id}", [
                'max_risk_score' => 35,
                'require_trusted_device' => true,
                'reason' => 'Tighten audit export risk threshold and require trusted device signal',
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.version', $initialVersion + 1)
            ->assertJsonPath('data.max_risk_score', 35)
            ->assertJsonPath('data.require_trusted_device', true);

        // 8. Unverified device trust signal fails closed when policy requires trusted device
        $untrustedDeviceRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/zero-trust/evaluate', [
                'resource' => 'audit',
                'action' => 'export',
                'context' => [
                    'mfa_verified' => true,
                    'auth_age_minutes' => 2,
                    'risk_score' => 10,
                    'device_trusted' => false,
                ],
            ]);

        $untrustedDeviceRes->assertStatus(200)
            ->assertJsonPath('data.allowed', false)
            ->assertJsonPath('data.decision', 'DENY')
            ->assertJsonPath('data.reason_code', 'UNTRUSTED_OR_UNVERIFIED_DEVICE_CONTEXT');
    }

    public function test_session_security_workspace_uses_safe_handles_and_never_exposes_raw_session_ids(): void
    {
        $rawSessionSecretId = 'RAW_SECRET_SESSION_ID_998877665544332211';

        DB::table('sessions')->insert([
            'id' => $rawSessionSecretId,
            'user_id' => $this->standardAdmin->id,
            'ip_address' => '203.0.113.55',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) Chrome/125.0',
            'payload' => base64_encode(serialize(['_token' => 'test'])),
            'last_activity' => now()->timestamp,
        ]);

        $token = $this->standardAdmin->createToken('Admin CLI Token');
        $rawPlainTextToken = $token->plainTextToken;

        $res = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/session-security');

        $res->assertStatus(200)
            ->assertJsonPath('success', true);

        $rawContent = (string) $res->getContent();
        $this->assertStringNotContainsString($rawSessionSecretId, $rawContent);
        $this->assertStringNotContainsString($rawPlainTextToken, $rawContent);

        $sessions = collect($res->json('data.sessions'));
        $adminSession = $sessions->firstWhere('user_id', $this->standardAdmin->id);
        $this->assertNotNull($adminSession);
        $this->assertStringStartsWith('SES-', $adminSession['session_handle']);

        // Terminate session by safe handle
        $termRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/session-security/terminate-session', [
                'session_handle' => $adminSession['session_handle'],
                'reason' => 'Suspicious session IP location change',
            ]);

        $termRes->assertStatus(200)
            ->assertJsonPath('data.terminated', true);

        $this->assertDatabaseMissing('sessions', ['id' => $rawSessionSecretId]);

        // Account-wide session and token invalidation
        $invRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/session-security/invalidate-account', [
                'user_id' => $this->standardAdmin->id,
                'reason' => 'Compromised credential containment drill',
            ]);

        $invRes->assertStatus(200)
            ->assertJsonPath('data.tokens_revoked', 1);
    }

    public function test_threat_detection_correlation_deduplication_false_positive_and_dual_approval_rollback(): void
    {
        // 1. Seed brute-force failed login events from a single attacker IP
        for ($i = 0; $i < 6; $i++) {
            SecurityEvent::create([
                'user_id' => $this->standardAdmin->id,
                'event_type' => 'failed_login',
                'severity' => 'warning',
                'ip_address' => '198.51.100.77',
                'user_agent' => 'HydraBruteForce/1.0',
                'description' => 'Invalid password attempt',
                'resolved' => false,
            ]);
        }

        // 2. Seed destructive user actions by standardAdmin to trigger TDR-PRIV-ABUSE-VELOCITY (high-impact disable_admin_account)
        for ($i = 0; $i < 5; $i++) {
            AuditLog::record(
                'user.suspended',
                User::class,
                $this->regularUser->id,
                ['status' => 'active'],
                ['status' => 'suspended'],
                ['reason' => 'Bulk velocity test '.$i],
                $this->standardAdmin->id
            );
        }

        // 3. Run threat correlation evaluation
        $evalRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/threat-detection/evaluate');

        $evalRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertGreaterThanOrEqual(2, $evalRes->json('data.new_detections_count'));

        // 4. Re-running evaluation within suppression window deduplicates rather than creating duplicate detections
        $secondEvalRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/threat-detection/evaluate');

        $secondEvalRes->assertStatus(200)
            ->assertJsonPath('data.new_detections_count', 0);

        $bruteForceDetection = ThreatDetection::whereHas('rule', fn ($q) => $q->where('rule_id', 'TDR-AUTH-BRUTEFORCE'))->firstOrFail();
        $this->assertGreaterThanOrEqual(2, $bruteForceDetection->occurrence_count);

        // 5. Mark brute-force detection as false positive
        $fpRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/threat-detection/detections/{$bruteForceDetection->id}/false-positive", [
                'reason' => 'Authorized red-team penetration test from 198.51.100.77',
            ]);

        $fpRes->assertStatus(200)
            ->assertJsonPath('data.status', 'false_positive');

        // 6. High-impact action (disable_admin_account) requires human dual-approval and blocks self-approval
        $pendingAction = AutomatedResponseAction::where('action_type', 'disable_admin_account')
            ->where('target_identifier', (string) $this->standardAdmin->id)
            ->firstOrFail();

        $this->assertSame('pending_approval', $pendingAction->status);
        $this->assertTrue($pendingAction->requires_human_approval);

        // Force requested_by to superAdmin to verify anti-self-approval enforcement
        $pendingAction->update(['requested_by' => $this->superAdmin->id]);

        $selfApproveRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/threat-detection/actions/{$pendingAction->id}/approve", [
                'reason' => 'Attempting to self-approve my own requested response action',
            ]);

        $selfApproveRes->assertStatus(422)
            ->assertJsonPath('success', false);

        // 7. Second independent Super Admin approves the action -> executes and suspends standardAdmin
        $approveRes = $this->actingAs($this->secondSuperAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/threat-detection/actions/{$pendingAction->id}/approve", [
                'reason' => 'Confirmed anomalous bulk administrative suspension velocity',
            ]);

        $approveRes->assertStatus(200)
            ->assertJsonPath('data.status', 'executed');

        $this->assertSame('suspended', $this->standardAdmin->fresh()->status);

        // 8. Rollback / compensate the executed response action -> restores standardAdmin to active
        $rollbackRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/threat-detection/actions/{$pendingAction->id}/rollback", [
                'reason' => 'Admin identity verified out-of-band; restoring account status',
            ]);

        $rollbackRes->assertStatus(200)
            ->assertJsonPath('data.status', 'rolled_back');

        $this->assertSame('active', $this->standardAdmin->fresh()->status);
    }

    public function test_automated_response_cannot_disable_last_active_super_admin(): void
    {
        // Suspend secondSuperAdmin so only primary superAdmin remains active
        $this->secondSuperAdmin->update(['status' => 'suspended']);

        $action = AutomatedResponseAction::create([
            'action_uid' => 'ACT-TEST-LAST-SA',
            'action_type' => 'disable_admin_account',
            'target_type' => 'user',
            'target_identifier' => (string) $this->superAdmin->id,
            'status' => 'pending_approval',
            'requires_human_approval' => true,
            'idempotency_key' => hash('sha256', 'test-last-sa'),
            'requested_by' => $this->standardAdmin->id,
        ]);

        $service = app(ThreatDetectionAndResponseService::class);
        $executed = $service->executeResponseAction($action, $this->superAdmin);

        $this->assertSame('failed', $executed->status);
        $this->assertSame('CANNOT_DISABLE_LAST_SUPER_ADMIN', $executed->execution_result['reason'] ?? null);
        $this->assertSame('active', $this->superAdmin->fresh()->status);
    }

    public function test_gate_c_offsite_backup_verification_and_gate_d_key_rotation_drill(): void
    {
        // Gate C: Local disk must never be accepted as off-site storage
        $localOffsiteRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/offsite-verify', [
                'disk' => 'local',
            ]);

        $localOffsiteRes->assertStatus(200)
            ->assertJsonPath('data.verified', false)
            ->assertJsonPath('data.status', 'BLOCKED')
            ->assertJsonPath('data.reason_code', 'LOCAL_DISK_IS_NOT_OFFSITE')
            ->assertJsonPath('data.gate_c_status', 'NOT_VERIFIED');

        // Gate C: Unconfigured S3 disk is honestly reported as BLOCKED / NOT_VERIFIED
        config([
            'filesystems.disks.s3.key' => '',
            'filesystems.disks.s3.secret' => '',
            'filesystems.disks.s3.bucket' => '',
        ]);

        $s3BlockedRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/offsite-verify', [
                'disk' => 's3',
            ]);

        $s3BlockedRes->assertStatus(200)
            ->assertJsonPath('data.verified', false)
            ->assertJsonPath('data.status', 'BLOCKED')
            ->assertJsonPath('data.reason_code', 'OFFSITE_CREDENTIALS_MISSING')
            ->assertJsonPath('data.gate_c_status', 'NOT_VERIFIED');

        // Gate D: Encryption Key Recovery & Key Rotation Drill (v1 -> v2 + historical v1 decryptability)
        $gateDRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/key-rotation-drill');

        $gateDRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.passed', true)
            ->assertJsonPath('data.gate_d_status', 'AUTOMATED_AND_DRILL_VERIFIED')
            ->assertJsonPath('data.steps.1_create_and_verify_v1_backup.passed', true)
            ->assertJsonPath('data.steps.2_reject_wrong_decryption_key.passed', true)
            ->assertJsonPath('data.steps.3_reject_missing_decryption_key.passed', true)
            ->assertJsonPath('data.steps.4_key_rotation_v2_new_backup.passed', true)
            ->assertJsonPath('data.steps.5_historical_v1_backup_decryptable_after_rotation.passed', true);
    }

    public function test_audit_log_secret_redaction_and_sequence_gap_detection(): void
    {
        // 1. Sensitive keys in old_values, new_values, and metadata must be scrubbed to [REDACTED] before storage & hashing
        $sensitiveSecret = 'SUPER_SECRET_PASSWORD_TOKEN_999111222';
        $log = AuditLog::record(
            'security.credentials.test',
            User::class,
            $this->superAdmin->id,
            ['password' => $sensitiveSecret, 'safe_field' => 'before'],
            ['api_key' => $sensitiveSecret, 'nested' => ['two_factor_secret' => $sensitiveSecret, 'keep' => 'after']],
            ['authorization' => 'Bearer '.$sensitiveSecret, 'reason' => 'Testing redaction'],
            $this->superAdmin->id
        );

        $rawRow = DB::table('audit_logs')->where('id', $log->id)->first();
        $this->assertStringNotContainsString($sensitiveSecret, (string) $rawRow->old_values);
        $this->assertStringNotContainsString($sensitiveSecret, (string) $rawRow->new_values);
        $this->assertSame('[REDACTED]', $log->old_values['password']);
        $this->assertSame('before', $log->old_values['safe_field']);
        $this->assertSame('[REDACTED]', $log->new_values['api_key']);
        $this->assertSame('[REDACTED]', $log->new_values['nested']['two_factor_secret']);
        $this->assertSame('[REDACTED]', $log->metadata['authorization']);

        // 2. Verify chain integrity passes and exports checkpoint file
        $auditService = app(TamperEvidentAuditService::class);
        $verifyResult = $auditService->verifyChainIntegrity(500, $this->superAdmin->id);

        $this->assertTrue($verifyResult['valid']);
        $this->assertSame(0, $verifyResult['gaps_count']);
        $checkpoint = $auditService->createExternalCheckpoint($this->superAdmin->id);
        $this->assertNotNull($checkpoint);
        Storage::disk('local')->assertExists("audit-checkpoints/checkpoint_{$checkpoint->sequence_end}.json");

        // 3. Simulate a sequence gap (deleting an intermediate row via raw DB query when testing trigger bypass or gap)
        $logA = AuditLog::record('test.seq.a', null, null, null, ['step' => 1], null, $this->superAdmin->id);
        $logB = AuditLog::record('test.seq.b', null, null, null, ['step' => 2], null, $this->superAdmin->id);
        $logC = AuditLog::record('test.seq.c', null, null, null, ['step' => 3], null, $this->superAdmin->id);

        // Temporarily drop delete triggers if present and simulate external DBA row deletion gap
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_audit_logs_delete');
        }
        DB::table('audit_logs')->where('id', $logB->id)->delete();

        $gapVerify = $auditService->verifyChainIntegrity(500, $this->superAdmin->id);
        $this->assertFalse($gapVerify['valid']);
        $this->assertGreaterThanOrEqual(1, $gapVerify['gaps_count']);
    }

    public function test_ha_probes_compliance_controls_config_drift_slo_sbom_and_release_gates(): void
    {
        // 1. Public Liveness & Readiness Probes
        $this->getJson('/api/v2/health/live')
            ->assertStatus(200)
            ->assertJsonPath('status', 'ALIVE');

        $this->getJson('/api/v2/health/ready')
            ->assertStatus(200)
            ->assertJsonPath('status', 'READY')
            ->assertJsonPath('ready', true);

        // 2. HA & Resilience Assessment honestly reports single-node topology in SQLite test environment
        $haRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/ha-resilience');

        $haRes->assertStatus(200)
            ->assertJsonPath('data.topology_mode', 'single-node')
            ->assertJsonPath('data.multi_instance_safe', false);

        // 3. Compliance Controls & Reviewer Sign-Off
        $compRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/compliance-controls');

        $compRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $control = ComplianceControl::firstOrFail();
        $reviewRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/admin/enterprise/compliance-controls/{$control->id}/review", [
                'reason' => 'Verified control evidence hash and runtime enforcement',
            ]);

        $reviewRes->assertStatus(200)
            ->assertJsonPath('data.last_reviewed_by', $this->superAdmin->id);

        // 4. Configuration Drift & Time-Bounded Exceptions (never storing plaintext secrets)
        $secretKey = (string) config('app.key');
        $driftRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/config-drift');

        $driftRes->assertStatus(200)
            ->assertJsonPath('data.baseline_key', 'CFG-BASELINE-V1');
        $this->assertStringNotContainsString($secretKey, (string) $driftRes->getContent());

        $excRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/config-drift/exceptions', [
                'expires_in_hours' => 24,
                'reason' => 'Approved temporary maintenance window configuration exception',
            ]);

        $excRes->assertStatus(200)
            ->assertJsonPath('data.status', 'exception_granted');

        // 5. SLO & Error Budget Dashboard (honestly marks unaccumulated 30d uptime as INSUFFICIENT_TELEMETRY)
        $sloRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/slo');

        $sloRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $slosByKey = collect($sloRes->json('data.slos'))->keyBy('slo_key');
        $this->assertSame('INSUFFICIENT_TELEMETRY', $slosByKey['SLO-API-AVAIL']['status']);
        $this->assertSame('MEETING_SLO', $slosByKey['SLO-DB-LATENCY']['status']);

        // 6. CycloneDX 1.5 SBOM Generation from composer.lock & package.json
        $sbomRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/admin/enterprise/sbom');

        $sbomRes->assertStatus(200)
            ->assertJsonPath('data.bomFormat', 'CycloneDX')
            ->assertJsonPath('data.specVersion', '1.5');
        $this->assertGreaterThan(10, $sbomRes->json('data.summary.composer_packages'));

        // 7. Release Verification Gates
        $gatesRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/release-gates/evaluate', [
                'release_version' => 'v6.0.0-test',
            ]);

        $gatesRes->assertStatus(200)
            ->assertJsonPath('data.release_version', 'v6.0.0-test');
        $this->assertCount(14, $gatesRes->json('data.gate_results'));

        // 8. Phase 6 Admin Dashboard UI renders #sec-zerotrust workspace
        $dashRes = $this->actingAs($this->superAdmin)
            ->get('/admin/dashboard');

        $dashRes->assertStatus(200)
            ->assertSee('sec-zerotrust', false)
            ->assertSee('loadZeroTrustWorkspace', false);
    }
}
