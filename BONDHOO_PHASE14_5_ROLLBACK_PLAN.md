# BONDHOO PHASE 14.5 — ROLLBACK & CONTINGENCY PLAN
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal DevSecOps Lead & Release Manager  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Overview & Rollback Architecture

Because Phase 14.5 operates strictly on the **Google Play Store Internal Testing Track**:
- The public Play Store listing is not active.
- End users on web or production databases are completely isolated from testing artifacts.
- Rollbacks can be executed with zero downtime or user impact.

---

## 2. Contingency Procedures by Event

### Event 1: Duplicate Version Code Conflict
- **Symptom:** Play Console displays error: `"Version code 2 has already been used for package com.jugajug.android."`
- **Action:**
  1. Update `android/app/build.gradle.kts`:
     ```kotlin
     versionCode = 3
     versionName = "1.0.2"
     ```
  2. Run: `./gradlew :app:bundleRelease`
  3. Upload the freshly generated `app-release.aab` with Version Code 3.

### Event 2: Google Sign-In Auth Failure on Tester Devices (`ApiException: 10`)
- **Symptom:** Testers can log in via email/password, but Google Sign-In button throws Developer Error 10.
- **Root Cause:** Play App Signing Key SHA-1 has not been added to Firebase Console or Google Cloud Credentials.
- **Action:**
  1. Open Play Console > Release > Setup > App integrity.
  2. Copy `App signing key SHA-1`.
  3. Add to Firebase Console > Project Settings > Your apps > `com.jugajug.android`.
  4. Wait 5 minutes for Google OAuth cache propagation. No new build required.

### Event 3: Critical Crash Encountered During Internal Testing
- **Symptom:** Testers encounter an unhandled exception in the Internal Testing build.
- **Action:**
  1. Navigate to **Google Play Console > Testing > Internal testing**.
  2. Under the release, click **Halt rollout** or remove testers from the email list.
  3. Revert code fix locally, increment version code, and release a hotfix build.
  4. The standalone APK (`app-release.apk`) remains available for direct ADB debugging.

### Event 4: Upload Key Loss / Corruption
- **Symptom:** Local keystore `connectly-release.jks` is lost or inaccessible.
- **Action:**
  1. Under Google Play App Signing, Google protects the master distribution key.
  2. The owner contacts Google Play Support and requests an **Upload Key Reset**.
  3. A new Upload Key is generated locally, and its `.pem` certificate is submitted to Google.
  4. Google registers the new Upload Key within 48 hours without disrupting existing users.

---

## 3. Rollback Safety Verification

- [x] Backend database schema untouched (no destructive rollbacks required).
- [x] Git repository state completely clean and non-destructively versioned.
- [x] Standalone QA APK available as offline fallback.

**Conclusion:** The rollback framework is production-safe, resilient, and fully operational.

