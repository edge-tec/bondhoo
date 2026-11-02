# FCM PRODUCTION VALIDATION & NOTIFICATION LIFECYCLE AUDIT

**Target:** Firebase Cloud Messaging (FCM) v1 API & Connectly Android Notification Pipeline  
**Android Dependencies:** `com.google.firebase:firebase-messaging:24.1.0` (Firebase BoM 33.7.0)  
**Backend Driver:** `PushNotificationService.php` + Google API Client / Guzzle HTTP v1  
**Audit Date:** November 2026  

---

## 1. Executive Summary

Push notification delivery is vital for real-time mobile applications to wake dormant devices for incoming WebRTC calls and deliver asynchronous chat messages. This audit evaluates the Android client implementation, background service lifecycle, token management, backend dispatch logic, and actual delivery status.

| Inspection Item | Status | Verification Detail |
| :--- | :---: | :--- |
| **Android Messaging Service** | `PASS` | `ConnectlyFirebaseMessagingService` properly extends `FirebaseMessagingService` |
| **Manifest Registration** | `PASS` | Declared with `exported="false"` and `MESSAGING_EVENT` intent filter |
| **Device Token Registration API** | `PASS` | `POST /api/v1/devices/tokens` stores token, platform, and active status |
| **Full-Screen Incoming Call Intent** | `PASS` | `CallNotificationManager` creates heads-up + full-screen intent for locked states |
| **WakeLock Management** | `PASS` | `PARTIAL_WAKE_LOCK` acquired safely during push processing with 10s timeout |
| **Backend FCM HTTP v1 Dispatcher** | `PASS` | `PushNotificationService` builds RFC-compliant FCM v1 payloads |
| **Real Google Cloud FCM Delivery** | `NOT VERIFIED` | Live delivery unverified; real Firebase service-account JSON is pending setup |

---

## 2. Android Client Notification Architecture

### 2.1 Service Implementation
- **File:** [ConnectlyFirebaseMessagingService.kt](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/app/src/main/java/com/connectly/android/service/ConnectlyFirebaseMessagingService.kt)
- **Token Lifecycle:**
  - `onNewToken(token: String)`: Injects `DeviceTokenManager` via Hilt and triggers immediate registration with JUGAJUG backend `/api/v1/devices/tokens`.
- **Payload Handling:**
  - `onMessageReceived(remoteMessage: RemoteMessage)`:
    - Parses both `notification` and `data` payloads.
    - Inspects `type` field:
      - `type == "call"`: Triggers `CallNotificationManager.showIncomingCallNotification()` with high priority and ringtone sound.
      - `type == "message"`: Triggers `NotificationManager.showMessageNotification()` and dispatches local event synchronization.

### 2.2 Call Notification Channel & Full-Screen Intent
- **Channel ID:** `connectly_calls_high_priority`
- **Importance:** `NotificationManager.IMPORTANCE_HIGH`
- **Features:**
  - Custom ringtone URI with `AudioAttributes.USAGE_VOICE_COMMUNICATION_SIGNALLING`.
  - Configured with `setFullScreenIntent(fullScreenPendingIntent, true)` allowing incoming calls to show over lockscreen.
  - Action buttons: "Accept" (launches `CallActivity` with `ACTION_ACCEPT`) and "Decline" (sends broadcast to `CallActionBroadcastReceiver` with `ACTION_DECLINE`).

### 2.3 Audio/CPU WakeLock Protection
- In accordance with Android power management guidelines:
  ```kotlin
  val wakeLock = powerManager.newWakeLock(
      PowerManager.PARTIAL_WAKE_LOCK,
      "Connectly:IncomingCallWakeLock"
  )
  wakeLock.acquire(10_000L) // Safe 10s auto-release timeout
  ```
- Protects CPU execution while the OS binds audio drivers and prepares WebRTC peer factory.

---

## 3. Backend Push Notification Dispatch Audit

### 3.1 Service Implementation
- **File:** [PushNotificationService.php](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/app/Services/PushNotificationService.php)
- **Payload Construction:**
  - Standardized JSON structure targeting FCM HTTP v1:
  ```json
  {
    "message": {
      "token": "dK...4x",
      "priority": "HIGH",
      "android": {
        "priority": "HIGH",
        "ttl": "60s"
      },
      "data": {
        "type": "call",
        "call_id": "992",
        "caller_id": "14",
        "caller_name": "Test User",
        "call_type": "video",
        "session_token": "tok_xyz"
      }
    }
  }
  ```
- **Error Handling:** Invalid or expired registration tokens (e.g., `UNREGISTERED` / `INVALID_ARGUMENT`) trigger automatic deactivation (`is_active = false`) in `device_tokens` table.

---

## 4. Pending Cloud Credentials & Live Verification

- **Current State:**
  - The Android `google-services.json` file in `android/app/` contains a configured development project template.
  - The Laravel backend environment variables `FIREBASE_CREDENTIALS` or `FIREBASE_PROJECT_ID` require production service-account credentials.
- **Delivery Status:** `NOT VERIFIED` (Cannot dispatch physical over-the-air packets to Google FCM servers until production service credentials are provided).
- **Automated Logic Status:** `PASS` (All payload creation, token validation, and mock delivery unit tests pass).

---

## 5. FCM Validation Gate Status

- **Android Client Code & Service Lifecycle:** `PASS`
- **Backend Queue & Token Management:** `PASS`
- **Live Device Cloud Push Delivery:** `NOT VERIFIED` (Pending production Firebase Service Account JSON).

