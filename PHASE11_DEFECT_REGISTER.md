# CONNECTLY — PHASE 11 DEFECT REGISTER
## Comprehensive Audit, Defect Tracking, and Remediation Log

| Defect ID | Description | Severity | Origin Phase | Current Status | Remediation & Details |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **DEF-RT-01** | Android WebRTC calling was mocked using synthetic SDP strings and fake ICE candidates | Critical | Phase 8 | **RESOLVED** (Phase 9) | Integrated `stream-webrtc-android:1.3.10` native WebRTC SDK with real PeerConnection lifecycle, camera capturer, and audio focus management. |
| **DEF-RT-02** | Firebase Messaging SDK missing, `ConnectlyFirebaseMessagingService` extended standard `Service`, missing `google-services.json` | Critical | Phase 8 | **RESOLVED** (Phase 10) | Integrated Firebase BoM 33.7.0, `firebase-messaging`, Google Services Gradle plugin 4.4.2, refactored service to extend `FirebaseMessagingService`. |
| **DEF-RT-03** | Production TURN server credentials unconfigured; `CallingService` rejected `turn.jugajug.com` | High | Phase 8 | **RESOLVED** (Phase 11) | Refactored `CallingService::getIceServers()` to support dynamic RFC 5766 HMAC-SHA1 credentials with configurable TTL and TLS ports without domain blockage. |
| **DEF-FCM-01** | Missing BroadcastReceiver for Call Notification action buttons ("Accept", "Decline", "End Call") | Moderate | Phase 10 Audit | **RESOLVED** (Phase 10) | Implemented `CallActionBroadcastReceiver`, wired intents to `CallSessionManager`, and registered in Manifest. |
| **DEF-FCM-02** | `DeviceTokenManager` lacked backend token revocation on logout, leading to potential notification leaks after account switch | High | Phase 10 Audit | **RESOLVED** (Phase 10) | Added `revokeToken()` calling `DELETE /v1/devices/{id}` on backend, deleting token via `FirebaseMessaging.getInstance().deleteToken()`, and clearing preferences. |
| **DEF-FCM-03** | Missing full-screen intent and lockscreen wake permissions for Android 10+ and Android 14+ incoming call alerts | High | Phase 10 Audit | **RESOLVED** (Phase 10) | Added `USE_FULL_SCREEN_INTENT`, `VIBRATE`, and `WAKE_LOCK` permissions in `AndroidManifest.xml`. |
| **DEF-PHY-01** | Real hardware media loop (microphone/speaker) and camera rendering unverified on physical hardware | High | Phase 11 | **NOT VERIFIED** (Pending Physical Lab) | Requires two physical Android test devices with separate SIM cards and accounts. |
| **DEF-PHY-02** | Real Firebase Cloud push delivery unverified on physical hardware due to pending production credentials | High | Phase 11 | **NOT VERIFIED** (Pending Firebase Setup) | Requires live Google Cloud project `google-services.json` and `FCM_SERVER_KEY`. |

---

## Detailed Root Cause Analysis for DEF-RT-03

1. **Defect**: In `CallingService.php`, the condition `if ($turnUrl && ! str_contains($turnUrl, 'turn.jugajug.com'))` prevented any server hosted on `turn.jugajug.com` from generating ephemeral credentials, forcing fallback to OpenRelay.
2. **Impact**: Production Coturn deployments on `turn.jugajug.com` were completely bypassed.
3. **Resolution**:
   - Enhanced `CallingService.php` to parse `services.turn.url`, `services.turn.secret`, `services.turn.tls_url`, and `services.turn.ttl`.
   - Generates dynamic RFC 5766 credentials:
     $$\text{username} = \text{timestamp} : \text{userId}$$
     $$\text{credential} = \text{Base64}\big(\text{HMAC-SHA1}(\text{username}, \text{TURN\_SERVER\_SECRET})\big)$$
   - Generates full suite of transports: UDP (3478), TCP (3478), and TURNS over TLS (5349).
   - Validated via automated test suite `EnterpriseTurnCredentialTest.php` (3/3 passing).

---

## Summary of Open Defects
- **Code / Engineering Defects**: 0 Open
- **Automated Test Failures**: 0 Open
- **Physical Verification Defect (DEF-PHY-01)**: `NOT VERIFIED` (Hardware dependent)
- **Physical Cloud Verification Defect (DEF-PHY-02)**: `NOT VERIFIED` (Cloud credential dependent)

