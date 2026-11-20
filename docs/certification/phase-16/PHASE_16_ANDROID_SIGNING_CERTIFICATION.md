# PHASE 16 — ANDROID SIGNING CERTIFICATION REPORT

**Project:** Bondhoo Android Application (`com.jugajug.android`)  
**Phase:** 16.9  
**Date:** 2026-11-20

---

## 1. Signing Verification Results

| Requirement | Expected | Actual | Status |
| :--- | :--- | :--- | :--- |
| **Debug Certificate Prohibition** | Must NOT be `CN=Android Debug,O=Android,C=US` | Hardened `android/app/build.gradle.kts` to remove `debug.keystore` fallback from `release` buildType. | **PASS (Debug Fallback Eliminated)** |
| **Production `.jks` Keystore Signing** | Signed with original production release key | Original production `.jks` and `android/keystore.properties` not provided in workspace. | **BLOCKED (Missing Production Keystore)** |
| **APK Signature Schemes (v1/v2/v3)** | `apksigner verify --verbose --print-certs` | Deferred until operator signs release APK with production `.jks`. | **BLOCKED** |
| **Play Store Upgrade Compatibility** | Certificate SHA-256 fingerprint matches previous Play Store release | Cannot be verified without original production `.jks`. | **BLOCKED** |

---

## 2. Operator Instructions to Produce Signed Production APK & AAB

1. Copy `android/keystore.properties.example` to `android/keystore.properties` (this file is gitignored):
   ```properties
   storeFile=/absolute/path/to/bondhoo-release.jks
   storePassword=YOUR_KEYSTORE_PASSWORD
   keyAlias=YOUR_KEY_ALIAS
   keyPassword=YOUR_KEY_PASSWORD
   ```
2. Build and verify the signed release APK and App Bundle:
   ```bash
   ./android/gradlew -p android assembleRelease bundleRelease
   apksigner verify --verbose --print-certs android/app/build/outputs/apk/release/app-release.apk
   shasum -a 256 android/app/build/outputs/apk/release/app-release.apk
   ```
