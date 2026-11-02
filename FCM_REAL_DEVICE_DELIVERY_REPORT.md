# FCM REAL-DEVICE DELIVERY & PUSH LIFECYCLE AUDIT

**Target:** Firebase Cloud Messaging (FCM) v1 Engine  
**Client:** `ConnectlyFirebaseMessagingService` (`com.connectly.android.staging`)  
**Backend:** `PushNotificationService.php` + Google API Client  
**Date:** November 2026  
**Status:** `CLIENT/BACKEND LOGIC PASS — CLOUD DELIVERY NOT VERIFIED`  

---

## 1. Executive Summary

Firebase Cloud Messaging delivers out-of-band wakeups for incoming WebRTC calls and offline chat messages. This audit validates the client service integration, token lifecycle synchronization, background wake-lock handling, and delivery states across different Android power management states.

---

## 2. Notification Dispatch & Lifecycle Architecture

```
+-----------------------------------------------------------------------------------+
| JUGAJUG Backend (PushNotificationService)                                         |
| -> Construct FCM v1 High-Priority Data Payload with android.priority="HIGH"       |
+-----------------------------------------------------------------------------------+
                                          |
                                          v
+-----------------------------------------------------------------------------------+
| Google FCM v1 Infrastructure (Google Play Services Push Gateway)                   |
+-----------------------------------------------------------------------------------+
                                          |
                                          v
+-----------------------------------------------------------------------------------+
| Connectly Android Client (ConnectlyFirebaseMessagingService.kt)                   |
| 1. onMessageReceived() catches remote data payload                                |
| 2. Acquire PowerManager.PARTIAL_WAKE_LOCK (10s max timeout)                       |
| 3. CallNotificationManager.showIncomingCallNotification()                         |
| 4. Displays full-screen heads-up intent + custom ringtone                         |
| 5. User taps Accept -> Launches CallActivity with ACTION_ACCEPT                   |
+-----------------------------------------------------------------------------------+
```

---

## 3. Real-Device Push Delivery Test Matrix

| Test ID | Device State / Scenario | Expected Outcome | Verification Detail | Status |
| :--- | :--- | :--- | :--- | :---: |
| **FCM-01** | App in Foreground | Message delivered via WebSocket or silent push; in-app banner | In-app notification dispatcher verified | `PASS` |
| **FCM-02** | App in Background | High-priority push triggers heads-up incoming call alert | System notification channel verified | `PASS` (Code) / `NOT VERIFIED` (Cloud) |
| **FCM-03** | App Swiped / Killed | FCM wakes application process; incoming call notification displays | Service declared in Manifest with proper priority | `PASS` (Code) / `NOT VERIFIED` (Cloud) |
| **FCM-04** | Android Doze Mode | High-priority message bypasses Doze battery restriction | `android.priority = "HIGH"` enforced in backend | `PASS` (Code) / `NOT VERIFIED` (Cloud) |
| **FCM-05** | Screen Locked | Full-screen intent turns on screen; shows call controls | `setFullScreenIntent` configured on channel | `PASS` (Code) / `NOT VERIFIED` (Cloud) |
| **FCM-06** | Accept Action Button | Tapping "Accept" launches `CallActivity` and connects | `PendingIntent` with `ACTION_ACCEPT` verified | `PASS` |
| **FCM-07** | Decline Action Button | Tapping "Decline" triggers `CallActionBroadcastReceiver` | Broadcast receiver dismisses notification | `PASS` |
| **FCM-08** | Token Refresh Event | `onNewToken` pushes new token to `/api/v1/devices/tokens` | Injected `DeviceTokenManager` tested | `PASS` |
| **FCM-09** | User Logout | Calling logout deactivates device token (`is_active = false`) | Unit test `DeviceTokenManagementTest` passed | `PASS` |
| **FCM-10** | Duplicate Push Payload | App ignores second push with identical `call_id` | LRU deduplication cache verified | `PASS` |

---

## 4. Current Constraints & Blockers

- **Credential Dependency:** Over-the-air push delivery to real devices requires active Google Cloud Firebase service-account credentials configured in the staging backend (`FIREBASE_CREDENTIALS`).
- **Delivery Status:** `NOT VERIFIED` (Cloud push delivery remains unverified until real Firebase credentials are provisioned).
- **Codebase Implementation:** `PASS` (All code paths, receivers, and test assertions are fully verified).

