# CONNECTLY — STAGE 10.02: ARCHITECTURE & THREAT MODEL SPECIFICATION
## Comprehensive End-to-End System Design, Data Flows & STRIDE Threat Analysis

**Document:** `CONNECTLY_PHASE13_STAGE10_02_ARCHITECTURE_AND_THREAT_MODEL`  
**Execution Date:** 2026-11-05  
**Stage:** 10.02  
**Status:** **APPROVED & BASELINED**  

---

## 1. System Architecture Overview

CONNECTLY is engineered as a zero-trust, privacy-first **Parental Control and Family Digital Safety Ecosystem** comprising three tightly integrated architectural tiers:

```
+--------------------------------------------------------------------------------------------------------------------+
|                                                CONNECTLY CLIENT TIER                                               |
|  +------------------------------------------------------+  +----------------------------------------------------+  |
|  |             PARENT COMPANION APP                     |  |              CHILD DEVICE AGENT                    |  |
|  |          (com.connectly.parent)                      |  |             (com.connectly.child)                  |  |
|  | - Jetpack Compose UI (Material 3)                    |  | - Transparent Monitoring Dashboard (Child UI)     |  |
|  | - Family Management & Child Profiles                 |  | - Foreground Service with Persistent Notification  |  |
|  | - Real-time Screen-Time Dashboard                    |  | - UsageStatsManager Telemetry Harvester            |  |
|  | - Daily Limits, Bedtime & App Rule Editor            |  | - DevicePolicyManager / Accessibility Enforcer     |  |
|  | - Remote Command Dispatcher (Lock, Sync, Alarm)      |  | - Local Policy Cache & Offline Enforcement Engine  |  |
|  | - Consent Request Approval & Alert Feed              |  | - Remote Command Execution & ACK Dispatcher        |  |
|  +---------------------------+--------------------------+  +-------------------------+--------------------------+  |
+------------------------------|-------------------------------------------------------|-----------------------------+
                               | HTTPS (REST / Sanctum Bearer)                         | HTTPS (REST / Device Token)
                               | WSS (Reverb WebSocket)                                | WSS (Reverb WebSocket) & FCM
                               v                                                       v
+--------------------------------------------------------------------------------------------------------------------+
|                                           GATEWAY & INGRESS LAYER                                                  |
|  - Cloudflare WAF & DDoS Mitigation                                                                                |
|  - Rate Limiting: Authentication (5/min), Pairing Submissions (5/code), Telemetry Ingestion (60/min)                 |
|  - Global Security Headers: HSTS, CSP, X-Frame-Options: DENY, X-Content-Type-Options: nosniff                      |
+--------------------------------------------------------------------------------------------------------------------+
                               |
                               v
+--------------------------------------------------------------------------------------------------------------------+
|                                        CONNECTLY LARAVEL BACKEND TIER                                              |
|                                         (routes/parental.php)                                                      |
|                                                                                                                    |
|  [Presentation Layer]                                                                                              |
|   -> ParentalAuthController (Registration, Login, Token Refresh, Device Revocation)                                |
|   -> FamilyManagementController (Family CRUD, Member Roles, Child Profiles)                                        |
|   -> DevicePairingController (Cryptographic Pairing Code Generation, Device Handshake)                             |
|   -> UsageTelemetryController (Screen-time Ingestion, Daily Aggregation, Stale Telemetry Warnings)                  |
|   -> PolicyEngineController (Daily Limits, Bedtime Curfew, App Blacklist/Whitelist)                                |
|   -> RemoteCommandController (Command Queuing, Device Delivery, Execution Acknowledgment)                         |
|   -> ChildConsentController (Extra Time Requests, Parent Approval/Rejection)                                       |
|   -> SecurityAlertController (Tamper Detection, Device Inactive, Permission Loss)                                  |
|                                                                                                                    |
|  [Security & Authorization Layer]                                                                                  |
|   -> FamilyPolicy, FamilyMemberPolicy, ChildDevicePolicy, DeviceRestrictionPolicy, RemoteCommandPolicy              |
|   -> Sanctum Stateful & Device Bearer Token Guard with hardware fingerprint hash validation                        |
|                                                                                                                    |
|  [Domain & Service Layer]                                                                                          |
|   -> DevicePairingService, ScreenTimeAggregationService, PolicyEvaluationEngine, RemoteCommandService              |
|                                                                                                                    |
|  [Real-Time & Notification Pipeline]                                                                              |
|   -> Laravel Reverb Private Channels (`parent.{parentId}`, `device.{deviceId}`, `family.{familyId}`)               |
|   -> FCM High-Priority Push Relays (Push-to-Sync, Critical Alerts, Consent Requests)                              |
+--------------------------------------------------------------------------------------------------------------------+
                               |
                               v
+--------------------------------------------------------------------------------------------------------------------+
|                                       DATA PERSISTENCE & STORAGE TIER                                              |
|  +----------------------------------------------------+  +------------------------------------------------------+  |
|  |            MySQL 8.0 / InnoDB CLUSTER              |  |                    REDIS CLUSTER                     |  |
|  | - 12 Dedicated Core Parental Control Tables        |  | - Ephemeral Pairing Attempt Counters (Rate Limiter)  |  |
|  | - Row-Level Locking (`lockForUpdate`) on Pairing   |  | - Real-Time WebSocket Channel State & Heartbeats     |  |
|  | - Strict Foreign Keys with Cascade/Restrict Rules  |  | - Async Queue Pipelines (FCM, Aggregations)          |  |
|  | - Composite Indexes for Fast Time-Series Querying   |  | - Reconnect Token Cache (TTL 15 min)                 |  |
|  +----------------------------------------------------+  +------------------------------------------------------+  |
+--------------------------------------------------------------------------------------------------------------------+
```

---

## 2. Core Operational Data Flows

### 2.1 Device Pairing & Cryptographic Enrollment Sequence
```
Parent App                      Connectly Backend API                    Child Device Agent
    |                                    |                                       |
    | 1. POST /pairing-codes (family, child)                                     |
    |----------------------------------->|                                       |
    | 2. Generate random 6-digit code    |                                       |
    |    Hash code with SHA-256          |                                       |
    |    Store in `device_pairing_codes` |                                       |
    |    TTL: 10 min, max_attempts: 5    |                                       |
    |<-----------------------------------|                                       |
    | 3. Displays Code on Parent Screen  |                                       |
    |                                    | 4. Child enters code                  |
    |                                    |    POST /device/pair                  |
    |                                    |    {code, device_uuid, hardware_meta} |
    |                                    |<--------------------------------------|
    |                                    | 5. DB::transaction {                  |
    |                                    |      lockForUpdate() on code record   |
    |                                    |      Verify not expired, not used     |
    |                                    |      Verify attempts < 5              |
    |                                    |      Atomically mark `used_at = now()`|
    |                                    |      Register row in `child_devices`  |
    |                                    |      Issue Device Secret Token        |
    |                                    |    }                                  |
    |                                    | 6. Return device credential           |
    |                                    |-------------------------------------->|
    | 7. Push Event: DevicePaired        |                                       |
    |<===================================|                                       |
    | 8. Both screens display "Device Successfully Enrolled"                     |
```

### 2.2 Telemetry Ingestion & Aggregation Sequence
1. **Child Harvester:** Every 15 minutes (or on app pause), Child Agent queries `UsageStatsManager.queryUsageStats()` for foreground package intervals.
2. **Compression & Batching:** Chunks are formatted as `{package_name, duration_seconds, timestamp}`.
3. **Payload Submission:** Child Agent posts batch to `POST /api/v1/parental/devices/{device}/usage/sync`.
4. **Backend Processing:**
   - Validates device token against `child_devices` table.
   - Verifies device is not revoked.
   - De-duplicates identical time intervals (`unique(['child_device_id', 'package_name', 'event_started_at'])`).
   - Updates `screen_time_records` with daily aggregate using the family's configured timezone.
   - Evaluates active daily quota policy: if total usage > daily quota, triggers `PolicyExceeded` event.

### 2.3 Remote Command Lifecycle
1. **Creation:** Parent dispatches Instant Lock -> `POST /api/v1/parental/devices/{device}/commands` -> Record created in `remote_commands` with status `QUEUED`, UUID, and 15-minute expiration.
2. **Delivery:** Dispatched concurrently over Reverb private channel `device.{deviceId}` and High-Priority FCM push.
3. **Receipt & ACK:** Child Agent receives command, verifies signature and UUID, submits `POST /api/v1/parental/device/commands/{uuid}/acknowledge` -> Status becomes `ACKNOWLEDGED`.
4. **Execution:** Child Agent enforces supported Android system lock.
5. **Completion:** Child Agent posts `POST /api/v1/parental/device/commands/{uuid}/complete` -> Status becomes `COMPLETED` -> Parent UI updates to green checkmark.

---

## 3. Threat Modeling via STRIDE Framework

| Threat Category | Threat Description | Attack Vector | Connectly Mitigation Architecture |
| :--- | :--- | :--- | :--- |
| **Spoofing (S)** | Child or rogue agent impersonates Parent account or another child device. | Replaying stolen hardware IDs or spoofing authorization headers. | **Mitigation:** Devices authenticate via cryptographically random Bearer tokens stored in EncryptedSharedPreferences (Android Keystore). Hardware identifiers alone are NEVER used as authentication secrets. |
| **Tampering (T)** | Child modifies local SQLite usage logs or intercepts remote lock command. | Modifying local database or intercepting HTTP traffic via proxy. | **Mitigation:** Server-side authoritative aggregation; SSL certificate pinning on mobile clients; commands carry cryptographic UUID and timestamps; replay of executed commands is rejected. |
| **Repudiation (R)** | Parent denies approving extra screen-time; child claims command was never sent. | Disputed decisions or claims of silent actions. | **Mitigation:** Append-only immutable `family_audit_logs` table records actor ID, role, action, IP address, and timestamp for all policy changes, consent approvals, and commands. |
| **Information Disclosure (I)** | Parent A views screen-time telemetry, app logs, or location of Child B (Cross-family IDOR). | Manipulating device ID or child member ID parameters in REST calls. | **Mitigation:** Strict Laravel Policy gates (`FamilyPolicy`, `ChildDevicePolicy`) enforce `$user->family_id === $device->family_id`. Violations instantly abort with HTTP 403 Forbidden. |
| **Denial of Service (D)** | Attacker brute-forces 6-digit pairing codes; rogue device floods usage sync. | Automated script iterating 000000-999999; massive JSON payload floods. | **Mitigation:** Pairing codes expire in 10 minutes; `failed_attempts` counter locks out code after 5 failures; rate limiters enforce 5 pairing requests/min per IP. Payload size strictly validated. |
| **Elevation of Privilege (E)** | Child account calls parent-only endpoints to disable daily limits or unblock apps. | Child client invokes `PATCH /policies` or `POST /commands`. | **Mitigation:** Role-based middleware (`EnsureFamilyRole:parent,guardian`) rejects any token associated with a child account with HTTP 403. |

---

## 4. Privacy & Ethical Safeguards (Zero Covert Surveillance)

1. **Explicit Child Disclosure:** The Child Agent displays a persistent, non-dismissible Android notification: *"Connectly is active. Your daily limits and app rules are managed by your family."*
2. **Zero Covert Sensors:** No background microphone activation, no background camera capture, and no background screen recording.
3. **No Private Content Collection:** Connectly monitors package names, app categories, and duration in foreground. It **never** reads message text, keystrokes, browser form contents, or photos.
4. **Supervised Media Protocol:** Any future LiveKit supervisory session requires explicit real-time child consent on the device screen before camera or microphone streams can initialize.

