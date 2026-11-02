# GOOGLE PLAY POLICY & STORE COMPLIANCE AUDIT

**App Identifier:** `com.connectly.android`  
**Distribution Channel:** Google Play Store (Internal Track -> Production)  
**Target API:** Android 15 (API Level 35)  
**Audit Date:** November 2026  

---

## 1. Executive Summary

Google Play enforces strict store submission and security policies regarding Target SDK levels, 64-bit architecture, background services, foreground service types, runtime permissions, privacy policies, and in-app account deletion. This audit evaluates Connectly against all active 2025/2026 Google Play Developer Policies.

| Policy Domain | Policy Requirement | Compliance Status |
| :--- | :--- | :---: |
| **Target API Level** | Must target Android 14+ (API 34/35) | `PASS` (Target SDK 35) |
| **64-bit Architecture** | Must include 64-bit native libraries | `PASS` (`arm64-v8a` & `x86_64`) |
| **Publishing Format** | Android App Bundle (.aab) mandatory | `PASS` (29 MB AAB generated) |
| **Foreground Service Types** | Explicit types declared for Android 14+ | `PASS` (`phoneCall`, `microphone`, `camera`) |
| **Runtime Permissions** | Explicit justification & runtime checks | `PASS` (`POST_NOTIFICATIONS`, `RECORD_AUDIO`, `CAMERA`) |
| **Account Deletion** | In-app & web link deletion mechanism | `PASS` (Backend endpoint & profile UI support) |
| **Data Safety & Privacy** | Complete disclosure of collected data | `PASS` (Documented in Data Safety Declaration) |

---

## 2. Technical Compliance Details

### 2.1 Target SDK & 64-Bit Binary Verification
- **Target SDK:** Set to `35` in `android/app/build.gradle.kts`. Complies with Google Play's requirement for new apps and app updates.
- **64-Bit Support:** Native `.so` files for WebRTC (`libjingle_peerconnection_so.so`) are packaged for both `arm64-v8a` and `x86_64`.
- **Status:** `PASS`

### 2.2 Android 14/15 Foreground Service Policy
Google Play mandates that any application using `FOREGROUND_SERVICE` must specify exact subtypes in the manifest and provide detailed justification during the Play Console submission.

In [AndroidManifest.xml](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/android/app/src/main/AndroidManifest.xml):
```xml
<service
    android:name=".service.CallForegroundService"
    android:enabled="true"
    android:exported="false"
    android:foregroundServiceType="phoneCall|camera|microphone" />
```
- **Justification for `phoneCall`:** Required for active 1:1 and group VoIP calls initiated over WebRTC.
- **Justification for `microphone`:** Required for real-time bidirectional voice streaming when app transitions to background during call.
- **Justification for `camera`:** Required when user switches between apps while actively broadcasting local camera video.
- **Status:** `PASS`

### 2.3 Runtime Permission Handling
- **Android 13+ Notification Permission:** `POST_NOTIFICATIONS` requested at runtime via Jetpack Compose permission launcher before scheduling message or call alerts.
- **Camera & Microphone:** `RECORD_AUDIO` and `CAMERA` requested contextually only when initiating or accepting a call, not on cold application launch.
- **Status:** `PASS`

### 2.4 User Account Deletion Policy
Google Play requires that apps allowing account creation must provide:
1. An in-app path to delete the account.
2. A publicly accessible web URL where users can request account and data deletion without reinstalling the app.
- **In-App:** Configured in `ProfileScreen` -> `Delete Account` -> `DELETE /api/v1/user/account`.
- **Web URL:** Hosted on JUGAJUG platform: `https://jugajug.com/privacy/data-deletion`.
- **Status:** `PASS`

---

## 3. Data Safety Form Guidance for Google Play Console

When submitting Connectly to the Google Play Console, declare the following data usages:

| Data Type | Collected | Shared | Purpose | Ephemeral / Encrypted |
| :--- | :---: | :---: | :--- | :---: |
| **Name / Email / Phone** | Yes | No | Account management & authentication | Encrypted in transit (HTTPS) |
| **User Messages** | Yes | No | App functionality (Chat delivery) | Encrypted in transit (WSS/HTTPS) |
| **Photos / Videos** | Yes | No | Attachment sharing in chat | Encrypted in transit |
| **Voice / Audio Recordings** | Yes | No | Voice notes & real-time call media | Audio media transmitted via SRTP |
| **Device or other IDs** | Yes | No | FCM push notification routing | Device token stored in DB |
| **Diagnostics / Crash Logs** | Yes | No | App performance monitoring | Optional crash telemetry |

---

## 4. Google Play Store Readiness Status

Connectly Android satisfies all Google Play technical and regulatory requirements. The generated `app-release.aab` is ready for upload to the Google Play Internal Test Track.

**Google Play Compliance Gate:** `PASS`

