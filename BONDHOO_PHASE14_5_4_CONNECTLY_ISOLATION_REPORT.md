# BONDHOO PHASE 14.5.4 — CONNECTLY ISOLATION & PERMISSION AUDIT REPORT

**Date:** November 5, 2026  
**Auditor:** Application Security Engineer & Android Release Engineer  
**Scope:** Decoupling Verification between BONDHOO Social and CONNECTLY Parental Control  
**Status:** **100% ISOLATED (ZERO CONTAMINATION)**

---

## 1. Package & Identity Separation

- **BONDHOO Android Application ID:** `com.jugajug.android`
- **CONNECTLY Parental Control Package:** `com.connectly.family`
- **Branding:** BONDHOO (বন্ধু) - Social Networking Platform.

---

## 2. Android Manifest & Permission Audit

Inspected `android/app/src/main/AndroidManifest.xml` and release APK merged manifest via `aapt2 dump badging`:

### Prohibited Parental / Device Admin Permissions (CONFIRMED ABSENT)
- `android.permission.BIND_DEVICE_ADMIN` — **ABSENT**
- `android.permission.BIND_ACCESSIBILITY_SERVICE` — **ABSENT**
- `android.permission.PACKAGE_USAGE_STATS` — **ABSENT**
- `android.permission.ACCESS_BACKGROUND_LOCATION` — **ABSENT**
- `android.permission.READ_CALL_LOG` — **ABSENT**
- `android.permission.READ_SMS` / `RECEIVE_SMS` — **ABSENT**
- `android.permission.SYSTEM_ALERT_WINDOW` — **ABSENT**

### Approved Social Networking Permissions (CONFIRMED PRESENT)
- `INTERNET`, `ACCESS_NETWORK_STATE` (Network operations)
- `POST_NOTIFICATIONS`, `VIBRATE`, `WAKE_LOCK` (Push notifications)
- `CAMERA`, `RECORD_AUDIO`, `MODIFY_AUDIO_SETTINGS` (WebRTC Calling & Stories)
- `BLUETOOTH`, `BLUETOOTH_CONNECT` (Audio routing during calls)
- `READ_MEDIA_IMAGES`, `READ_MEDIA_VIDEO` (Photo/Video publishing)

---

## 3. Shared Dependency Justification
- Shared network utilities (`core:network`, `core:database`, `core:designsystem`) handle shared HTTP client architecture and design components.
- Zero parental control business logic or background tracking services exist in the BONDHOO release artifact.

