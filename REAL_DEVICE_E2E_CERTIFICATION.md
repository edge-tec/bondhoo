# REAL-DEVICE END-TO-END CERTIFICATION REPORT

**Target Platform:** Connectly Native Android (Kotlin + Jetpack Compose)  
**Backend Host:** JUGAJUG Laravel 13  
**Audit Date:** November 2026  
**Scope:** Physical Hardware Integration, Audio/Video Rendering, NAT Traversal & Network Transitions  

---

## 1. Executive Summary

This report establishes the physical hardware verification matrix for Connectly Android. In compliance with strict engineering certification standards, **synthetic or simulated pass states are prohibited**. Tests that require two physical devices, live cellular radio towers, or microphone/speaker hardware that have not been executed on real physical hardware are tagged `NOT RUN` or `NOT VERIFIED`.

| Verification Category | Status | Primary Constraint / Evidence |
| :--- | :---: | :--- |
| **Android Unit & Domain Logic** | `PASS` | 13 Gradle modules executed & passed in 19s |
| **Backend API & Signaling Tests** | `PASS` | 47 Feature tests executed & passed in 1.90s |
| **Two-Phone Physical Audio Loop** | `NOT RUN` | Requires two physical devices in acoustic proximity |
| **Native Video Track Rendering** | `NOT VERIFIED` | SurfaceViewRenderer pipeline implemented; physical sensor unverified |
| **Cellular (LTE/5G CGNAT) Media Relay** | `NOT RUN` | Requires live cellular SIM carrier connection & active Coturn server |
| **Wi-Fi to Cellular Network Handover** | `NOT RUN` | Requires dynamic radio transition during active WebRTC call |
| **Background Push Wakeup on Dormant OS** | `NOT VERIFIED` | Requires real Google FCM credentials & physical device in Doze mode |

---

## 2. Test Matrix & Protocol Specification

The following verification matrix defines the acceptance criteria for staging certification on physical test hardware:

### 2.1 Audio Calling Pipeline (Test ID: HW-AUD-01)
- **Protocol:**
  1. Device A (Caller) initiates 1:1 voice call to Device B (Callee) via WebRTC.
  2. Device B rings via `CallNotificationManager` and user taps "Accept".
  3. WebRTC PeerConnection transitions to `CONNECTED`.
  4. Audio track captured via Android `AudioRecord` / WebRTC `JavaAudioDeviceModule`.
  5. Audio playout routed to Earpiece / Speakerphone.
- **Observed Result:** Unit tests confirm WebRtcEngine peer initialization, audio constraint application, and signaling exchange.
- **Hardware Status:** `NOT RUN` (Pending two physical devices).

### 2.2 Video Calling Pipeline (Test ID: HW-VID-01)
- **Protocol:**
  1. Caller initiates video call.
  2. Front-facing camera acquired via `Camera2Enumerator` and `VideoCapturer`.
  3. Local video track rendered to `SurfaceViewRenderer` in Jetpack Compose UI.
  4. Remote SDP exchange completes; remote video stream rendered to full-screen `SurfaceViewRenderer`.
- **Observed Result:** Classes compile cleanly; R8 preserves `org.webrtc.SurfaceViewRenderer`.
- **Hardware Status:** `NOT VERIFIED` (Physical camera sensor and GLSurface rendering unverified).

### 2.3 Hostile NAT & Cellular CGNAT Traversal (Test ID: HW-NET-01)
- **Protocol:**
  1. Device A connected to strict symmetric enterprise Wi-Fi.
  2. Device B connected to public cellular carrier (4G/5G CGNAT).
  3. Direct STUN binding fails.
  4. ICE engine falls back to allocated TURN relay candidate (`typ relay`).
  5. Bi-directional SRTP media packets flow continuously through Coturn relay port range.
- **Observed Result:** REST API generates valid HMAC credentials. Live relay requires Coturn provisioning.
- **Hardware Status:** `NOT RUN` (Blocked by live Coturn host availability).

### 2.4 Radio Interface Handover (Test ID: HW-NET-02)
- **Protocol:**
  1. Active call established on Wi-Fi.
  2. Device A disconnects Wi-Fi, falling back to Cellular data.
  3. `NetworkConnectivityMonitor.kt` detects `NetworkCapabilities.TRANSPORT_CELLULAR`.
  4. WebRTC triggers ICE re-start (`createOffer` with `iceRestart = true`).
  5. Audio stream recovers within `< 1500ms` without call termination.
- **Hardware Status:** `NOT RUN`.

---

## 3. Physical Test Environment Recommendations

For the Controlled Staging Phase, the QA team must configure:

1. **Device Fleet:**
   - Device A: Google Pixel 7 / 8 (Android 14/15, API 34/35).
   - Device B: Samsung Galaxy S22 / S23 (One UI, Android 13/14).
   - Device C: Xiaomi / Oppo device (testing aggressive OEM background task killing / battery optimizations).
2. **Network Environments:**
   - Dedicated 5GHz Wi-Fi AP.
   - Dual-SIM LTE/5G carrier subscriptions (e.g., Grameenphone, Robi, Airtel or T-Mobile).

---

## 4. Certification Gate Status

- **Code Level Verification:** `PASS`
- **Real-Device Hardware Verification:** `NOT RUN` / `NOT VERIFIED`

*Recommendation: Physical hardware validation must take place as Step 1 of the Controlled Staging deployment.*

