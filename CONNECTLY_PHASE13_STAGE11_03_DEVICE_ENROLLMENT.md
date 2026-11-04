# CONNECTLY — PHASE 13 STAGE 11.03
## SECURE DEVICE ENROLLMENT & PAIRING SPECIFICATION

**Platform:** Connectly Parental Control & Family Digital Safety Platform  
**Audit Date:** November 05, 2026  
**Auditor:** Principal Software Architect & QA Engineer  
**Status:** IMPLEMENTED & AUDITED  

---

### 1. Enrollment Lifecycle Flow

```mermaid
sequenceDiagram
    autonumber
    actor Parent as Parent (Web/App)
    participant API as Connectly Backend
    participant DB as MySQL Database
    actor Child as Child Device Agent

    Parent->>API: POST /api/v1/parental/families/{id}/pairing-codes (child_member_id)
    API->>DB: Store SHA-256 hash, 10-min TTL in device_pairing_codes
    API-->>Parent: Display 6-digit numeric code & expiry
    Child->>API: POST /api/v1/parental/device/pair (code, device_uuid, device_name)
    API->>DB: SELECT FOR UPDATE on device_pairing_codes (atomic redemption)
    Note over API,DB: Check: used_at IS NULL, failed_attempts < 5, expires_at > NOW()
    API->>DB: Stamp used_at = NOW(), enroll in child_devices
    API-->>Child: Return device_token (cpd_...)
    Child->>Child: Store token in EncryptedSharedPreferences
```

---

### 2. Edge Cases & Attack Mitigations Tested

1. **Expired Pairing Code:** If `expires_at <= now()`, redemption is rejected with HTTP 422.
2. **Replay / Single-Use:** Reusing the exact same pairing code is rejected immediately with HTTP 422 (Verified in automated tests).
3. **Brute Force Protection:** Maximum 5 failed attempts per code hash. Endpoints protected with route rate limiting (`throttle:10,1`).
4. **Concurrency Safety:** Enforced via MySQL InnoDB `lockForUpdate()` preventing race conditions during concurrent redemption attempts.

