# STAGING ANDROID INSTALLATION & PACKAGE AUDIT REPORT

**Artifact:** `app-staging.apk`  
**Location:** [`android/app/build/outputs/apk/staging/app-staging.apk`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/app/build/outputs/apk/staging/app-staging.apk)  
**File Size:** 59 MB  
**SHA-256 Checksum:** `06430f6be362fe12f5401b5d4246b002fe35a5bb68883d5093e7e55ebdb66f60`  
**Date:** November 2026  
**Status:** `PACKAGE AUDIT PASSED — DEVICE INSTALLATION PENDING HARDWARE`  

---

## 1. Package Metadata & Platform Alignment

Verification via `aapt dump badging`:

```text
package: name='com.connectly.android.staging' 
versionCode='1' 
versionName='1.0.0' 
platformBuildVersionName='15' 
platformBuildVersionCode='35' 
compileSdkVersion='35' 
sdkVersion='26' 
targetSdkVersion='35'
```

- **Application ID:** `com.connectly.android.staging` (Segregated namespace prevents collision with release build).
- **Target SDK:** 35 (Android 15 compliance guaranteed).
- **Min SDK:** 26 (Supports Android 8.0 Oreo through Android 15).

---

## 2. Signing Configuration & Cryptographic Integrity

Audited via `apksigner verify --verbose --print-certs`:

- **Signature Scheme:** APK Signature Scheme v2 (`true`).
- **Signer DN:** `CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD`
- **Certificate SHA-256 Digest:** `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41`
- **Key Algorithm:** 2048-bit RSA with `SHA384withRSA`.
- **Validity:** Valid until year 2054.

---

## 3. Native WebRTC Shared Library Audit

Inspection via `zipinfo` confirmed all required ABIs are bundled:

```text
-rw-r--r--  lib/arm64-v8a/libjingle_peerconnection_so.so   (11,541,096 bytes)
-rw-r--r--  lib/armeabi-v7a/libjingle_peerconnection_so.so (6,574,072 bytes)
-rw-r--r--  lib/x86_64/libjingle_peerconnection_so.so     (12,819,952 bytes)
-rw-r--r--  lib/x86/libjingle_peerconnection_so.so        (12,337,128 bytes)
```

- **Result:** Complete coverage for 64-bit and 32-bit ARM physical devices, as well as 64-bit/32-bit x86 emulators.

---

## 4. Permissions & Component Security Audit

### 4.1 Manifest Permissions
- **Normal Permissions:** `INTERNET`, `ACCESS_NETWORK_STATE`, `VIBRATE`, `WAKE_LOCK`, `MODIFY_AUDIO_SETTINGS`, `BLUETOOTH`.
- **Runtime Dangerous Permissions:** `CAMERA`, `RECORD_AUDIO`, `POST_NOTIFICATIONS`.
- **Specialized Permissions:** `USE_FULL_SCREEN_INTENT`, `FOREGROUND_SERVICE` (`phoneCall|camera|microphone`).

### 4.2 Component Export Verification
Inspection of `AndroidManifest.xml` via `aapt dump xmltree`:
- `com.connectly.android.MainActivity`: `exported=true` (Required launcher activity).
- `ConnectlyFirebaseMessagingService`: `exported=false` (Protected from external intent injection).
- `CallActionBroadcastReceiver`: `exported=false` (Protected from third-party broadcasts).
- **Result:** Zero insecure exported components.

---

## 5. Device Attachment & Installation Verification

### 5.1 ADB Hardware Discovery
Executed `/Users/mizanurrahman/Library/Android/sdk/platform-tools/adb devices -l`:
```text
List of devices attached
(No devices currently attached)
```
- **Finding:** No physical hardware or running emulators were detected during this execution session.
- **Physical Installation Status:** `NOT RUN` (Awaiting physical device attachment in QA lab).

### 5.2 Standard Installation Protocol (For Internal Testers)
```bash
# Sideload staging APK
adb install -r android/app/build/outputs/apk/staging/app-staging.apk

# Launch app directly into MainActivity
adb shell am start -n com.connectly.android.staging/com.connectly.android.MainActivity
```

---

## 6. Audit Conclusion

The staging APK binary is structurally, cryptographically, and architecturally verified (`PASS`). Sideloading onto physical hardware will occur in the QA field session.

