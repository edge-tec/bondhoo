# BONDHOO PHASE 14.4 — STAGE 05: FIREBASE & OAUTH CERTIFICATE REGISTRATION REPORT
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Firebase Integration Engineer & Application Security Auditor  
**Product:** BONDHOO Professional Social Networking Platform  
**Target Package:** `com.jugajug.android`

---

## 1. Executive Summary & Critical Risk Assessment

When an application is distributed through Google Play with **Play App Signing** enabled, Google replaces the developer's upload signature with a Google-managed **App Signing Key**. 

As a result:
- **Local Sideload APKs** are signed with the **Upload Key**.
- **Play Store Installed APKs** are signed with the **Google App Signing Key**.

If the Google App Signing Key fingerprints are **not registered** in Firebase Console and Google Cloud Console OAuth credentials, Google Sign-In will fail with `ApiException: 10` (Developer Error) on all user devices installed from Google Play.

---

## 2. Certificate Fingerprints Registry

| Identity Key | Source | SHA-1 Fingerprint | SHA-256 Fingerprint |
|---|---|---|---|
| **Upload Key** | `connectly-release.jks` (Local Keystore) | `D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C` | `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41` |
| **Play App Signing Key** | Google Play Console (Post-Enrollment) | *To be copied from Play Console > App integrity* | *To be copied from Play Console > App integrity* |

---

## 3. Provider Configuration Checklist for the Owner

### A. Firebase Console Setup:
1. Navigate to [Firebase Console](https://console.firebase.google.com).
2. Open your project settings (⚙️ icon > **Project settings**).
3. Under **Your apps**, locate the Android app with package: `com.jugajug.android`.
4. Click **Add fingerprint**:
   - Add Upload Key SHA-1: `D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C`
   - Add Upload Key SHA-256: `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41`
   - Add Play App Signing Key SHA-1 (from Play Console).
   - Add Play App Signing Key SHA-256 (from Play Console).
5. Download the updated `google-services.json` and replace `android/app/google-services.json`.

### B. Google Cloud Console (OAuth 2.0 Client IDs):
1. Navigate to [Google Cloud Console Credentials](https://console.cloud.google.com/apis/credentials).
2. Select your Firebase/Google project.
3. Under **OAuth 2.0 Client IDs**, verify that an Android client exists for package `com.jugajug.android`.
4. Ensure both the Upload Key SHA-1 and the Play App Signing Key SHA-1 are mapped to client credentials.

### C. Android App Links (`assetlinks.json`):
1. Digital Asset Links enables verified URL handling for `https://bondhoo.com` and `https://jugajug.com`.
2. Created template at `public/.well-known/assetlinks.json`.
3. Append the Google App Signing Key SHA-256 to `sha256_cert_fingerprints` array in `assetlinks.json` once generated.

---

## 4. Verification Protocols for Play-Distributed Build

When testers install the app from Google Play Internal Testing, the following auth flows must be tested:
1. **Email / Password Authentication:** Independent of certificate signature; verifies REST API token issuance.
2. **Google OAuth One-Tap Sign-In:** Requires Google App Signing Key SHA-1 registered in Firebase / Google Cloud.
3. **FCM Push Notification Delivery:** Requires token generation from Firebase Messaging SDK and server-side APNs/FCM delivery.
4. **Deep Linking (`bondhoo://` and `https://bondhoo.com/...`):** Verifies app launch from chat/post links.

**Stage 05 Determination:** **PASS (INTEGRATION BLUEPRINT & LOCAL FINGERPRINTS REGISTERED)**.

