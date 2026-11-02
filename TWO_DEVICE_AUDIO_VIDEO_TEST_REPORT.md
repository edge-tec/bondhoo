# TWO-DEVICE AUDIO & VIDEO REAL-DEVICE TEST REPORT

**Target Platform:** Connectly Android Staging (`com.connectly.android.staging`)  
**Backend:** JUGAJUG Staging (`staging-api.jugajug.com`)  
**Scope:** Two-Phone WebRTC Audio & Video Calling Verification  
**Date:** November 2026  
**Status:** `CODE LOGIC PASS — PHYSICAL HARDWARE EXECUTION NOT RUN`  

---

## 1. Executive Summary

This report establishes the physical device testing register for WebRTC 1:1 calling between two Android devices. In adherence to enterprise safety and testing integrity guidelines, **no synthetic or simulated pass states are accepted**.

All underlying state machines, signaling endpoints, camera permission flows, and native WebRTC factories have passed code-level automated unit and integration tests. Physical hardware execution on actual mobile devices is categorized as `NOT RUN` pending the physical staging lab session.

---

## 2. Test Hardware Fleet Profile (Designated Staging Hardware)

| Role | Device Identifier | Manufacturer / Model | OS Version | Network Connection |
| :--- | :--- | :--- | :--- | :--- |
| **Device A (Caller)** | DEV-STG-01 | Google Pixel 8 | Android 15 (VanillaIceCream, API 35) | 5GHz Enterprise Wi-Fi |
| **Device B (Callee)** | DEV-STG-02 | Samsung Galaxy S23 | Android 14 (One UI 6.1, API 34) | Cellular LTE / 5G |

---

## 3. Audio Calling Test Suite

| Test ID | Test Scenario | Expected Outcome | Execution Evidence | Status |
| :--- | :--- | :--- | :--- | :---: |
| **AUD-01** | Call Initiation | Caller initiates call; state transitions to `OUTGOING_RINGING`; signaling offer sent | Unit tests pass (`CallSessionManagerTest`) | `PASS` (Code) / `NOT RUN` (HW) |
| **AUD-02** | Call Arrival & Ringing | Callee receives incoming call notification with ringtone sound and accept/decline | Unit tests pass (`CallNotificationManagerTest`) | `PASS` (Code) / `NOT RUN` (HW) |
| **AUD-03** | Call Acceptance | Callee taps Accept; SDP answer exchanged; PeerConnection enters `CONNECTED` | Feature test verifies state to `active` | `PASS` (Code) / `NOT RUN` (HW) |
| **AUD-04** | Two-Way Audio Playout | Bidirectional audio audible with low latency (< 150ms) and no echo | Requires physical acoustic measurement | `NOT RUN` |
| **AUD-05** | Microphone Mute/Unmute | Muting disables local audio track; peer receives silence; unmute restores audio | `WebRtcEngine.setMicrophoneMuted()` verified | `PASS` (Code) / `NOT RUN` (HW) |
| **AUD-06** | Speakerphone Toggle | Audio dynamically routes between earpiece and loudspeaker | `AudioManager` routing verified | `PASS` (Code) / `NOT RUN` (HW) |
| **AUD-07** | Call Termination | Either peer taps Hangup; `CallEndedEvent` dispatches; resources released | Feature test verifies database state | `PASS` |

---

## 4. Video Calling Test Suite

| Test ID | Test Scenario | Expected Outcome | Execution Evidence | Status |
| :--- | :--- | :--- | :--- | :---: |
| **VID-01** | Camera Permission | Runtime prompt requests `CAMERA`; denial displays graceful explanation | Jetpack Compose permission launcher verified | `PASS` |
| **VID-02** | Local Video Preview | Front camera stream renders smoothly to local `SurfaceViewRenderer` | EGL context initialization verified | `PASS` (Code) / `NOT RUN` (HW) |
| **VID-03** | Remote Video Playout | Callee receives remote H.264/VP8 video stream at 720p 30fps | Video track subscription implemented | `NOT RUN` |
| **VID-04** | Camera Flip (Front/Rear) | Switching cameras transitions video capturer without dropping call | `Camera2Enumerator` switch logic verified | `PASS` (Code) / `NOT RUN` (HW) |
| **VID-05** | Video Track Mute | User pauses camera; peer receives blank frame or placeholder avatar | `videoTrack.setEnabled(false)` verified | `PASS` (Code) / `NOT RUN` (HW) |
| **VID-06** | Hardware Resource Release | Ending call closes `CameraCapturer` and releases camera sensor | `WebRtcEngine.cleanup()` lifecycle verified | `PASS` |

---

## 5. Negative & Edge Case Test Suite

| Test ID | Scenario | Expected Result | Result | Status |
| :--- | :--- | :--- | :--- | :---: |
| **NEG-01** | Callee Declines Call | Caller receives decline signal; UI transitions to `CALL_ENDED`; tone stops | Backend transitions state to `declined` | `PASS` |
| **NEG-02** | Caller Cancels Early | Callee notification dismisses immediately; no ghost ringing | Signaling cancel event broadcasted | `PASS` |
| **NEG-03** | Call Timeout (No Answer) | 45-second timer expires; call recorded as "Missed Call" in database | Scheduler marks call missed | `PASS` |
| **NEG-04** | App Process Termination | OS kills app mid-call; peer receives connection closed signal within 5s | ICE connection state timeout trigger | `PASS` (Code) / `NOT RUN` (HW) |
| **NEG-05** | Simultaneous Incoming Calls | Second incoming call displays "Call Waiting" or returns busy signal | Backend validates user `in_call` state | `PASS` |
| **NEG-06** | Permission Denied at Ring | Callee accepts but denies mic permission; call prompts or cancels | UI permission boundary trap | `PASS` |

---

## 6. Real-Device Hardware Verification Gate

- **Automated Domain & Signaling Logic:** `PASS`
- **Physical Two-Phone Acoustic & Optical Validation:** `NOT RUN` (Requires staging lab physical session).

