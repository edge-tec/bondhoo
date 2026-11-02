# CONNECTLY — REAL DEVICE CALL TEST REPORT
## Phase 11: Real Physical Device Matrix & End-to-End Media Verification

| Test Scenario Category | Automated Harness Status | Physical Real-Device Status | Verdict |
| :--- | :--- | :--- | :--- |
| **A. Audio Calling Matrix** | **PASS** (11/11 automated tests) | Physical Audio Hardware Loop | **NOT VERIFIED** |
| **B. Video Calling Matrix** | **PASS** (Camera2 capturer linked) | Physical Camera & Screen Render | **NOT VERIFIED** |
| **C. Network Traversal Scenarios** | **PASS** (ICE restart & coordinator tested) | Cross-Carrier Cellular / Dual Wi-Fi | **NOT VERIFIED** |
| **D. FCM Awakening Scenarios** | **PASS** (16/16 unit tests passed) | Real Device Lockscreen / WakeLock | **NOT VERIFIED** |

---

## 1. Physical Device Requirements & Setup

To validate this phase on physical hardware, two independent test devices with distinct SIM cards and Google Play accounts are required:

| Device Role | Hardware Model | OS Version | Network Connection | Authenticated Account |
| :--- | :--- | :--- | :--- | :--- |
| **Device A (Caller)** | Physical Android Device (e.g. Pixel 8) | Android 14+ (API 34) | Cellular LTE / 5G | `user_alpha@jugajug.com` (ID: 101) |
| **Device B (Callee)** | Physical Android Device (e.g. Galaxy S23) | Android 13+ (API 33) | Home / Office Wi-Fi | `user_beta@jugajug.com` (ID: 102) |

---

## 2. Test Execution & Verification Checklist

### A. Audio Call Scenarios

| Test Case ID | Test Description | Expected Behavior | Verification Status |
| :--- | :--- | :--- | :--- |
| **AUD-01** | Device A dials Device B | Backend authorizes session, generates call ID, sends FCM VoIP push | **PASS** (Backend Verified) |
| **AUD-02** | Device B receives incoming alert | Heads-up notification displays caller name, ringtone plays, vibration starts | **NOT VERIFIED** (Physical) |
| **AUD-03** | Device B taps "Accept" | Notification dismissed, audio focus requested, PeerConnection connected | **NOT VERIFIED** (Physical) |
| **AUD-04** | Bidirectional Audio Flow | Both parties speak and hear clean audio without acoustic echo | **NOT VERIFIED** (Physical) |
| **AUD-05** | Microphone Mute / Unmute | Local audio track disabled; remote party hears silence; UI updates | **NOT VERIFIED** (Physical) |
| **AUD-06** | Speakerphone Toggle | Audio routes between earpiece and loudspeaker | **NOT VERIFIED** (Physical) |
| **AUD-07** | Call Termination | Either party taps "End Call"; WebSocket broadcasts end; audio stops | **PASS** (Backend Verified) |

### B. Video Call Scenarios

| Test Case ID | Test Description | Expected Behavior | Verification Status |
| :--- | :--- | :--- | :--- |
| **VID-01** | Video call initiation | Camera2 capturer starts, local preview displays 720p 30fps stream | **NOT VERIFIED** (Physical) |
| **VID-02** | Remote video rendering | SDP answer sets remote video track; `SurfaceViewRenderer` displays video | **NOT VERIFIED** (Physical) |
| **VID-03** | Camera switch (Front / Rear) | `CameraVideoCapturer.switchCamera()` smoothly switches lens | **NOT VERIFIED** (Physical) |
| **VID-04** | Camera mute (Video disable) | Video capturer stops, local video track disabled, black frame/avatar shown | **NOT VERIFIED** (Physical) |

### C. Network Handover & NAT Scenarios

| Test Case ID | Test Description | Expected Behavior | Verification Status |
| :--- | :--- | :--- | :--- |
| **NET-01** | Same Local Wi-Fi | ICE selects `host` candidate pair directly | **NOT VERIFIED** (Physical) |
| **NET-02** | Wi-Fi to Symmetric 4G/5G | Direct P2P fails; ICE gathers and selects `relay` TURN candidate pair | **NOT VERIFIED** (Physical) |
| **NET-03** | Wi-Fi to Cellular Handover | Network monitor detects IP change; triggers `IceRestart`; call continues | **NOT VERIFIED** (Physical) |
| **NET-04** | Temporary 5-second drop | Call enters `DISCONNECTED`; reconnects within 10s without ending | **NOT VERIFIED** (Physical) |

---

## 3. Strict Verification Verdict

In accordance with Phase 11 acceptance criteria:
- Android Client Code Implementation: **PASS**
- Unit & Feature Test Suites: **PASS**
- Physical Media Transmission on Hardware: **NOT VERIFIED** (Marked pending physical device lab execution)

