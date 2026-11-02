# FCM STAGING QA & PUSH NOTIFICATION VALIDATION REPORT

**Target Platform:** Firebase Cloud Messaging (FCM) v1 Engine  
**Client:** `ConnectlyFirebaseMessagingService` (`com.connectly.android.staging`)  
**Backend:** `PushNotificationService.php` + Google API Client / Laravel Queue  
**Date:** November 2026  
**Status:** `INTEGRATION CODE PASS — CLOUD DISPATCH NOT VERIFIED`  

---

## 1. Executive Summary

This report assesses the end-to-end integration of Firebase Cloud Messaging for out-of-band call signaling and chat notifications. 

In strict adherence to QA rules, **we do not claim live cloud push delivery based solely on unit tests**. The code-level implementation, device registration API, and token lifecycle handling are verified as `PASS`. Physical over-the-air packet transmission to sleeping devices is categorized as `NOT VERIFIED` pending the deployment of the staging Firebase Admin SDK credentials JSON on the staging server.

---

## 2. Token Lifecycle & Registration Telemetry

### 2.1 Device Token Registration API
- **Endpoint:** `POST /api/v1/devices/tokens`
- **Controller:** [DeviceSessionController.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Http/Controllers/Api/V1/DeviceSessionController.php)
- **Database Table:** `device_tokens`
- **Attributes Stored:** `user_id`, `token`, `platform` (`android`), `is_active` (`true`), `last_seen_at`.
- **Test Result:** Passing in `DeviceTokenManagementTest` (verified token creation, uniqueness, and active status updates).

### 2.2 Client-Side Token Acquisition
Inside [DeviceTokenManager.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/feature/notifications/src/main/java/com/connectly/android/feature/notifications/data/fcm/DeviceTokenManager.kt):
```kotlin
FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
    if (task.isSuccessful) {
        val token = task.result
        registerTokenWithBackend(token)
    }
}
```
- **Lifecycle:** Tested via `DeviceTokenManagerTest`. On token refresh, `onNewToken()` receives the new token string and syncs it with the backend API.

---

## 3. FCM Push Notification Test Matrix

| Test ID | Scenario | Preconditions | Expected Mechanism | Verification Status |
| :--- | :--- | :--- | :--- | :---: |
| **FCM-QA-01** | Device Token Registration | User logs in on Android | Token uploaded to `/api/v1/devices/tokens` with `is_active=1` | `PASS` |
| **FCM-QA-02** | Token Invalidation on Logout | User taps Logout | Backend sets `is_active=0` for token; token removed | `PASS` |
| **FCM-QA-03** | Account Switch Token Rebind | User A logs out; User B logs in | Token re-bound to User B in `device_tokens` table | `PASS` |
| **FCM-QA-04** | Foreground Message Push | App actively displayed | WebSocket delivers message; FCM suppressed or silent | `PASS` |
| **FCM-QA-05** | Background Incoming Call | App minimized to home | High-priority data push wakes app; shows heads-up notification | `PASS` (Code) / `NOT VERIFIED` (Cloud) |
| **FCM-QA-06** | App Swiped / Killed | App process killed | High-priority push launches service; plays ringtone | `PASS` (Code) / `NOT VERIFIED` (Cloud) |
| **FCM-QA-07** | Android Doze Mode | Device stationary & dark | High-priority payload bypasses Doze maintenance window | `PASS` (Code) / `NOT VERIFIED` (Cloud) |
| **FCM-QA-08** | Screen Locked Wakeup | Display off & secured | Full-screen intent turns on screen via `WakeLock` (10s timeout) | `PASS` (Code) / `NOT VERIFIED` (Cloud) |
| **FCM-QA-09** | Accept / Decline Action | Notification displayed | Tapping "Accept" launches `CallActivity`; "Decline" cancels | `PASS` |
| **FCM-QA-10** | Expired Call Push | Callee offline > 60s | TTL expiration causes FCM to drop stale call payload | `PASS` (Backend TTL verified) |

---

## 4. Current Blockers for Full Cloud Validation

1. **Staging Firebase Service Account Credentials:**
   - The backend service requires a staging Google Cloud service account JSON file placed at:
     `/etc/connectly/staging-firebase-adminsdk.json`.
   - Without this key, the Laravel HTTP v1 client cannot authenticate with Google's OAuth2 push endpoint.
2. **Physical Device Fleet:**
   - Physical devices with Google Play Services installed are required to observe over-the-air latency and Doze mode bypass behavior.

---

## 5. Sign-Off & Status

- **Architecture & Local Logic:** `PASS`
- **Cloud Delivery to Real Hardware:** `NOT VERIFIED` (Pending staging credentials and test devices).

