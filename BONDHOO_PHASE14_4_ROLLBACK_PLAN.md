# BONDHOO PHASE 14.4 — ROLLBACK & CONTINGENCY PLAN
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal DevSecOps Engineer & Release Manager  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Overview & Risk Tolerance

The **Phase 14.4** rollout targets the **Google Play Store Internal Testing Track**. 
Because internal testing is isolated from the public store listing:
- Rollback can be performed **instantly** without impacting external end users.
- Production web application and database infrastructure remain completely isolated from Android client test releases.

---

## 2. Contingency Scenarios & Immediate Remediation Procedures

### Scenario 1: AAB Upload Blocked by Play Console Error
- **Trigger:** Play Console reports a package naming collision, manifest syntax error, or duplicate version code.
- **Remediation Procedure:**
  1. Inspect the exact error message reported by Play Console.
  2. If version code was previously uploaded in a test draft, increment `versionCode = 3` and `versionName = "1.0.2"` in `android/app/build.gradle.kts`.
  3. Recompile the bundle using `./gradlew :app:bundleRelease`.
  4. Upload the new `.aab` file to the same Internal Testing release draft.

### Scenario 2: Play App Signing Key Mismatch (Google Sign-In Failure)
- **Trigger:** Internal testers report `ApiException: 10` when attempting Google Sign-In on Play-installed builds.
- **Root Cause:** Play App Signing Key SHA-1 has not been registered in Firebase / Google Cloud Console.
- **Remediation Procedure:**
  1. Navigate to **Google Play Console > Release > Setup > App integrity**.
  2. Copy the **App signing key SHA-1 fingerprint**.
  3. Paste into **Firebase Console > Project Settings > Your apps > com.jugajug.android**.
  4. Allow 5–10 minutes for Google Auth backend propagation. (No new AAB upload required).

### Scenario 3: Critical Crash or High-Risk Bug Discovered in Internal Testing
- **Trigger:** Testers encounter an unhandled exception or critical regression in core functionality.
- **Remediation Procedure:**
  1. In Google Play Console, open **Internal testing**.
  2. Click **Create new release** with a hotfix build (`versionCode = 3`).
  3. Upload the hotfix AAB.
  4. Alternatively, remove testers from the email list to temporarily halt installations.
  5. Fall back to the standalone debug/release APK (`app-release.apk`) for local debugger attachment.

### Scenario 4: Keystore Access / Key Loss Contingency
- **Trigger:** Upload Key (`connectly-release.jks`) corrupted or inaccessible.
- **Remediation Procedure:**
  1. In Google Play App Signing, because the App Signing Key is securely stored by Google, a lost Upload Key **does NOT** break the application or lock users out.
  2. The owner contacts Google Play Developer Support via Play Console and requests an **Upload Key Reset**.
  3. A new Upload Key is generated locally, and its public `.pem` certificate is submitted to Google.
  4. Within 48 hours, Google registers the new Upload Key, allowing subsequent AAB uploads.

---

## 3. Rollback Safety Checklist

- [x] Zero irreversible schema migrations executed on the backend.
- [x] Zero destructive Git operations (clean branch state preserved).
- [x] Production web traffic remains 100% active and uninterrupted.
- [x] Standalone APK kept available as a fallback for internal testing.

**Conclusion:** The rollback and contingency framework is fully verified and production-safe.

