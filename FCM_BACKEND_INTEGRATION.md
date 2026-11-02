# CONNECTLY — FCM BACKEND INTEGRATION REPORT
## Phase 10: Laravel 13 Push Notification Service & Device Session Integration

| Component | Target File | Verification Status |
| :--- | :--- | :--- |
| **Push Notification Service** | `app/Services/Push/PushNotificationService.php` | **PASS** |
| **Device Registration API** | `POST /api/v1/devices/register` | **PASS** |
| **Device Revocation API** | `DELETE /api/v1/devices/{id}` | **PASS** |
| **Device Session Manager** | `app/Services/Messenger/DeviceSessionService.php` | **PASS** |
| **VoIP Call Push Dispatch** | `CallingService.php -> sendVoipCallPush` | **PASS** |
| **Chat Message Push Dispatch** | `PushNotificationService::sendMessagePush` | **PASS** |
| **Feature Test Suite** | `MobileFcmPushNotificationTest.php` | **PASS** (6/6 tests) |
| **Pint Code Style** | `vendor/bin/pint --test` | **PASS** (0 issues) |

---

## 1. Backend Architecture & Dispatch Flow

The backend push notification engine is built directly into Laravel 13 with multi-platform routing (`android` -> FCM, `ios`/`macos` -> APNs, `web` -> WebPush):

```
       ┌──────────────────────────────────────────────────┐
       │   JUGAJUG CallingService / MessagingService      │
       └────────────────────────┬─────────────────────────┘
                                │
                                ▼
       ┌──────────────────────────────────────────────────┐
       │     PushNotificationService::sendVoipCallPush    │
       │     PushNotificationService::sendMessagePush     │
       └────────────────────────┬─────────────────────────┘
                                │
               Fetch active DeviceSessions for User
               where is_revoked = false and push_token IS NOT NULL
                                │
                                ▼
       ┌──────────────────────────────────────────────────┐
       │       sendFcmNotification(DeviceSession, ...)    │
       └──────────────┬───────────────────────────┬───────┘
                      │                           │
          FCM HTTP v1 Endpoint            Legacy Endpoint Fallback
    (https://fcm.googleapis.com/v1)   (https://fcm.googleapis.com/fcm/send)
                      │                           │
                      ▼                           ▼
        If HTTP 404 / UNREGISTERED: Automatically mark
        is_revoked = true & push_token = null (Token Hygiene)
```

---

## 2. API Contracts & Payload Structures

### A. Device Registration (`POST /api/v1/devices/register`)
- **Headers**: `Authorization: Bearer <Sanctum-Token>`, `Accept: application/json`
- **Request Body**:
```json
{
  "device_id": "c9284fae-128f-41e9-9231-7e8220ef129b",
  "device_name": "Google Pixel 8",
  "platform": "android",
  "app_version": "1.0.0",
  "push_token": "fXy9_fcm_token_example..."
}
```
- **Response**: `200 OK`
```json
{
  "status": "success",
  "message": "Device session registered successfully.",
  "data": {
    "id": 42,
    "user_id": 15,
    "device_id": "c9284fae-128f-41e9-9231-7e8220ef129b",
    "device_name": "Google Pixel 8",
    "platform": "android",
    "push_token": "fXy9_fcm_token_example...",
    "is_revoked": false,
    "last_active_at": "2026-11-02T12:00:00Z"
  }
}
```

### B. Device Revocation (`DELETE /api/v1/devices/{id}`)
- **Headers**: `Authorization: Bearer <Sanctum-Token>`
- **Response**: `200 OK`
```json
{
  "status": "success",
  "message": "Device session revoked successfully.",
  "data": {
    "revoked": true
  }
}
```

### C. Incoming Call VoIP Push Payload
- **Priority**: `high`
- **TTL**: `45s`
- **Data Payload**:
```json
{
  "type": "call.incoming",
  "action": "call.incoming",
  "call_id": "84920",
  "uuid": "call_sec_84920_ab",
  "conversation_id": "104",
  "call_type": "audio",
  "room_id": "room_84920",
  "caller_id": "12",
  "caller_name": "Mizanur Rahman",
  "caller_avatar": "https://example.com/avatar.jpg",
  "priority": "high",
  "ttl": "45s"
}
```

---

## 3. Safe Credential Management Protocol

> [!IMPORTANT]
> In accordance with enterprise zero-credential-leakage policy, production Firebase Admin credentials and service account JSON keys are NOT stored in Git repositories.

### Configuration Protocol:
1. **Environment Variables**:
   In `.env` or `.env.production`:
   ```env
   FCM_PROJECT_ID=jugajug-connectly-prod
   FCM_SERVER_KEY="Bearer ya29.c.b0..." # Or service account token generator
   ```
2. **Production Service Account Secret Injection**:
   In production CI/CD (GitHub Secrets / Laravel Cloud / Docker Secret Mounts):
   Mount `firebase-service-account.json` securely to `storage/credentials/firebase.json` (chmod 600) and reference via `FIREBASE_CREDENTIALS=/path/to/firebase.json`.
3. **Automated Token Revocation**:
   When Firebase responds with `404`, `UNREGISTERED`, or `NotRegistered`, `PushNotificationService` immediately updates the database row:
   ```php
   $device->update(['is_revoked' => true, 'push_token' => null]);
   ```
   This prevents wasteful dispatch and protects API quotas.

---

## 4. Verification Verdict

- Laravel Device Registration Endpoint: **PASS**
- Laravel Device Session Revocation: **PASS**
- Push Notification Token Hygiene: **PASS**
- Unit & Feature Test Suite: **PASS** (`MobileFcmPushNotificationTest` 6/6 passed)
- Physical Device Cloud Delivery: **NOT VERIFIED** (Pending user cloud credentials)

