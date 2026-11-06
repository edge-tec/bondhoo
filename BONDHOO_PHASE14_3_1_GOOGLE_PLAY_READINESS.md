# BONDHOO PHASE 14.3.1 — GOOGLE PLAY STORE READINESS REPORT
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Google Play Release Engineer & Compliance Lead  
**Product:** BONDHOO Professional Social Networking Platform  
**Target Submission Track:** Google Play Console — Internal Testing Track

---

## 1. Executive Summary

This report establishes the complete compliance and deployment readiness checklist for uploading the **BONDHOO** Android application to the **Google Play Console**. All policy, permission, architecture, and bundle specifications have been vetted against Google Play Policy (2025/2026 revisions), including Android 16 (API 36) support.

---

## 2. Play Console Application Specifications

| Parameter | Specification | Compliance Status |
|---|---|---|
| **App Title** | `BONDHOO` (বন্ধু) | Verified (Matches branding) |
| **Package Name** | `com.jugajug.android` | Verified (Canonical Bondhoo ID) |
| **Release Artifact Format** | Android App Bundle (`.aab`) | Verified (`app-release.aab` generated) |
| **Version Code** | `2` | Verified (Incremented from 1) |
| **Version Name** | `1.0.1` | Verified |
| **Compile SDK** | `36` (Android 16) | Verified |
| **Target SDK** | `36` (Android 16) | Exceeds min policy requirement (Target SDK >= 35) |
| **Minimum SDK** | `26` (Android 8.0 Oreo) | Supports 98.7% of active Android devices |
| **Primary Category** | Social / Professional Networking | Set |
| **Target Audience** | General Audience (18+ / Adults) | Confirmed: Not child-directed |

---

## 3. Permissions & Google Play Policy Compliance

### Sensitive Permissions Audit
Google Play strictly regulates sensitive permissions (SMS, Call Logs, Accessibility, Device Admin, Background Location). A line-by-line audit of `android/app/src/main/AndroidManifest.xml` confirms:

| Permission Category | Status | Notes / Declaration |
|---|---|---|
| **Accessibility API** (`BIND_ACCESSIBILITY_SERVICE`) | **NOT PRESENT** | Zero risk of policy rejection |
| **Device Administration** (`BIND_DEVICE_ADMIN`) | **NOT PRESENT** | Clean social platform manifest |
| **Usage Stats** (`PACKAGE_USAGE_STATS`) | **NOT PRESENT** | No parental control monitoring |
| **Background Location** (`ACCESS_BACKGROUND_LOCATION`) | **NOT PRESENT** | No background tracking |
| **Foreground Location** (`ACCESS_COARSE_LOCATION`, `ACCESS_FINE_LOCATION`) | **OPTIONAL / FOREGROUND ONLY** | Used strictly for user-initiated check-ins |
| **Camera & Media** (`CAMERA`, `READ_MEDIA_IMAGES`, `READ_MEDIA_VIDEO`) | **STANDARD RUNTIME** | Prompted on user photo/video upload |
| **Notifications** (`POST_NOTIFICATIONS`) | **STANDARD RUNTIME** | Compliant with Android 13+ requirements |
| **Internet & Network** (`INTERNET`, `ACCESS_NETWORK_STATE`) | **NORMAL** | Standard REST API & WebSocket connectivity |

### Families Policy Applicability
- **Finding:** BONDHOO is a professional social networking and LMS platform intended for university students, professionals, and adults.
- **Declaration:** Under the Google Play Console Target Audience declaration, "18 and older" must be selected.
- **Impact:** The application is **exempt** from the Google Play Designed for Families (DFF) requirements, eliminating complex COPPA disclosures.

---

## 4. Play App Signing & SHA Fingerprints

When creating the release in Google Play Console, Play App Signing should be configured using the existing release key or Play App Signing key generation.

- **Upload Key Fingerprints (`connectly-release.jks`):**
  - **SHA-1:** `D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C`
  - **SHA-256:** `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41`
- **Firebase / Google Cloud Console Sync:**
  - Both fingerprints are registered under `com.jugajug.android` in `google-services.json`.

---

## 5. Data Safety Section Declarations

Google Play requires accurate Data Safety disclosures:

1. **Data Collected & Shared:**
   - **Personal Info:** Name, email address, user ID (collected for account functionality).
   - **Messages:** In-app messages, posts, comments (collected for app functionality).
   - **Photos & Videos:** Uploaded user media (collected for user sharing).
   - **App Info & Performance:** Crash logs and performance diagnostics.
2. **Security Practices:**
   - **Data Encrypted in Transit:** Yes (HTTPS / TLS 1.3).
   - **Account Deletion Supported:** Yes. The application provides both an in-app deletion button and a public URL (`https://bondhoo.com/account/delete`).
   - **Data Deletion Policy:** User data, profile, and messages are purged upon request.

---

## 6. Pre-Upload Step-by-Step Checklist for Release Manager

- [x] Android App Bundle (`app-release.aab`) compiled and signed.
- [x] Package Name verified: `com.jugajug.android`.
- [x] Target SDK verified: 36 (Android 16).
- [x] Zero restricted parental control permissions in manifest.
- [x] Privacy Policy URL accessible: `https://bondhoo.com/privacy-policy`.
- [x] App Icon & Feature Graphic prepared using official Bondhoo infinity visual assets.
- [x] Ready to upload directly to **Internal Testing Track**.

**Verdict:** **READY FOR PLAY CONSOLE INTERNAL TESTING RELEASE**.

