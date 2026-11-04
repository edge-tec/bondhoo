# CONNECTLY — PHASE 13 STAGE 11.13 TO 11.15
## OFFLINE-FIRST SYNC, API CONTRACT & THREAT MODEL AUDIT

**Platform:** Connectly Parental Control & Family Digital Safety Platform  
**Audit Date:** November 05, 2026  
**Status:** IMPLEMENTED & AUDITED  

---

### 1. Offline-First Synchronization Architecture (Stage 11.13)

- **Android Client Queue:**
  - WorkManager background workers (`UsageSyncWorker`, `PolicySyncWorker`) buffer events locally if offline.
  - Exponential backoff retry policy triggers automatically when network connectivity resumes.
- **Backend Idempotency & Conflict Handling:**
  - `POST /api/v1/parental/device/usage/sync` uses transaction-safe upserts on `(child_device_id, application_package, usage_date)`.
  - Re-syncing the same offline logs does not artificially inflate screen-time numbers.
  - Remote commands accept `idempotency_key` (UUID) preventing duplicate execution upon reconnection.

---

### 2. Complete Parental Control API Contract (Stage 11.14)

| Endpoint | Method | Auth | Request Body | Response | Throttling |
|---|---|---|---|---|---|
| `/api/v1/parental/device/pair` | POST | None | `pairing_code, device_uuid, device_name, platform` | `{device_id, device_token}` | 10/min |
| `/api/v1/parental/device/rotate-token` | POST | Bearer Token | Empty | `{device_token}` | 60/min |
| `/api/v1/parental/device/usage/sync` | POST | Bearer Token | `events: [{application_package, duration_seconds, event_started_at}]` | `{records_ingested}` | 60/min |
| `/api/v1/parental/device/policies` | GET | Bearer Token | Query | `{policies: {bedtime_curfew, blacklisted_packages, ...}}` | 60/min |
| `/api/v1/parental/device/commands/pending` | GET | Bearer Token | Query | `{commands: [...]}` | 60/min |
| `/api/v1/parental/device/commands/{uuid}/ack` | POST | Bearer Token | `status, failure_reason` | `{status}` | 60/min |
| `/api/v1/parental/device/apps/inventory` | POST | Bearer Token | `apps: [{package_name, app_name}]` | `{total_apps}` | 30/min |
| `/api/v1/parental/device/consent-requests` | POST | Bearer Token | `request_type, requested_value, reason` | `{request_id}` | 20/min |
| `/api/v1/parental/device/alerts` | POST | Bearer Token | `alert_type, description, severity` | `{alert_id}` | 30/min |
| `/api/v1/parental/families` | POST | Sanctum | `name, timezone` | `{family}` | 60/min |
| `/api/v1/parental/families/{family}` | GET | Sanctum | None | `{family: {members, restrictions}}` | 60/min |
| `/api/v1/parental/families/{family}` | PUT | Sanctum | `name, timezone` | `{family}` | 60/min |
| `/api/v1/parental/families/{family}/activity` | GET | Sanctum | None | `{activity_logs: [...]}` | 60/min |
| `/api/v1/parental/families/{family}/members` | POST | Sanctum | `name, role, user_id` | `{member}` | 60/min |
| `/api/v1/parental/families/{family}/members/{id}` | PUT | Sanctum | `name, role` | `{member}` | 60/min |
| `/api/v1/parental/families/{family}/members/{id}` | DELETE | Sanctum | None | `{message}` | 60/min |
| `/api/v1/parental/families/{family}/pairing-codes` | POST | Sanctum | `child_member_id` | `{pairing_code, expires_at}` | 15/min |
| `/api/v1/parental/children/{child}/screen-time` | GET | Sanctum | `date` | `{screen_time: {total_seconds, apps}}` | 60/min |
| `/api/v1/parental/children/{child}/screen-time/weekly` | GET | Sanctum | `date` | `{weekly_summary}` | 60/min |
| `/api/v1/parental/children/{child}/screen-time/quota` | GET | Sanctum | `date` | `{quota}` | 60/min |
| `/api/v1/parental/families/{family}/restrictions` | POST | Sanctum | `child_member_id, restriction_type, configuration` | `{restriction}` | 60/min |
| `/api/v1/parental/devices/{device}/commands` | POST | Sanctum | `command_type, payload, idempotency_key` | `{command}` | 60/min |
| `/api/v1/parental/devices/{device}/revoke` | POST | Sanctum | None | `{message}` | 60/min |
| `/api/v1/parental/families/{family}/consent-requests` | GET | Sanctum | None | `{consent_requests}` | 60/min |
| `/api/v1/parental/consent-requests/{req}/respond` | POST | Sanctum | `status, response_note` | `{consent_request}` | 60/min |
| `/api/v1/parental/families/{family}/alerts` | GET | Sanctum | None | `{alerts}` | 60/min |
| `/parental` | GET | Web Auth | None | Inertia View | 60/min |

---

### 3. Dedicated Threat Model & STRIDE Analysis (Stage 11.15)

| Threat | Attack Scenario | Impact | Likelihood | Mitigation | Verification |
|---|---|---|---|---|---|
| **Spoofing** | Adversary attempts to guess child device bearer token | Critical | Low | 48-char random tokens (`cpd_...`), SHA-256 stored, `hash_equals()` constant-time check | Test 19 |
| **Tampering** | Man-in-the-middle modifies usage telemetry logs | High | Low | HTTPS transport, token validation, server-side duration constraints | Test 7 |
| **Repudiation** | Guardian denies modifying screen limits | Medium | Low | Immutable `family_audit_logs` tracking IP, user ID, timestamp, and action | Test 18 |
| **Information Disclosure** | Cross-family parent views another child's app usage | Critical | Low | `FamilyPolicy` and strict guardian checks (HTTP 403 Forbidden) | Test 3 & 13 |
| **Denial of Service** | Botnet floods pairing endpoint to lock codes | High | Medium | Route rate limiting (`throttle:10,1`), max 5 attempts per code | Test 6 |
| **Elevation of Privilege** | Child attempts to invoke parent API to unblock device | Critical | Low | Sanctum authentication requirement (`auth:sanctum`), child device token rejected | Test 14 |

