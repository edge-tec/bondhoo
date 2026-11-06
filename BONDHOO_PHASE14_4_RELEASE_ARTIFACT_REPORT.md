# BONDHOO PHASE 14.4 — STAGE 03: SIGNED RELEASE ARTIFACT VERIFICATION REPORT
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Android Release Engineer & Quality Assurance Lead  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Executive Summary

This report establishes the forensic technical verification of the release artifacts generated for **Google Play Store Internal Testing** and **Direct QA Device Testing**. Both the **Android App Bundle (.aab)** and **Standalone Release APK (.apk)** were compiled, signed with the verified Upload Key (`connectly-release.jks`), and validated using official Android SDK tools (`jarsigner`, `apksigner`, `aapt2`).

---

## 2. Release Artifact Inventory & Checksums

| Metric / Parameter | Google Play App Bundle (AAB) | Standalone Device APK |
|---|---|---|
| **Relative Path** | `android/app/build/outputs/bundle/release/app-release.aab` | `android/app/build/outputs/apk/release/app-release.apk` |
| **File Size** | 31 MB (32,498,735 bytes) | 47 MB (49,697,044 bytes) |
| **SHA-256 Checksum** | `2fa62aa65ffe5aabe61363699423fbf4e8253264e7c785aaccd1092f5b3ba794` | `a6e2b91ad3bd5b7c6bfd6a4deb280bbe2ee56ba23d5ec115abe395592b85d354` |
| **Application ID** | `com.jugajug.android` | `com.jugajug.android` |
| **Application Label** | `BONDHOO` | `BONDHOO` |
| **Version Code** | `2` | `2` |
| **Version Name** | `1.0.1` | `1.0.1` |
| **Platform Build** | Android 16 (API Level 36) | Android 16 (API Level 36) |
| **Compile SDK** | `36` | `36` |
| **Target SDK** | `36` | `36` |
| **Min SDK** | `26` (Android 8.0 Oreo) | `26` (Android 8.0 Oreo) |
| **Primary Verifier Tool** | `jarsigner -verify -verbose -certs` | `apksigner verify --verbose --print-certs` |
| **Verification Result** | **JAR VERIFIED (Exit Code 0)** | **VERIFIES (Scheme v2: TRUE)** |

---

## 3. Tool-by-Tool Cryptographic Proofs

### A. Jarsigner Verification (AAB Bundle):
- **Command:** `jarsigner -verify -certs android/app/build/outputs/bundle/release/app-release.aab`
- **Output:**
  - `jar verified.`
  - `The signer certificate will expire on 2054-03-19.`
  - Valid cryptographic hash table for all bundle entries including `base/manifest/AndroidManifest.xml` and native ABIs.

### B. Apksigner Verification (APK Binary):
- **Command:** `apksigner verify --verbose --print-certs android/app/build/outputs/apk/release/app-release.apk`
- **Output:**
  - `Verifies: true`
  - `Verified using v2 scheme (APK Signature Scheme v2): true`
  - `Number of signers: 1`
  - `Signer #1 certificate DN: CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD`
  - `Signer #1 certificate SHA-256 digest: 3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741`
  - `Signer #1 certificate SHA-1 digest: d3497eb462ca34961c9f2e96c6e2911ce9e8345c`
  - `Signer #1 key algorithm: RSA (2048 bits)`

### C. AAPT2 Badging Inspection:
- **Command:** `aapt2 dump badging android/app/build/outputs/apk/release/app-release.apk`
- **Output:**
  - `package: name='com.jugajug.android' versionCode='2' versionName='1.0.1' platformBuildVersionName='16' platformBuildVersionCode='36' compileSdkVersion='36'`
  - `minSdkVersion:'26'`
  - `targetSdkVersion:'36'`
  - `application-label:'BONDHOO'`

---

## 4. Release Purpose & Distribution Mapping

1. **`app-release.aab`:** Upload directly to Google Play Console under **Internal Testing**. Google Play processes this bundle into split APKs optimized for each user device's density, ABI, and language.
2. **`app-release.apk`:** Used by QA team for direct ADB sideloading and manual validation on physical Android test devices.

**Stage 03 Determination:** **PASS (RELEASE ARTIFACTS VERIFIED & SIGNED)**.

