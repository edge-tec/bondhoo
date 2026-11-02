# CONNECTLY — FCM DEFECT REGISTER
## Phase 10: Real-Time Defect Status, Impact, and Resolution Log

| Defect ID | Description | Severity | Origin Phase | Current Status | Resolution |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **DEF-RT-01** | Android WebRTC calling engine was mocked using synthetic SDP strings and fake ICE candidates | Critical | Phase 8 | **RESOLVED** (Phase 9) | Integrated `stream-webrtc-android:1.3.10` native WebRTC SDK with real PeerConnection lifecycle, camera capturer, and audio focus management. |
| **DEF-RT-02** | Firebase Messaging SDK missing, `ConnectlyFirebaseMessagingService` extended standard `Service`, missing `google-services.json` | Critical | Phase 8 | **RESOLVED** (Phase 10) | Integrated Firebase BoM 33.7.0, `firebase-messaging`, Google Services Gradle plugin 4.4.2, refactored service to extend `FirebaseMessagingService`, and connected push to `CallSessionManager`. |
| **DEF-FCM-01** | Missing BroadcastReceiver for Call Notification action buttons ("Accept", "Decline", "End Call") | Moderate | Phase 10 Audit | **RESOLVED** (Phase 10) | Implemented `CallActionBroadcastReceiver`, wired intents `ACTION_ACCEPT_CALL`, `ACTION_REJECT_CALL`, `ACTION_END_CALL` to `CallSessionManager` and registered in Manifest. |
| **DEF-FCM-02** | `DeviceTokenManager` lacked backend token revocation on logout, leading to potential notification leaks after account switch | High | Phase 10 Audit | **RESOLVED** (Phase 10) | Added `revokeToken()` calling `DELETE /v1/devices/{id}` on backend, deleting token via `FirebaseMessaging.getInstance().deleteToken()`, and clearing preferences. |
| **DEF-FCM-03** | Missing full-screen intent and lockscreen wake permissions for Android 10+ and Android 14+ incoming call alerts | High | Phase 10 Audit | **RESOLVED** (Phase 10) | Added `USE_FULL_SCREEN_INTENT`, `VIBRATE`, and `WAKE_LOCK` permissions in `AndroidManifest.xml`. |
| **DEF-FCM-04** | Physical device push delivery unverified in local development environment | Informational | Phase 10 | **OPEN (NOT VERIFIED)** | Requires target physical hardware connected to Google Play Services and production Firebase project credentials. |

---

## Detailed Defect Resolution Analysis

### DEF-RT-02 Resolution:
1. **Root Cause**: The service class was a skeleton placeholder extending `android.app.Service` without overriding `onNewToken` or `onMessageReceived`. The project lacked `com.google.gms.google-services` plugin and `firebase-messaging` library in Gradle.
2. **Impact**: Device was unable to receive Firebase background push messages or handle incoming call push notifications.
3. **Fix Details**:
   - Added `firebase-bom:33.7.0` and `firebase-messaging` to `libs.versions.toml`.
   - Applied `com.google.gms.google-services:4.4.2` to `:app`.
   - Refactored `ConnectlyFirebaseMessagingService` to extend `FirebaseMessagingService`.
   - Injected `CallNotificationManager`, `CallSessionManager`, and `NotificationChannelManager`.
   - Provided documented placeholder `android/app/google-services.json` so build compiles cleanly.
4. **Verification**: Full `:app:assembleDebug` passed, 16/16 unit tests passed.

### DEF-FCM-01 Resolution:
1. **Root Cause**: `CallNotificationManager` created pending broadcast intents (`ACTION_ACCEPT_CALL`, `ACTION_REJECT_CALL`), but there was no registered BroadcastReceiver to consume them.
2. **Impact**: Clicking "Accept" or "Decline" on incoming call notifications resulted in no action.
3. **Fix Details**:
   - Created `CallActionBroadcastReceiver` in `core:webrtc`.
   - Registered receiver with action filter in `android/app/src/main/AndroidManifest.xml`.
   - On accept: dismisses notification, triggers `callSessionManager.acceptCall(callId)`, and launches app calling screen.
   - On reject: dismisses notification and triggers `callSessionManager.rejectCall()`.

### DEF-FCM-02 Resolution:
1. **Root Cause**: Device session was only registered via `POST /api/v1/devices/register` but never revoked when a user logged out.
2. **Impact**: If a user logged out and another user logged into the same device (or if user logged out completely), FCM would still dispatch private chat and call notifications to the device.
3. **Fix Details**:
   - Added `DELETE /v1/devices/{id}` Retrofit contract in `ConnectlyApi.kt`.
   - Implemented `revokeToken()` in `DeviceTokenManager.kt` which calls backend API revocation, clears local SharedPreferences, and calls `FirebaseMessaging.getInstance().deleteToken()`.

---

## Current Status Summary
- **Critical Defects**: 0 Open
- **High Defects**: 0 Open
- **Moderate Defects**: 0 Open
- **Informational / Physical Hardware Environment**: 1 Tracked (`DEF-FCM-04`: `NOT VERIFIED`)

