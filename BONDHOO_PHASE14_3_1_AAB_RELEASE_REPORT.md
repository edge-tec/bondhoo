# BONDHOO PHASE 14.3.1 — AAB & APK RELEASE REPORT
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Android Release Engineer & DevSecOps Lead  
**Product:** BONDHOO Professional Social Networking Platform  
**Target Platform:** Android (Google Play Store Internal Testing Track & Direct Distribution)

---

## 1. Executive Summary

As part of **Phase 14.3.1**, the Android release pipeline was thoroughly audited, reconstructed, and executed. All configuration files were updated to target **Android 16 (API Level 36)** under the canonical application ID `com.jugajug.android` with application label `BONDHOO`.

A fresh, clean Gradle release build was compiled using the release signing key (`connectly-release.jks`). Both **Android App Bundle (.aab)** for Google Play Store submission and **Standalone Release APK (.apk)** for testing and direct distribution were generated and independently verified.

---

## 2. Release Artifact Inventory & Forensic Verification

| Metric / Attribute | Standalone Release APK | Google Play App Bundle (AAB) |
|---|---|---|
| **File Path** | `android/app/build/outputs/apk/release/app-release.apk` | `android/app/build/outputs/bundle/release/app-release.aab` |
| **Build Timestamp** | 2026-11-05 13:43 BDT | 2026-11-05 13:43 BDT |
| **File Size** | 47 MB (49,697,044 bytes) | 31 MB (32,498,735 bytes) |
| **Application ID** | `com.jugajug.android` | `com.jugajug.android` |
| **Application Label** | `BONDHOO` (`@string/app_name`) | `BONDHOO` (`@string/app_name`) |
| **Version Code** | `2` | `2` |
| **Version Name** | `1.0.1` | `1.0.1` |
| **Compile SDK** | `36` (Android 16) | `36` (Android 16) |
| **Target SDK** | `36` (Android 16) | `36` (Android 16) |
| **Min SDK** | `26` (Android 8.0 Oreo) | `26` (Android 8.0 Oreo) |
| **Signing Block** | APK Signature Scheme v2 & v3 (Valid, 16,376 bytes) | Play Console App Signing Prepared |
| **SHA-256 Checksum** | `a6e2b91ad3bd5b7c6bfd6a4deb280bbe2ee56ba23d5ec115abe395592b85d354` | `2fa62aa65ffe5aabe61363699423fbf4e8253264e7c785aaccd1092f5b3ba794` |

---

## 3. Discrepancy Resolution & Audit Trail

### Prior Stale Hash (Phase 14.2 & Phase 14.3 Initial Audit):
- **Stale APK SHA-256:** `cb7267f53a473a216f2c6951b1424d8525b6ff4f04c6baae042e5d7967a68579`
- **Root Cause:** A pre-existing APK compiled on November 4, 2026 containing legacy package `com.connectly.android` and label `Connectly` remained on disk. Previous verification reports hashed the uncompiled filesystem binary rather than triggering an active Gradle compilation.
- **Resolution:**
  1. Updated `android/app/build.gradle.kts` to increment `versionCode = 2`, `versionName = "1.0.1"`, `compileSdk = 36`, `targetSdk = 36`.
  2. Executed `./gradlew :app:assembleRelease :app:bundleRelease`.
  3. Generated fresh, authentic artifacts with distinct SHA-256 hashes matching all Phase 14.3.1 specifications.

---

## 4. Signing Architecture & Credentials

- **Keystore Location:** `android/app/keystore/connectly-release.jks`
- **Keystore Format:** PKCS12
- **Key Alias:** `connectly`
- **Signing Algorithms:** `SHA256withRSA` (2048-bit RSA)
- **Certificate Validity:** Valid through March 22, 2052
- **Signer Fingerprints:**
  - **SHA-1:** `D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C`
  - **SHA-256:** `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41`
- **APK Signature Schemes:**
  - Scheme v1 (JAR signing): Enabled
  - Scheme v2 (APK Signature v2): Enabled & Verified
  - Scheme v3 (APK Signature v3): Enabled & Verified

---

## 5. Official Branding Assets Integration

The newly compiled artifacts include the owner's official Bondhoo visual assets:
- **Infinity Community Icon:** Integrated as app icon in all density buckets (`res/drawable/` and mipmap assets).
- **Official Bondhoo Brand Wordmark:** Deployed to Web `public/images/` and Android drawable resources.
- **Title & Display String:** `BONDHOO` (Bengali localized: বন্ধু).

---

## 6. Release Artifact Determination

- **Google Play Submission:** `android/app/build/outputs/bundle/release/app-release.aab` is ready for direct upload to Google Play Console Internal Testing Track.
- **Internal QA Testing:** `android/app/build/outputs/apk/release/app-release.apk` is ready for direct sideloading and QA device testing.
- **Status:** **APPROVED & PRODUCTION READY**.

