# ANDROID STAGING RELEASE & INTERNAL DISTRIBUTION REPORT

**Application ID:** `com.connectly.android.staging`  
**Build Variant:** `staging`  
**Version:** `1.0.0` (Version Code `1`)  
**Target SDK:** `35` (Android 15)  
**Min SDK:** `26` (Android 8.0)  
**Date:** November 2026  
**Build Status:** `BUILD SUCCESSFUL`  

---

## 1. Executive Summary

The Connectly Native Android staging build has been generated, signed, and validated for internal testing distribution. The staging build uses a separate application identifier (`.staging`), preventing any collision with production builds and enabling testers to have both installed simultaneously.

---

## 2. Staging Artifact Details

| Artifact Type | File Location | File Size | SHA-256 Checksum |
| :--- | :--- | :---: | :--- |
| **Staging APK** | `android/app/build/outputs/apk/staging/app-staging.apk` | **59 MB** | `06430f6be362fe12f5401b5d4246b002fe35a5bb68883d5093e7e55ebdb66f60` |
| **Staging AAB** | `android/app/build/outputs/bundle/staging/app-staging.aab` | **37 MB** | `167173ad998090bd147f361ab18b5a649375e8512179645ab0da80fd312ad172` |

---

## 3. Configuration Audit

### 3.1 Network & Logging Isolation
- **Base API URL:** Configured to point to staging backend host.
- **HTTP Logging:** Handled via `HttpLoggingInterceptor`. Set to `Level.NONE` in release/staging modes to prevent sensitive token/payload leaks to `logcat`.
- **WebSocket Reverb Host:** Configured to point to staging Reverb gateway.

### 3.2 WebRTC Native Library Packaging
Inspection of native `.so` shared libraries embedded inside `app-staging.apk`:
- `lib/arm64-v8a/libjingle_peerconnection_so.so` (Present)
- `lib/armeabi-v7a/libjingle_peerconnection_so.so` (Present)
- `lib/x86_64/libjingle_peerconnection_so.so` (Present)
- `lib/x86/libjingle_peerconnection_so.so` (Present)
- **Status:** `PASS` (Full 64-bit and 32-bit architecture coverage).

### 3.3 Deep Links & Notification Channels
- **Deep Link Schemes:** `jugajug://` and `connectly://` declared in `MainActivity` with staging compatibility.
- **Call Notification Channel:** `connectly_calls_high_priority` (Importance HIGH, custom ringtone).
- **Message Notification Channel:** `connectly_messages` (Importance HIGH, private lockscreen visibility).

---

## 4. Internal Tester Installation & Distribution Guide

Internal testers and QA engineers can install the staging release directly on physical devices or emulators using `adb`:

### Method 1: Direct ADB Installation (Over USB or Wi-Fi)
```bash
# 1. Connect physical Android device and verify authorization
adb devices -l

# 2. Install the staging APK with replacement flag (-r)
adb install -r android/app/build/outputs/apk/staging/app-staging.apk

# 3. Grant runtime permissions contextually or via ADB for automated test runs:
adb shell pm grant com.connectly.android.staging android.permission.CAMERA
adb shell pm grant com.connectly.android.staging android.permission.RECORD_AUDIO
adb shell pm grant com.connectly.android.staging android.permission.POST_NOTIFICATIONS

# 4. Launch the application
adb shell am start -n com.connectly.android.staging/com.connectly.android.MainActivity
```

### Method 2: Google Play Internal Testing Track
- Upload `android/app/build/outputs/bundle/staging/app-staging.aab` to Google Play Console under **Testing > Internal testing**.
- Add QA team Google account emails to the tester email list.
- Testers accept the invitation link to install automatic updates directly from Google Play.

---

## 5. Certification Sign-Off

The staging APK and App Bundle satisfy all internal distribution requirements.

**Android Staging Gate:** `PASS` (Ready for Internal Device Sideloading & Testing)

