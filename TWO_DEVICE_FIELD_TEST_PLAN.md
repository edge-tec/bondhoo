# TWO-DEVICE REAL-DEVICE FIELD TEST PLAN & PROTOCOL

**Document Type:** Field Test Protocol & QA Execution Plan  
**Target Application:** Connectly Staging (`app-staging.apk` / `com.connectly.android.staging`)  
**Backend:** JUGAJUG Staging (`staging-api.jugajug.com`)  
**Date:** November 2026  
**Execution Status:** `FIELD TEST PROTOCOL ESTABLISHED — EXECUTION NOT RUN`  

---

## 1. Executive Summary

This document outlines the end-to-end field testing protocol between two physical Android devices. In adherence to strict testing guidelines, **all tests requiring physical mobile hardware in this session are classified as `NOT RUN`** due to the absence of connected mobile devices on the host (`adb devices -l` returned empty).

The test cases below define the exact verification steps, pass/fail criteria, and log collection instructions for the staging field session.

---

## 2. Test Hardware & Network Configuration Profile

| Parameter | Device A (Caller) | Device B (Callee / Receiver) |
| :--- | :--- | :--- |
| **Device Model** | Google Pixel 8 | Samsung Galaxy S23 |
| **OS Version** | Android 15 (VanillaIceCream / API 35) | Android 14 (One UI 6.1 / API 34) |
| **Primary Network** | 5GHz Wi-Fi (WPA3 Enterprise / Fiber) | Cellular 4G LTE / 5G (Carrier CGNAT) |
| **Installed APK** | `app-staging.apk` (SHA-256: `06430f6...`) | `app-staging.apk` (SHA-256: `06430f6...`) |
| **Staging User** | `tester_alpha@connectly.staging` | `tester_beta@connectly.staging` |

---

## 3. Comprehensive Field Test Scenarios

### 3.1 Audio Calling Test Matrix
| Test ID | Scenario | Preconditions | Test Steps | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **FLD-AUD-01** | Basic 1:1 Voice Call | Both users logged in; app in foreground | 1. Alpha taps "Voice Call" on Beta's profile.<br>2. Beta hears ringtone.<br>3. Beta taps "Accept". | Two-way audio connects in < 1.5s; clear voice with MOS > 4.0 | `NOT RUN` |
| **FLD-AUD-02** | Microphone Mute/Unmute | Active call in progress | 1. Alpha taps "Mute".<br>2. Beta speaks.<br>3. Alpha taps "Unmute". | Beta cannot hear Alpha while muted; audio restores immediately | `NOT RUN` |
| **FLD-AUD-03** | Speakerphone Routing | Active call in progress | 1. Alpha toggles "Speaker".<br>2. Alpha switches back to earpiece. | Audio routes to loudspeaker without feedback or echo | `NOT RUN` |
| **FLD-AUD-04** | Call Termination | Active call in progress | 1. Beta taps "End Call". | Call closes immediately on both devices; returns to chat | `NOT RUN` |
| **FLD-AUD-05** | Call Rejection | Incoming call ringing on Beta | 1. Alpha calls Beta.<br>2. Beta taps "Decline". | Ringing stops immediately; Alpha UI shows "Call Declined" | `NOT RUN` |

### 3.2 Video Calling Test Matrix
| Test ID | Scenario | Preconditions | Test Steps | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **FLD-VID-01** | 720p Video Call Setup | Camera permission granted | 1. Alpha taps "Video Call".<br>2. Beta accepts with video. | Both local preview and remote video render smoothly at 30fps | `NOT RUN` |
| **FLD-VID-02** | Camera Switch (Front/Rear)| Video call active | 1. Alpha taps "Switch Camera".<br>2. Alpha switches back. | Video stream flips smoothly without dropping call or freezing | `NOT RUN` |
| **FLD-VID-03** | Video Mute (Camera Pause)| Video call active | 1. Beta pauses video track. | Alpha sees placeholder avatar; audio continues uninterrupted | `NOT RUN` |
| **FLD-VID-04** | A/V Lip Synchronization | Video call active | 1. Alpha counts aloud 1 to 5. | Audio and video arrive synchronized with < 50ms skew | `NOT RUN` |

### 3.3 Background & Lockscreen Incoming Calls
| Test ID | Scenario | Preconditions | Test Steps | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **FLD-BG-01** | Background Incoming Call | Beta app minimized to home screen | 1. Alpha calls Beta.<br>2. Beta device receives push. | High-priority heads-up banner appears with Accept/Decline | `NOT RUN` |
| **FLD-BG-02** | Locked-Screen Incoming Call| Beta device screen locked/sleeping | 1. Alpha calls Beta.<br>2. Screen lights up. | Full-screen incoming call UI turns on display; custom ringtone plays | `NOT RUN` |
| **FLD-BG-03** | Swiped / Killed App State | Beta app swiped away from Recents | 1. Alpha calls Beta. | FCM wakes application process; notification displays | `NOT RUN` |

### 3.4 Network Transition & Handover
| Test ID | Scenario | Preconditions | Test Steps | Expected Result | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **FLD-NET-01** | Wi-Fi to Cellular Handover | Active call on Wi-Fi | 1. Alpha walks out of Wi-Fi range.<br>2. Device switches to 4G LTE. | NetworkConnectivityMonitor detects transition; ICE restarts; call recovers in < 2s | `NOT RUN` |
| **FLD-NET-02** | Cellular to Wi-Fi Handover | Active call on 4G LTE | 1. Beta enters known Wi-Fi zone.<br>2. Device connects to Wi-Fi. | Media routes to Wi-Fi seamlessly; video bitrate ramps up | `NOT RUN` |
| **FLD-NET-03** | Symmetric NAT (TURN Relay) | Device A on strict corporate NAT, Device B on 5G | 1. Initiate call.<br>2. Inspect WebRTC stats. | Direct STUN binding fails; candidate pair selects `typ relay` | `BLOCKED` (Coturn host required) |

---

## 4. Test Log & Evidence Capture Instructions

During field test execution, testers will capture logcat telemetry via:

```bash
# Capture WebRTC and Calling logs from Device A
adb -s <DEVICE_A_SERIAL> logcat -v time -s WebRtcEngine:V CallSessionManager:V ReverbWebSocketManager:V > device_a_call.log

# Capture Notification and Push logs from Device B
adb -s <DEVICE_B_SERIAL> logcat -v time -s ConnectlyFirebaseMessagingService:V CallNotificationManager:V > device_b_push.log
```

---

## 5. Summary & Sign-Off

- **Field Test Protocol Completeness:** `PASS`
- **Field Test Physical Execution:** `NOT RUN` (Awaiting physical device lab session and cloud Coturn host).

