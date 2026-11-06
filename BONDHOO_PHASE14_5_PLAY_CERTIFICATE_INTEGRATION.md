# BONDHOO PHASE 14.5 — STAGE 04: PLAY APP SIGNING CERTIFICATE MAPPING & INTEGRATION
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Firebase Integration Engineer & Application Security Auditor  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Executive Summary & Dual-Certificate Separation

This report defines the mandatory post-enrollment integration procedure to bridge Google Play's cloud-managed App Signing Key with backend authentication and notification providers.

Under Google Play App Signing:
- **Certificate A (Upload Key):** Signs the bundle before upload (`connectly-release.jks`).
- **Certificate B (App Signing Key):** Signs the APKs installed by end users from the Play Store.

> **CRITICAL RULE:** The Upload Key fingerprint **CANNOT** be substituted for the Play App Signing Key fingerprint. Doing so will cause Google Sign-In to crash with `ApiException: 10` on all Play Store installations.

---

## 2. Certificate Fingerprint Inventory

| Certificate Role | Origin | SHA-1 Fingerprint | SHA-256 Fingerprint |
|---|---|---|---|
| **A. Upload Key** | Local Keystore (`connectly-release.jks`) | `D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C` | `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41` |
| **B. Google App Signing Key** | Google Play Console (Post-Enrollment) | *[Pending Owner Copy from Play Console > App Integrity]* | *[Pending Owner Copy from Play Console > App Integrity]* |

---

## 3. Provider Configuration Workflow

Following successful enrollment in Play Console:

### 1. Firebase Android App Registration:
- Open [Firebase Console](https://console.firebase.google.com).
- Navigate to **Project Settings > General > Your apps**.
- Select `com.jugajug.android`.
- Under **SHA certificate fingerprints**, register **both**:
  1. Upload Key SHA-1 & SHA-256
  2. Google App Signing Key SHA-1 & SHA-256
- Re-download the official `google-services.json` and commit it into `android/app/google-services.json`.

### 2. Google Cloud Platform (GCP) OAuth 2.0 Credentials:
- Open [Google Cloud Credentials](https://console.cloud.google.com/apis/credentials).
- Verify the Android OAuth Client ID for package `com.jugajug.android`.
- Add the Google App Signing Key SHA-1 fingerprint to the authorized signing list.

### 3. Digital Asset Links (`assetlinks.json`):
- Location on backend: `public/.well-known/assetlinks.json`
- Update the `sha256_cert_fingerprints` array to include both SHA-256 fingerprints:
  ```json
  [
    {
      "relation": ["delegate_permission/common.handle_all_urls"],
      "target": {
        "namespace": "android_app",
        "package_name": "com.jugajug.android",
        "sha256_cert_fingerprints": [
          "3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41",
          "<INSERT_GOOGLE_PLAY_APP_SIGNING_SHA256_HERE>"
        ]
      }
    }
  ]
  ```

---

## 4. Post-Integration Verification Protocols

Once fingerprints are registered, test the following flows on a Play-distributed build:
- [ ] **Google One-Tap / OAuth Sign-In:** Authenticates user and exchanges ID token with Laravel backend.
- [ ] **Email / Password Authentication:** Verifies standard REST API login.
- [ ] **FCM Notifications:** Verifies background notification delivery when device is locked.
- [ ] **Deep Links (`bondhoo://`):** Verifies custom scheme redirection.
- [ ] **Android App Links (`https://bondhoo.com/...`):** Verifies direct app opening without browser disambiguation dialog.

**Stage 04 Determination:** **PASS (INTEGRATION BLUEPRINT & LOCAL FINGERPRINTS COMPLETE)**.

