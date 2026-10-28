# Phase 6 — Enterprise Zero-Trust Security, High Availability, Advanced Compliance & Autonomous Operations Runbook

## 1. Zero-Trust Access Policy Administration (`ZeroTrustPolicyService`)
- **Architecture**: Deny-by-default authorization evaluation combining RBAC permissions, ABAC context, MFA status, JIT privilege elevation, session freshness, IP CIDR restrictions, device trust signals, and threat risk score thresholds.
- **Unverified Context Fail-Closed Rule**: When a policy requires `require_trusted_device = true` and no cryptographic device certificate or trusted device signal is presented, `ZeroTrustPolicyService::evaluateAccess()` fails closed (`DENY`, reason `UNTRUSTED_OR_UNVERIFIED_DEVICE_CONTEXT`).
- **Policy Updates**: Policies are versioned (`version` increments on every update) and audited via `AuditLog::record('zero_trust.policy.updated', ...)`.

## 2. Emergency Privileged Access Recovery (Break-Glass)
- **Scenario**: All active administrators are blocked by an overly restrictive Zero-Trust IP CIDR or risk threshold rule.
- **Procedure**:
  1. Authenticate via SSH to the application host using hardware-backed SSH keys.
  2. Inspect the active policy state:
     ```bash
     php artisan tinker --execute="dump(\App\Models\ZeroTrustPolicy::all()->toArray());"
     ```
  3. Record an emergency change ticket and reset the blocking policy parameter while preserving audit integrity:
     ```bash
     php artisan tinker --execute="\App\Services\Security\ZeroTrustPolicyService::class"
     ```
  4. Immediately run `TamperEvidentAuditService::verifyChainIntegrity()` after restoration.

## 3. MFA Recovery & Hardware Key / TOTP Reset
- **Procedure**:
  1. Require out-of-band identity verification by two independent `SUPER_ADMIN` or Security Officer operators.
  2. Revoke all active sessions and Sanctum tokens for the affected admin via `/api/v2/admin/enterprise/session-security/invalidate-account`.
  3. Clear `two_factor_secret` and `two_factor_recovery_codes`, forcing immediate re-enrollment upon next login.

## 4. Session & Sanctum Token Revocation (`SessionSecurityService`)
- **Safe Session Handles**: Raw session IDs and token values are never exposed in UI or API responses. Operators reference keyed HMAC-SHA256 handles (`SES-...` and `TOK-...`).
- **Single Session Termination**: `POST /api/v2/admin/enterprise/session-security/terminate-session` with `{ "session_handle": "SES-...", "reason": "..." }`.
- **Account-Wide Invalidation**: `POST /api/v2/admin/enterprise/session-security/invalidate-account` with `{ "user_id": <id>, "reason": "..." }`.

## 5. Compromised Administrator Account Containment
- **Automated / Manual Containment**:
  1. Trigger or approve an automated response action (`disable_admin_account` or `revoke_all_user_sessions`) in the Zero-Trust & Threat Detection workspace.
  2. Note: Last active `SUPER_ADMIN` protection prevents disabling or suspending the final `SUPER_ADMIN` account (`CANNOT_DISABLE_LAST_SUPER_ADMIN`).
  3. Review the actor's audit trail via `GET /api/v2/admin/enterprise/audit-logs?user_id=<id>`.

## 6. Threat Detection Rule Tuning & False-Positive Handling (`ThreatDetectionAndResponseService`)
- **Correlation Rules**:
  - `TDR-AUTH-BRUTEFORCE` (Failed login burst by IP)
  - `TDR-AUTHZ-DENIAL-BURST` (Repeated Zero-Trust policy denials)
  - `TDR-AUDIT-TAMPER-SIGNAL` (Tamper-evident hash chain anomaly)
  - `TDR-PRIV-ABUSE-VELOCITY` (High-velocity destructive user actions)
  - `TDR-MASS-EXPORT-ATTEMPT` (Mass privacy/data export spike)
- **Deduplication & Suppression**: Repeated triggers within `suppression_window_seconds` increment `occurrence_count` on the existing open `ThreatDetection` record instead of creating alert storms.
- **False-Positive Marking**: `POST /api/v2/admin/enterprise/threat-detection/detections/{id}/false-positive` increments the rule's `false_positive_count` and closes the detection with an immutable audit entry.

## 7. Automated Response Dual-Approval & Rollback
- **High-Impact Guardrails**: Actions such as `disable_admin_account` and `quarantine_user_account` enter `pending_approval` status.
- **Anti-Self-Approval**: The administrator who requested or triggered the action (`requested_by`) cannot approve it (`SELF_APPROVAL_FORBIDDEN`). A distinct administrator must call `POST /api/v2/admin/enterprise/threat-detection/actions/{id}/approve`.
- **Rollback / Compensation**: Executed actions store `rollback_Metadata` (`previous_status`, `previous_suspended_until`, etc.) and can be reverted via `POST /api/v2/admin/enterprise/threat-detection/actions/{id}/rollback`.

## 8. Configuration Drift Remediation & Exception Expiration
- **Baseline Inspection**: `GET /api/v2/admin/enterprise/config-drift` compares live runtime configuration against `CFG-BASELINE-V1`.
- **Temporary Exceptions**: Time-bounded exceptions (`MAX 168 hours`) can be granted with a mandatory justification via `POST /api/v2/admin/enterprise/config-drift/exceptions` and automatically expire when `exception_expires_at` passes.

## 9. High Availability Node / Queue / Cache Failure Handling
- **Probes**:
  - Liveness: `GET /api/v2/health/live` (200 OK if PHP runtime responds).
  - Readiness: `GET /api/v2/health/ready` (503 Service Unavailable if DB or Cache fails or app is in maintenance mode).
- **Single-Node vs Multi-Instance Disclosure**: `GET /api/v2/admin/enterprise/ha-resilience` reports actual topology (`single-node` when `CACHE_STORE=database`/`file` or `DB_CONNECTION=sqlite`) and lists concrete multi-instance hazards.

## 10. Database Failover & Recovery Verification
- Verify database read/write state via `/api/v2/health/ready`.
- Execute an isolated restore drill (`POST /api/v2/admin/enterprise/disaster-recovery/backups/{id}/restore-drill`) before promoting a recovery database.

## 11. Encryption Key Rotation & Historical Backup Decryption (Gate D)
- **Keyring Architecture**: `BackupService` derives independent 256-bit AES encryption keys (`jugajug-backup-enc-{version}`) and 256-bit HMAC signing keys (`jugajug-backup-mac-{version}`) via `hash_hkdf('sha256', ...)`.
- **Key Independence**: Backup encryption keys (`BACKUP_ENCRYPTION_KEY` / `BACKUP_KEYRING`) are strictly decoupled from standard application credentials (`APP_KEY`). Keys are never committed to version control, never stored in the database, and never serialized inside the encrypted backup envelope.
- **Key Access Boundaries & Role Separation**: Only personnel with authorized `SUPER_ADMIN` or Security Officer roles may access key material or trigger key rotation. Ordinary administrators, developers, and read-only auditors cannot read or export raw keys.
- **Authorized Recovery Procedure**:
  1. Retrieve the master key material from the air-gapped, hardware-backed vault / KMS secret store for the specific backup's `key_version` (indicated by `"kv"` in the backup envelope).
  2. Populate the keyring configuration: `config(['app.backup_keyring' => ['v1' => $v1Key, 'v2' => $v2Key]])`.
  3. The key derivation function deterministically derives the distinct encryption and HMAC keys using HKDF-SHA256 with the version-specific info string.
  4. Run `BackupService::verifyRestore($backup)` to verify constant-time HMAC-SHA256 authentication before attempting any AES-256-CBC decryption or decompression.
- **Key Loss Consequences**: Because the backup architecture enforces Authenticated Encryption (AES-256-CBC + HMAC-SHA256) with no backdoors or key escrow, loss of the specific version's master key renders all backups created with that key permanently and mathematically unrecoverable.
- **Verification Drill**: Run `POST /api/v2/admin/enterprise/disaster-recovery/key-rotation-drill` (or CLI `php artisan admin:backup-verify --key-drill`) to execute a non-destructive 6-stage drill using dedicated synthetic test keys proving:
  1. `1_create_and_verify_v1_backup`: Successful v1 encryption and integrity verification.
  2. `2_reject_wrong_decryption_key`: Wrong decryption key is safely rejected by HMAC authentication.
  3. `2b_reject_tampered_ciphertext`: Single bit-flip in ciphertext is detected and rejected by HMAC before decryption.
  4. `3_reject_missing_decryption_key`: Missing key version in keyring fails closed immediately.
  5. `4_key_rotation_v2_new_backup`: Seamless rotation to `v2` for newly created backups.
  6. `5_historical_v1_backup_decryptable_after_rotation`: Historical `v1` backups remain decryptable after rotating active key to `v2`.

## 12. Off-Site Backup Remote Storage & Disaster Recovery (Gate C)
- **Architecture**: `RemoteBackupService` provides a production-grade remote storage adapter for S3-compatible providers (AWS S3, Cloudflare R2, MinIO, Google Cloud Storage S3 Interop).
- **Strict Separation & Local Rejection**: Local filesystem storage (`local`, `public`, or local filesystem paths) is strictly rejected as proof of off-site backup (`LOCAL_DISK_IS_NOT_OFFSITE`).
- **Encrypted Transport (HTTPS)**: Remote endpoints MUST utilize TLS encryption (`https://`). Unencrypted plain HTTP endpoints are rejected (`UNENCRYPTED_TRANSPORT_FORBIDDEN`).
- **Configurable Credentials**:
  - `AWS_ACCESS_KEY_ID`: S3 access key ID.
  - `AWS_SECRET_ACCESS_KEY`: S3 secret access key.
  - `AWS_DEFAULT_REGION`: Bucket region (e.g., `ap-southeast-1`).
  - `AWS_BUCKET`: Remote backup bucket name.
  - `AWS_ENDPOINT`: Custom endpoint URL (e.g. `https://<account_id>.r2.cloudflarestorage.com` or custom S3).
  - `AWS_USE_PATH_STYLE_ENDPOINT`: Set `true` for MinIO or Ceph, `false` for AWS/R2.
- **Provider Capabilities Validation**:
  - Bucket Versioning (`getBucketVersioning`): Checks whether versioning is enabled to protect against accidental overwrite.
  - Object Lock / WORM Retention (`getObjectLockConfiguration`): Validates deletion protection and legal hold compliance.
  - Access Restrictions (`getPublicAccessBlock`): Confirms public access block is enforced so backups remain strictly private.
- **Verification Probe Workflow (`verifyOffsiteBackup`)**:
  1. Validates configuration, credentials, and encrypted transport.
  2. Generates a synthetic, non-production test artifact with distinct nonce and timestamp.
  3. Encrypts payload with HKDF-derived keys and uploads to `backups/synthetic_probes/`.
  4. Confirms remote object identity and existence on the remote bucket.
  5. Downloads object, asserts constant-time SHA-256 match, and authenticates AEAD decryption.
  6. Deletes test artifact from remote storage and verifies deletion confirmed (`cleanup_verified`).
- **Isolated Remote Disaster-Recovery Drill (`runRemoteDisasterRecoveryDrill`)**:
  - Run via `POST /api/v2/admin/enterprise/disaster-recovery/remote-drill` or CLI `php artisan admin:backup-verify --remote-drill`.
  - Workflow:
    1. Creates synthetic backup artifact with deterministic relational tables (`users`, `roles`, `platform_settings`, `audit_logs`).
    2. Encrypts and uploads to remote bucket `backups/dr_drills/{uid}.tar.gz.enc`.
    3. Retrieves remote object into isolated directory (`storage/app/restore-drills/{drillUid}`).
    4. Decrypts and restores into an isolated temporary SQLite database connection.
    5. Runs SQLite `PRAGMA integrity_check`, validates record counts and referential integrity.
    6. Executes negative recovery tests: verifies tampered ciphertext and wrong decryption keys fail closed with HMAC rejection.
    7. Destroys isolated temporary database and deletes remote synthetic artifact.
    8. Confirms live production database was completely untouched (`production_db_untouched = true`).
- **Honest Gate Reporting**:
  - Allowed statuses: `IMPLEMENTED`, `AUTOMATED_TEST_VERIFIED`, `REAL_SERVICE_VERIFIED`, `MANUALLY_REVIEWED`, `NOT_VERIFIED`, `BLOCKED`, `FAILED`.
  - When credentials or remote infrastructure are unconfigured in environment, Gate C is honestly reported as `BLOCKED` / `NOT_VERIFIED`. It is NEVER fabricated as verified.
  - Gate C may only be marked `REAL_SERVICE_VERIFIED` after a successful probe/drill against the actual remote storage service.

## 13. Audit Chain Tampering & Sequence Gap Investigation (`TamperEvidentAuditService`)
- **Verification**: `GET /api/v2/admin/enterprise/audit-logs/verify` checks:
  - Contiguous sequence numbers (`ERROR_GAP_DETECTED` if any intermediate audit log ID was deleted)
  - `previous_hash` linkage (`ERROR_CHAIN_BROKEN`)
  - HMAC-SHA256 payload signature (`ERROR_SIGNATURE_MISMATCH`)
- **External Checkpoints**: Checkpoints are exported to `storage/app/private/audit-checkpoints/checkpoint_{sequence_end}.json` and recorded in `audit_integrity_runs`.

## 14. Release Rollback Procedure
- Inspect release gates via `POST /api/v2/admin/enterprise/release-gates/evaluate`.
- If a post-deployment SLO or readiness check fails, roll back symlink to the previous release directory and run `php artisan queue:restart`.

## 15. Privacy Request & Legal Hold Conflict Resolution
- **Precedence**: Legal Hold (`users.is_legal_hold = true`) strictly overrides GDPR/privacy erasure requests (`HTTP 422` blocked) and automated retention pruning.
- **Resolution**: Only an authorized administrator may lift a Legal Hold (`POST /api/v2/admin/enterprise/governance/legal-hold`) with an audited legal justification before erasure can proceed.

