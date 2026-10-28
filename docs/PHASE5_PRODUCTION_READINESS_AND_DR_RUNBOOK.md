# Jugajug Enterprise Admin Command Center — Phase 5 Operational Runbook
## Production Readiness, Disaster Recovery, Security Hardening & Incident Response

---

## 1. Production Deployment & Release Acceptance

### Prerequisites
- PHP `>= 8.3` (verified on PHP `8.5.6`) with `pdo_mysql`, `pdo_sqlite`, `redis`, `mbstring`, `openssl`, `zlib`.
- MySQL `8.0+` (production) with a dedicated least-privilege database user (`SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES`; **never** use `root` in production).
- Redis `7.x` for cache, sessions, rate limiting, and Horizon queues.

### Pre-Deployment Verification Commands
```bash
# 1. Verify code formatting and dependency lockfile
./vendor/bin/pint --test
composer audit --locked

# 2. Create and cryptographically verify a pre-deployment backup + isolated restore drill
php artisan jugajug:backup --type=db --disk=local
php artisan admin:backup-verify --latest --drill

# 3. Run the 15-point Production Readiness configuration audit
php artisan admin:production-readiness
```

### Atomic Deployment & Rollback
- Execute `./deploy.sh` for symlink-based zero-downtime deployment.
- If `/api/v1/health` does not return `200 OK`, `./deploy.sh` automatically rolls back the `${APP_DIR}/current` symlink to the previous release and restarts queue workers (`php artisan queue:restart`).

---

## 2. Environment Configuration & Secret Hardening

Required production `.env` posture:
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jugajug.com

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_HTTP_ONLY=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

QUEUE_CONNECTION=redis
CACHE_STORE=redis
```
- **Never** commit `.env` or place `.env` / debug scripts inside `public/`.
- `ProductionReadinessService` checks `APP_KEY` length and cipher (`AES-256-CBC`) without ever logging or returning the key value.

---

## 3. Database Migration & Rollback Limitations

- Phase 3 (`2026_10_28_100000_enhance_enterprise_admin_system_phase3.php`) and Phase 5 (`2026_10_28_200000_enhance_enterprise_admin_system_phase5.php`) migrations are strictly **additive** and guard every column/table with `Schema::hasTable()` / `Schema::hasColumn()`.
- **Rollback Limitation**: Rolling back migrations in production drops newly added metadata columns; never run `migrate:fresh` or `migrate:rollback` on a live production database without an approved change window and verified backup.

---

## 4. Backup Creation, Encryption & Key Management

### Encryption Architecture
- Backups are compressed with `gzip` and encrypted at rest using **Authenticated Encryption (`AES-256-CBC + HMAC-SHA256`)** with a cryptographically random 16-byte IV per backup.
- Separate 256-bit encryption and MAC keys are derived via HKDF (`hash_hkdf('sha256', ...)`) scoped by `key_version` (`v1`).
- Encryption keys are **never** stored in database columns.

### CLI Commands
```bash
# Create an encrypted backup (types: full, db, media, redis)
php artisan jugajug:backup --type=db --disk=local

# Verify SHA-256 checksum, HMAC-SHA256 authentication tag, AES-256-CBC decryption, and manifest
php artisan admin:backup-verify --latest
```

---

## 5. Disaster Recovery & Isolated Restore Drills

### Safe Isolated Restore Drill Workflow
1. Validates backup `status === completed` and verifies SHA-256 checksum (`hash_equals`).
2. Verifies HMAC-SHA256 authentication tag and decrypts the AES-256-CBC payload.
3. Creates an isolated temporary directory (`storage/app/restore-drills/DRL-...`) and isolated SQLite database (`restored_database.sqlite`).
4. Restores all snapshot tables and rows into the isolated database.
5. Executes `PRAGMA integrity_check`, verifies table counts against the manifest, and checks foreign-key relationship integrity (`role_user` -> `users`).
6. Records `duration_ms` (measured RTO), `restored_tables_count`, and `restored_records_count` in `backup_restore_drills`.
7. Destroys the temporary database and directory in a `finally` block and confirms `isolated_resources_cleaned = true`.
8. **Never touches or overwrites the live database.**

```bash
# Run an isolated restore verification drill on the latest backup
php artisan admin:backup-verify --latest --drill
```

---

## 6. Security Incident Response (SOC 2.0)

- **Incident Lifecycle States**: `open` -> `acknowledged` -> `investigating` -> `escalated` -> `resolved` (and `reopened` if recurrence occurs).
- **API Endpoint**: `POST /api/v2/admin/enterprise/security/incidents/{id}/transition`
  - Payload: `{"status": "acknowledged|investigating|escalated|resolved|reopened", "notes": "...", "evidence_refs": ["LOG-101"]}`
- Every transition is locked with `lockForUpdate()`, appended to the incident's chronological `timeline`, and recorded in the tamper-evident `audit_logs` chain.

---

## 7. Privileged Access, JIT Elevation & Emergency Access

- **JIT Privilege Elevation**: Time-limited elevation (`5..1440` minutes) via `POST /api/v2/admin/enterprise/roles/elevate`.
- **Immediate Same-Session Revocation**: Revoking an elevation via `POST /api/v2/admin/enterprise/roles/elevations/{id}/revoke` immediately invalidates cached relations and blocks access (`403 Forbidden`) in the same session across both Web (`/admin`) and API routes.
- **MFA Enforcement**: Enabling `security_require_admin_mfa = true` in `platform_settings` requires `two_factor_enabled = true` on the administrator account before executing high-impact privileged actions (role assignment, JIT elevation, Legal Hold changes, restore drills, backup deletion).
- **Last `SUPER_ADMIN` Safeguard**: Row-locked (`lockForUpdate()`) inside database transactions; prevents suspending, demoting, bulk-suspending, or applying `full_ban` to the last active unrestricted `SUPER_ADMIN`.

---

## 8. Tamper-Evident Audit Chain Verification

```bash
# Verify the latest 200 audit log entries and seal a signed checkpoint
php artisan admin:audit-verify --limit=200 --checkpoint
```
- `AuditLog` records are immutable at the Eloquent model layer (`static::updating` and `static::deleting` throw `InvalidArgumentException`).

---

## 9. Privacy Requests (GDPR/DSAR) & Legal Hold Enforcement

- **Legal Hold Race-Condition Guard**: `User::deleting` executes `SELECT is_legal_hold FROM users WHERE id = ? FOR UPDATE`. Even if an in-memory `User` model has `is_legal_hold = false`, concurrent activation of Legal Hold in the database blocks deletion immediately.
- **Export Expiration**: Privacy export downloads (`GET /api/v2/admin/enterprise/governance/privacy-requests/{id}/download`) enforce `export_expires_at`. Expired exports return `410 Gone` and purge the temporary file.

---

## 10. Queue, Scheduler & Degraded Service Troubleshooting

```bash
# Record scheduler heartbeat manually or via cron (* * * * * php artisan schedule:run)
php artisan admin:scheduler-heartbeat
```
- If `scheduler_last_heartbeat_at` is older than 10 minutes, SOC operational telemetry and Production Readiness report `scheduler_missed_heartbeat = true` (`WARNING`).

