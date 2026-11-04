# CONNECTLY — PHASE 13 STAGE 10.05 TO 10.10
## CORE DOMAIN SERVICES & REST API SPECIFICATION

**Platform:** Connectly Parental Control & Family Digital Safety  
**Audit Date:** November 05, 2026  
**Status:** IMPLEMENTED & AUDITED  

---

### 1. Family & Role Management (Stage 10.05)
- **Service:** `App\Services\Parental\FamilyService`
- **Controller:** `App\Http\Controllers\Parental\FamilyController`
- **Policy:** `App\Policies\Parental\FamilyPolicy`
- **Endpoints:**
  - `POST /api/v1/parental/families`: Creates family group and registers owner as primary parent.
  - `GET /api/v1/parental/families/{family}`: Retrieves members, active restrictions, and active alerts.
  - `POST /api/v1/parental/families/{family}/members`: Invites secondary guardians or registers child profiles.
- **IDOR / BOLA Guard:** Any attempt by an unauthorized parent to access a family outside their membership returns `HTTP 403 Forbidden`.

---

### 2. Device Pairing Engine (Stage 10.06)
- **Service:** `App\Services\Parental\DevicePairingService`
- **Pairing Code Generation:**
  - `POST /api/v1/parental/families/{family}/pairing-codes`
  - Generates single-use 6-digit numeric code (`100000` to `999999`).
  - Strict 10-minute TTL (`expires_at = now()->addMinutes(10)`).
  - Stored as SHA-256 hash (`hash('sha256', $code)`). Plaintext is never stored in DB.
- **Atomic Concurrency Redemption:**
  - `POST /api/v1/parental/device/pair`
  - Uses `SELECT ... FOR UPDATE` row-level lock on `device_pairing_codes`.
  - Atomically stamps `used_at = now()`.
  - Maximum 5 failed attempts before permanent lockout.
  - Enrolls device in `child_devices` and issues high-entropy token (`cpd_<random48>`).

---

### 3. Usage Monitoring Engine (Stage 10.07)
- **Service:** `App\Services\Parental\UsageTelemetryService`
- **Telemetry Ingestion:**
  - `POST /api/v1/parental/device/usage/sync` (Protected by `auth.child_device`).
  - Batch ingestion of foreground application runtime events.
  - Granular log stored in `app_usage_logs`.
  - Daily aggregated upsert in `screen_time_records` with composite uniqueness on `(child_device_id, application_package, usage_date)`.
  - Heartbeat timestamp updated (`last_seen_at = now()`).
- **Screen Time Analytics:**
  - `GET /api/v1/parental/children/{childMember}/screen-time`
  - Delivers total seconds, total minutes, and per-package breakdown.

---

### 4. Policy Restriction Engine (Stage 10.08)
- **Service:** `App\Services\Parental\PolicyEngineService`
- **Upsertion:**
  - `POST /api/v1/parental/families/{family}/restrictions`
  - Supports: `daily_time_limit`, `bedtime_curfew`, `app_blacklist`, `app_whitelist`.
  - Policy Versioning: Automatically increments `policy_version` on every modification.
- **Child Policy Query:**
  - `GET /api/v1/parental/device/policies` (Protected by `auth.child_device`).
  - Delivers consolidated active policies, curfew windows, and blacklisted package lists.

---

### 5. Remote Command Delivery Engine (Stage 10.09)
- **Service:** `App\Services\Parental\RemoteCommandService`
- **Command Dispatch:**
  - `POST /api/v1/parental/devices/{device}/commands`
  - Types: `device_lock`, `device_unlock`, `policy_sync`, `sound_alarm`, `telemetry_flush`.
  - Status lifecycle: `queued` -> `sent` -> `acknowledged` -> `completed` / `rejected` / `failed`.
  - UUID and Idempotency: Unique `command_uuid` per instruction.
- **Device Polling & Acknowledgment:**
  - `GET /api/v1/parental/device/commands/pending`
  - `POST /api/v1/parental/device/commands/{uuid}/ack`

---

### 6. Consent Requests & Security Alerts (Stage 10.10)
- **Services:** `ConsentRequestService`, `SecurityAlertService`
- **Child Consent Requests:**
  - `POST /api/v1/parental/device/consent-requests` (`extra_time`, `unblock_app`)
  - `GET /api/v1/parental/families/{family}/consent-requests`
  - `POST /api/v1/parental/consent-requests/{request}/respond` (`approved` or `rejected`)
- **Tamper & Security Health Alerts:**
  - `POST /api/v1/parental/device/alerts` (e.g. `usage_access_revoked`, `device_admin_disabled`)
  - `GET /api/v1/parental/families/{family}/alerts`
- **Credential Revocation:**
  - `POST /api/v1/parental/devices/{device}/revoke`: Nullifies token hash, marks device `revoked`. Subsequent requests immediately fail with `HTTP 401 Unauthorized`.

