# CONNECTLY — PHASE 13 STAGE 11.07 TO 11.10
## APPLICATION RESTRICTIONS, REMOTE COMMANDS, REALTIME & CONSENT

**Platform:** Connectly Parental Control & Family Digital Safety Platform  
**Audit Date:** November 05, 2026  
**Status:** IMPLEMENTED & AUDITED  

---

### 1. Application Inventory & Restrictions (Stage 11.07)
- **Inventory Sync:** `POST /api/v1/parental/device/apps/inventory` enables child devices to synchronize installed application metadata (`package_name`, `app_name`, `is_system_app`).
- **Parental Controls:** Parents can configure package limits or add apps to the blacklist.
- **Operating System Boundary:** Operating system enforcement is restricted to supported Android APIs (e.g., overlay reminders or managed profile rules), with zero deceptive automation.

---

### 2. Remote Command Infrastructure (Stage 11.08)
- **Supported Commands:** `device_lock`, `device_unlock`, `policy_sync`, `sound_alarm`, `telemetry_flush`.
- **Lifecycle:** `queued` → `sent` → `acknowledged` → `completed` / `rejected` / `failed`.
- **Idempotency & Replay Protection:** Commands accept an `idempotency_key` (UUID). Retrying with the same key returns the existing command rather than queueing duplicates.

---

### 3. Private Real-Time Broadcasting (Stage 11.09)
- **Channels in `routes/channels.php`:**
  - `private-family.{familyId}.guardians`: Only authenticated parents/guardians belonging to that family can subscribe.
  - `private-device.{deviceId}.commands`: Strictly authorized to parents and the assigned device.
- **Broadcast Events:**
  - `ChildSecurityAlertRaised`
  - `ConsentRequestCreated`
  - `RemoteCommandQueued`

---

### 4. Consent Requests & Security Alerts (Stage 11.10)
- **Child Escalation Flow:**
  - Child requests extra time via `POST /api/v1/parental/device/consent-requests`.
  - Parents view pending requests and approve/reject via `/consent-requests/{id}/respond`.
- **Tamper & Security Health Detection:**
  - Device agent triggers alert if Usage Access or Device Administrator permissions are disabled.
  - Alerts are categorized by severity: `low`, `medium`, `high`, `critical`.

