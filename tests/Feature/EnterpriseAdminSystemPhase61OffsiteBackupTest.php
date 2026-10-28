<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\BackupRestoreDrill;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\EnterpriseAccessControlService;
use App\Services\Admin\EnterpriseGovernanceAndSloService;
use App\Services\Backup\RemoteBackupService;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterpriseAdminSystemPhase61OffsiteBackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $standardAdmin;

    protected User $regularUser;

    protected BackupService $backupService;

    protected RemoteBackupService $remoteBackupService;

    protected function setUp(): void
    {
        parent::setUp();

        app(EnterpriseAccessControlService::class)->syncStandardPermissions();

        $superAdminRole = Role::where('name', 'SUPER_ADMIN')->firstOrFail();
        $adminRole = Role::where('name', 'ADMIN')->firstOrFail();

        $this->superAdmin = User::factory()->create([
            'name' => 'Phase 6.1 Super Admin',
            'email' => 'superadmin-p61@jugajug.com',
            'username' => 'superadmin_p61',
            'status' => 'active',
            'two_factor_enabled' => true,
        ]);
        $this->superAdmin->roles()->attach($superAdminRole);

        $this->standardAdmin = User::factory()->create([
            'name' => 'Phase 6.1 Standard Admin',
            'email' => 'admin-p61@jugajug.com',
            'username' => 'admin_p61',
            'status' => 'active',
            'two_factor_enabled' => false,
        ]);
        $this->standardAdmin->roles()->attach($adminRole);

        $this->regularUser = User::factory()->create([
            'name' => 'Phase 6.1 Regular User',
            'email' => 'user-p61@jugajug.com',
            'username' => 'user_p61',
            'status' => 'active',
        ]);

        $this->backupService = app(BackupService::class);
        $this->remoteBackupService = app(RemoteBackupService::class);
    }

    /**
     * 1. Gate C strictly rejects local filesystem storage as proof of off-site backup.
     */
    public function test_gate_c_rejects_local_filesystem_as_offsite_storage(): void
    {
        $res = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/offsite-verify', [
                'disk' => 'local',
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.verified', false)
            ->assertJsonPath('data.status', 'BLOCKED')
            ->assertJsonPath('data.gate_c_status', 'NOT_VERIFIED')
            ->assertJsonPath('data.reason_code', RemoteBackupService::REASON_LOCAL_DISK)
            ->assertJsonPath('data.is_separate_from_primary', false);

        // Also test 'public' disk
        $publicRes = $this->remoteBackupService->verifyOffsiteBackup('public');
        $this->assertFalse($publicRes['verified']);
        $this->assertSame('BLOCKED', $publicRes['status']);
        $this->assertSame(RemoteBackupService::REASON_LOCAL_DISK, $publicRes['reason_code']);
    }

    /**
     * 2. Gate C enforces encrypted transport (HTTPS) and rejects unencrypted HTTP endpoints.
     */
    public function test_gate_c_rejects_unencrypted_http_transport(): void
    {
        config([
            'filesystems.disks.insecure_s3' => [
                'driver' => 's3',
                'key' => 'AKIA_TEST_KEY',
                'secret' => 'SECRET_TEST_KEY',
                'bucket' => 'test-bucket',
                'region' => 'us-east-1',
                'endpoint' => 'http://insecure-s3-endpoint.local:9000',
            ],
        ]);

        $res = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/offsite-verify', [
                'disk' => 'insecure_s3',
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.verified', false)
            ->assertJsonPath('data.status', 'FAILED')
            ->assertJsonPath('data.gate_c_status', 'FAILED')
            ->assertJsonPath('data.reason_code', RemoteBackupService::REASON_UNENCRYPTED_TRANSPORT);
    }

    /**
     * 3. Gate C fails closed when credentials or bucket configuration are missing.
     */
    public function test_gate_c_fails_closed_when_credentials_are_missing(): void
    {
        config([
            'filesystems.disks.empty_s3' => [
                'driver' => 's3',
                'key' => '',
                'secret' => '',
                'bucket' => '',
                'region' => 'us-east-1',
            ],
        ]);

        $res = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/offsite-verify', [
                'disk' => 'empty_s3',
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.verified', false)
            ->assertJsonPath('data.status', 'BLOCKED')
            ->assertJsonPath('data.gate_c_status', 'NOT_VERIFIED')
            ->assertJsonPath('data.reason_code', RemoteBackupService::REASON_CREDENTIALS_MISSING);
    }

    /**
     * 4. Gate C fails closed when remote storage is unavailable or unreachable.
     */
    public function test_gate_c_fails_closed_when_storage_endpoint_is_unreachable(): void
    {
        config([
            'filesystems.disks.unreachable_s3' => [
                'driver' => 's3',
                'key' => 'AKIA_UNREACHABLE_KEY',
                'secret' => 'SECRET_UNREACHABLE_KEY',
                'bucket' => 'nonexistent-bucket',
                'region' => 'us-east-1',
                'endpoint' => 'https://unreachable-host-99999.invalid',
            ],
        ]);

        $res = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/offsite-verify', [
                'disk' => 'unreachable_s3',
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.verified', false)
            ->assertJsonPath('data.status', 'BLOCKED')
            ->assertJsonPath('data.gate_c_status', 'BLOCKED');
    }

    /**
     * 5. Gate C remote storage success path: upload synthetic probe, verify remote object identity,
     * read-back SHA-256 integrity, AEAD authenticated decryption, and probe cleanup.
     */
    public function test_gate_c_remote_storage_synthetic_probe_roundtrip_verification(): void
    {
        // Configure an isolated S3-compatible remote disk mock
        Storage::fake('remote_s3');
        config([
            'filesystems.disks.remote_s3' => [
                'driver' => 's3',
                'key' => 'AKIA_TEST_VALID_KEY',
                'secret' => 'SECRET_TEST_VALID_KEY',
                'bucket' => 'jugajug-dr-offsite-test',
                'region' => 'ap-southeast-1',
                'endpoint' => 'https://s3.ap-southeast-1.amazonaws.com',
            ],
        ]);

        $res = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/offsite-verify', [
                'disk' => 'remote_s3',
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('data.gate_c_status', 'REAL_SERVICE_VERIFIED')
            ->assertJsonPath('data.status', 'REAL_SERVICE_VERIFIED')
            ->assertJsonPath('data.disk', 'remote_s3')
            ->assertJsonPath('data.sha256_verified', true)
            ->assertJsonPath('data.aead_decryption_verified', true)
            ->assertJsonPath('data.cleanup_verified', true)
            ->assertJsonPath('data.provider_capabilities.encrypted_transport', true);

        // Ensure probe file was completely cleaned up from storage
        $files = Storage::disk('remote_s3')->allFiles('backups/synthetic_probes');
        $this->assertEmpty($files);
    }

    /**
     * 6. Gate C Isolated Remote Disaster-Recovery Drill:
     * Synthetic backup upload, remote retrieval, checksum verification, AEAD decryption,
     * restore into isolated SQLite DB, PRAGMA check, negative recovery cases, and cleanup.
     */
    public function test_gate_c_isolated_remote_disaster_recovery_drill(): void
    {
        Storage::fake('remote_dr_disk');
        config([
            'filesystems.disks.remote_dr_disk' => [
                'driver' => 's3',
                'key' => 'AKIA_DR_KEY',
                'secret' => 'SECRET_DR_KEY',
                'bucket' => 'jugajug-dr-synthetic-drill',
                'region' => 'ap-southeast-1',
                'endpoint' => 'https://s3.ap-southeast-1.amazonaws.com',
            ],
        ]);

        $usersBefore = User::count();

        $res = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/remote-drill', [
                'disk' => 'remote_dr_disk',
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.passed', true)
            ->assertJsonPath('data.gate_c_drill_status', 'REAL_SERVICE_VERIFIED')
            ->assertJsonPath('data.steps.1_create_synthetic_backup.passed', true)
            ->assertJsonPath('data.steps.2_encrypt_and_upload_remote.passed', true)
            ->assertJsonPath('data.steps.3_retrieve_remote_object.passed', true)
            ->assertJsonPath('data.steps.4_verify_integrity_and_decrypt.passed', true)
            ->assertJsonPath('data.steps.5_restore_isolated_database.passed', true)
            ->assertJsonPath('data.steps.6_verify_schema_and_counts.passed', true)
            ->assertJsonPath('data.steps.7_negative_recovery_tests.passed', true)
            ->assertJsonPath('data.steps.8_remote_and_local_cleanup.passed', true)
            ->assertJsonPath('data.negative_tests.tampered_ciphertext_rejected', true)
            ->assertJsonPath('data.negative_tests.wrong_key_rejected', true)
            ->assertJsonPath('data.cleanup.remote_artifact_deleted', true)
            ->assertJsonPath('data.cleanup.isolated_local_environment_cleaned', true);

        $this->assertGreaterThan(0, (int) $res->json('data.restored_tables_count'));
        $this->assertGreaterThan(0, (int) $res->json('data.restored_records_count'));

        // CRITICAL ASSERTION: Production database must remain completely untouched!
        $this->assertSame($usersBefore, User::count());

        // Remote storage drill artifact must be completely cleaned up
        $remoteDrillFiles = Storage::disk('remote_dr_disk')->allFiles('backups/dr_drills');
        $this->assertEmpty($remoteDrillFiles);

        // Record exists in backup_restore_drills table
        $drillRecord = BackupRestoreDrill::where('drill_uid', $res->json('data.drill_uid'))->first();
        $this->assertNotNull($drillRecord);
        $this->assertSame('passed', $drillRecord->status);
        $this->assertTrue((bool) $drillRecord->isolated_resources_cleaned);
    }

    /**
     * 7. Gate C isolated remote DR drill fails closed when remote storage is blocked.
     */
    public function test_gate_c_remote_dr_drill_fails_closed_when_unconfigured(): void
    {
        $res = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/remote-drill', [
                'disk' => 'local',
            ]);

        $res->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.passed', false)
            ->assertJsonPath('data.gate_c_drill_status', 'BLOCKED');
    }

    /**
     * 8. Gate D: HKDF Encryption Key Recovery, Key Rotation Drill & Historical Decryption.
     */
    public function test_gate_d_hkdf_key_rotation_and_historical_recovery_drill(): void
    {
        $res = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/key-rotation-drill');

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.passed', true)
            ->assertJsonPath('data.gate_d_status', 'AUTOMATED_AND_DRILL_VERIFIED')
            ->assertJsonPath('data.cipher', 'AES-256-CBC+HMAC-SHA256')
            ->assertJsonPath('data.steps.1_create_and_verify_v1_backup.passed', true)
            ->assertJsonPath('data.steps.2_reject_wrong_decryption_key.passed', true)
            ->assertJsonPath('data.steps.2b_reject_tampered_ciphertext.passed', true)
            ->assertJsonPath('data.steps.3_reject_missing_decryption_key.passed', true)
            ->assertJsonPath('data.steps.4_key_rotation_v2_new_backup.passed', true)
            ->assertJsonPath('data.steps.5_historical_v1_backup_decryptable_after_rotation.passed', true);
    }

    /**
     * 9. Unauthorized access boundaries: non-SUPER_ADMIN users cannot execute off-site verification or drills.
     */
    public function test_rbac_boundaries_for_offsite_backup_and_dr_drills(): void
    {
        // Regular user -> 403 Forbidden
        $this->actingAs($this->regularUser, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/offsite-verify')
            ->assertStatus(403);

        $this->actingAs($this->regularUser, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/remote-drill')
            ->assertStatus(403);

        $this->actingAs($this->regularUser, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/key-rotation-drill')
            ->assertStatus(403);

        // Standard admin without super admin -> 403 Forbidden
        $this->actingAs($this->standardAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/offsite-verify')
            ->assertStatus(403);

        $this->actingAs($this->standardAdmin, 'sanctum')
            ->postJson('/api/v2/admin/enterprise/disaster-recovery/remote-drill')
            ->assertStatus(403);
    }

    /**
     * 10. Release Gate accuracy: Gate C is honestly reported as BLOCKED when unconfigured,
     * preventing premature production release authorization.
     */
    public function test_release_gates_honestly_block_production_deploy_when_offsite_is_unconfigured(): void
    {
        // Unset any test remote configs so default S3 is evaluated
        config([
            'filesystems.disks.s3.key' => '',
            'filesystems.disks.s3.secret' => '',
            'filesystems.disks.s3.bucket' => '',
        ]);

        $governanceService = app(EnterpriseGovernanceAndSloService::class);
        $evaluation = $governanceService->evaluateReleaseGates($this->superAdmin);

        $this->assertFalse($evaluation['production_deploy_allowed']);
        $this->assertSame('blocked', $evaluation['overall_status']);

        // Gate 6 (Gate C) must be BLOCKED
        $this->assertArrayHasKey('gate_6_offsite_backup_verification', $evaluation['gates']);
        $this->assertSame('BLOCKED', $evaluation['gates']['gate_6_offsite_backup_verification']['status']);

        // Blockers list must contain Gate 6
        $gate6Blocker = collect($evaluation['blockers'])->first(fn ($b) => str_contains($b, 'gate_6_offsite_backup_verification'));
        $this->assertNotNull($gate6Blocker);
    }

    /**
     * 11. Artisan CLI command: VerifyBackupCommand supports --offsite, --remote-drill, --key-drill.
     */
    public function test_artisan_backup_verify_command_supports_all_gate_flags(): void
    {
        // Key rotation drill CLI
        $this->artisan('admin:backup-verify', ['--key-drill' => true])
            ->assertExitCode(0);

        // Offsite verification CLI on local disk fails closed with exit code 1
        $this->artisan('admin:backup-verify', ['--offsite' => true, '--disk' => 'local'])
            ->assertExitCode(1);

        // Remote DR drill CLI on unconfigured disk fails closed with exit code 1
        $this->artisan('admin:backup-verify', ['--remote-drill' => true, '--disk' => 'local'])
            ->assertExitCode(1);
    }

    /**
     * 12. Gate B: verifyMysql80Integration rejects SQLite and fails closed when daemon is unavailable.
     */
    public function test_gate_b_mysql_verification_rejects_sqlite_and_fails_closed(): void
    {
        $governanceService = app(EnterpriseGovernanceAndSloService::class);

        // SQLite connection must be rejected for Gate B
        $res = $governanceService->verifyMysql80Integration('sqlite');
        $this->assertFalse($res['verified']);
        $this->assertSame('BLOCKED', $res['status']);
        $this->assertStringContainsStringIgnoringCase('sqlite', $res['blocker']);

        // Non-existent unreachable MySQL host fails closed
        config([
            'database.connections.unreachable_mysql' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => '59999',
                'database' => 'fake_db',
                'username' => 'fake_user',
                'password' => 'fake_password',
            ],
        ]);

        $failRes = $governanceService->verifyMysql80Integration('unreachable_mysql');
        $this->assertFalse($failRes['verified']);
        $this->assertSame('BLOCKED', $failRes['status']);

        // Artisan command fails closed on invalid/unreachable connection
        $this->artisan('admin:mysql-verify', ['--connection' => 'sqlite'])
            ->assertExitCode(1);
    }

    /**
     * 13. VerifyReleaseGatesCommand evaluates all 14 release gates and halts deployment if any gate is blocked.
     */
    public function test_verify_release_gates_command_evaluates_14_gates_and_fails_when_blocked(): void
    {
        // When Gate 6 (offsite backup) is unconfigured/blocked, command must exit with code 1 and output table
        $this->artisan('admin:release-verify')
            ->expectsOutputToContain('JUGAJUG ENTERPRISE 14 RELEASE GATES EVALUATION')
            ->expectsOutputToContain('Production Deploy Allowed: NO')
            ->expectsOutputToContain('gate_6_offsite_backup_verification')
            ->assertExitCode(1);

        // JSON format output contains structured gates and deploy allowed = false
        $this->artisan('admin:release-verify', ['--json' => true])
            ->assertExitCode(1);
    }

    /**
     * 14. Gate C and release verifier seamlessly map to Cloudflare R2 disk with strict negative test validation.
     */
    public function test_gate_c_and_release_verifier_support_cloudflare_r2_with_negative_tests(): void
    {
        // 1. admin:release-verify with --disk=r2 checks CLOUDFLARE_R2 variables when unconfigured
        config([
            'filesystems.disks.r2.key' => null,
            'filesystems.disks.r2.secret' => null,
        ]);
        $this->artisan('admin:release-verify', ['--disk' => 'r2'])
            ->expectsOutputToContain('CLOUDFLARE_R2_ACCESS_KEY_ID')
            ->assertExitCode(1);

        // 2. Unencrypted HTTP transport on R2 is rejected
        config([
            'filesystems.disks.r2.key' => 'test_key',
            'filesystems.disks.r2.secret' => 'test_secret',
            'filesystems.disks.r2.bucket' => 'test-bucket',
            'filesystems.disks.r2.endpoint' => 'http://insecure.r2.cloudflarestorage.com',
        ]);
        $httpRes = $this->remoteBackupService->validateConfiguration('r2');
        $this->assertFalse($httpRes['valid']);
        $this->assertSame(RemoteBackupService::REASON_UNENCRYPTED_TRANSPORT, $httpRes['reason_code']);

        // 3. Invalid credentials fail closed with REMOTE_STORAGE_UNAVAILABLE
        config([
            'filesystems.disks.r2.endpoint' => 'https://valid-format.r2.cloudflarestorage.com',
        ]);
        $failRes = $this->remoteBackupService->verifyOffsiteBackup('r2');
        $this->assertFalse($failRes['verified']);
        $this->assertSame('BLOCKED', $failRes['status']);
    }
}
