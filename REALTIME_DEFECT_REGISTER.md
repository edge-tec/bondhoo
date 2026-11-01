# Connectly & JUGAJUG Real-Time Defect Register
**Phase 8: Enterprise Real-Time Quality Assurance & Defect Tracking**  
**Execution Timestamp:** 2026-11-01T21:31:48Z  
**Classification Standard:** Enterprise Severity (P0 Blocker, P1 Critical, P2 Major, P3 Minor)

---

## 1. Summary of Identified Defects

| Defect ID | Severity | Component | Summary | Current Status |
| :--- | :---: | :--- | :--- | :---: |
| **DEF-RT-01** | **P1 (Critical)** | `android:core:webrtc` | Native WebRTC C++ Media Engine missing (Mock SDP & ICE stub) | **OPEN (Requires SDK)** |
| **DEF-RT-02** | **P1 (Critical)** | `android:feature:notifications` | Firebase Cloud Messaging SDK and `google-services.json` missing | **OPEN (Requires SDK & JSON)** |
| **DEF-RT-03** | **P2 (Major)** | `backend:calling` | Production TURN relay server credentials unconfigured in `.env` | **OPEN (Requires Server)** |
| **DEF-RT-04** | **P2 (Major)** | `environment` | Host environment lacks `adb` and connected physical test devices | **OPEN (Requires Hardware)** |
| **DEF-RT-05** | **P3 (Minor)** | `android:feature:reels` | Deprecated Compose Icon (`Icons.Filled.Send`) and Media3 warnings | **OPEN (Non-blocking)** |
| **DEF-RT-06** | **P1 (Critical)** | `android:feature:messenger` | Redundant WebSocket connection in `MessengerWebSocketClient.kt` | **RESOLVED & VERIFIED** |
| **DEF-RT-07** | **P1 (Critical)** | `android:core:websocket` | Architecture layer inversion (`CallSessionManager` misplaced) | **RESOLVED & VERIFIED** |
| **DEF-RT-08** | **P3 (Minor)** | `android:core:websocket` | Kotlin type-mismatch warnings in `optString()` parsing | **RESOLVED & VERIFIED** |

---

## 2. Detailed Defect Analysis & Action Items

### DEF-RT-01: Native WebRTC C++ Media Engine Missing
- **Severity:** P1 (Critical - Blocks P2P Media Streaming)
- **Module:** `android/core/webrtc/src/main/java/com/connectly/android/core/webrtc/engine/WebRtcEngine.kt`
- **Root Cause:**
  `WebRtcEngine.kt` was implemented with synthetic string stubs for SDP generation and ICE candidates rather than binding to native libwebrtc. `android/gradle/libs.versions.toml` lacks `io.getstream:stream-webrtc-android` or `org.webrtc:google-webrtc`.
- **Impact:**
  Signaling and session management succeed on both backend and Android, but no actual audio or video frames can be captured, compressed, transmitted, or rendered between peers.
- **Remediation Steps:**
  1. Add `stream-webrtc-android = "1.3.10"` to `libs.versions.toml`.
  2. Implement real `PeerConnectionFactory`, `EglBase.Context`, `AudioSource`, `VideoSource`, `Camera2Enumerator`, and `SurfaceViewRenderer` inside `WebRtcEngine.kt`.
  3. Wire SDP and ICE callbacks to `WebRtcSignalingCoordinator.kt`.

---

### DEF-RT-02: Firebase Cloud Messaging SDK & Credentials Missing
- **Severity:** P1 (Critical - Blocks Remote Background Notifications)
- **Module:** `android/feature/notifications` & `JUGAJUG Laravel Backend`
- **Root Cause:**
  - `ConnectlyFirebaseMessagingService.kt` extends `android.app.Service` instead of `FirebaseMessagingService`.
  - `libs.versions.toml` does not declare `firebase-messaging` or `google-services` Gradle plugin.
  - `android/app/google-services.json` is missing.
  - Backend `storage/app/firebase-auth.json` is absent; `.env` has empty `FCM_SERVER_KEY`.
- **Impact:**
  Android devices cannot register device tokens, receive wake-up push notifications for incoming calls while terminated/backgrounded, or receive offline message alerts.
- **Remediation Steps:**
  1. Configure Firebase Console project and download `google-services.json` into `android/app/`.
  2. Add Firebase BOM (`platform("com.google.firebase:firebase-bom:33.7.0")`) and `com.google.firebase:firebase-messaging-ktx`.
  3. Export Google Service Account credentials JSON to `storage/app/firebase-auth.json` and set `FIREBASE_CREDENTIALS` in Laravel `.env`.

---

### DEF-RT-03: Production TURN Relay Server Credentials Unconfigured
- **Severity:** P2 (Major - Blocks Calling on Cellular / Symmetric NAT)
- **Module:** `app/Services/CallingService.php` & `.env`
- **Root Cause:**
  `CallingService::getIceServers()` returns fallback Google STUN servers. TURN configuration keys (`TURN_SERVER_URL`, `TURN_SERVER_USERNAME`, `TURN_SERVER_CREDENTIAL`) are present in `config/services.php` but undefined in `.env`.
- **Impact:**
  P2P WebRTC calls will fail on 4G/5G mobile carriers and strict corporate Wi-Fi firewalls that block direct UDP/TCP hole punching.
- **Remediation Steps:**
  1. Deploy or subscribe to a production Coturn/Xirsys TURN service.
  2. Add `TURN_SERVER_URL=turns:turn.jugajug.com:5349?transport=tcp`, `TURN_SERVER_USERNAME`, and `TURN_SERVER_CREDENTIAL` to `.env`.

---

### DEF-RT-04: Host Environment Lacks ADB and Physical Test Devices
- **Severity:** P2 (Major - Blocks Live Hardware Validation)
- **Module:** Host Toolchain (`macOS`)
- **Root Cause:**
  Host shell environment does not have `adb` on PATH (`/Users/mizanurrahman/Library/Android/sdk/platform-tools`), and zero physical devices or running emulators are connected.
- **Impact:**
  Cannot run instrumented tests, verify physical camera/mic drivers, or test real RF handover.
- **Remediation Steps:**
  1. Export `export PATH="$PATH:$HOME/Library/Android/sdk/platform-tools"` in `.zshrc`.
  2. Connect physical test devices via USB debugging or start an emulator with `emulator -avd Pixel_8_API_34`.

---

### DEF-RT-05: Compose Icon Deprecations & Media3 Annotations
- **Severity:** P3 (Minor - Code Quality)
- **Module:** `android/feature/reels/src/main/java/.../ReelsScreen.kt`
- **Root Cause:**
  `Icons.Filled.Send` is deprecated in Jetpack Compose in favor of `Icons.AutoMirrored.Filled.Send`. Media3 `@OptIn(UnstableApi::class)` warnings.
- **Remediation Steps:**
  Update icon reference and apply correct opt-in annotations.

---

## 3. Resolved Defects (Verified in Phase 8)

### DEF-RT-06: Redundant WebSocket Connection in `feature:messenger`
- **Resolution:** Removed separate socket manager; unified all real-time events through `ReverbWebSocketManager.kt` via Hilt dependency injection.
- **Verification:** Verified by clean compilation and unified subscription lifecycle.

### DEF-RT-07: Architecture Layer Inversion in `core:websocket`
- **Resolution:** Relocated `CallSessionManager.kt` to `core:webrtc` and `PushNotificationManager.kt` to `feature:notifications`.
- **Verification:** Verified by KSP processing and clean multi-module build.

### DEF-RT-08: Kotlin Type Mismatch Warnings in `ReverbWebSocketManager.kt`
- **Resolution:** Replaced `optString("key", null)` with type-safe null-checking logic.
- **Verification:** Verified by Kotlin compiler (0 warnings in `core:websocket`).

