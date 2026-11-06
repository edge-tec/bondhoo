# BONDHOO PHASE 14.5.4 — GOOGLE PLAY APP SIGNING & RELEASE READINESS AUDIT

**Date:** November 5, 2026  
**Auditor:** Google Play Release Engineer & DevSecOps Specialist  
**Application ID:** `com.jugajug.android`  
**Application Name:** BONDHOO  
**Version Code:** 2 | **Version Name:** 1.0.1  
**Status:** **READY FOR OWNER ENROLLMENT (CONTROLLED GATE: PENDING OWNER PLAY CONSOLE ACTION)**

---

## 1. Google Play Console Key Management Model

Google Play App Signing requires a two-key architecture:
1. **Upload Key (Developer Managed):** Used by the local release engineer or CI pipeline to sign the `.aab` file uploaded to Google Play. The active key is `CN=Bondhoo Upload Key` (SHA-256: `26:0B:64:E1:...:46:55`).
2. **App Signing Key (Google Managed):** Maintained within Google Cloud KMS. Google removes the upload signature and re-signs user distribution APKs with the App Signing Key.

---

## 2. Play App Signing Registration Status

- **Current Play Listing Status:** First-time release / Not yet uploaded.
- **Action Required by Owner:**
  1. Log in to Google Play Console.
  2. Navigate to: `Release > Setup > App signing`.
  3. Select **Let Google manage and protect your app signing key** (recommended).
  4. In the first AAB upload step, provide `app-release.aab`. Google Play will automatically register `bondhoo-upload-cert.pem` as the permanent Upload Certificate.
  5. If an Upload Key reset is ever needed, the owner uploads `/Users/mizanurrahman/.android/keystores/bondhoo-upload-cert.pem` via Play Console Support.

---

## 3. Firebase Certificate Alignment Matrix

| Client / Variant | Package Name | Registered SHA-1 / SHA-256 Status |
|---|---|---|
| **Release** | `com.jugajug.android` | Upload Key: `00:04:AE:42:...:ED` / `26:0B:64:E1:...:55` |
| **Debug** | `com.jugajug.android.debug` | Android Debug Key: Registered in `google-services.json` |
| **Staging** | `com.jugajug.android.staging` | Staging Key: Registered in `google-services.json` |

*Note: Once Google Play generates the final App Signing Key, the owner must copy its SHA-1/SHA-256 from Play Console into Firebase Console for Google Sign-In and Phone Auth to function on distribution builds.*

