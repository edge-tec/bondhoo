# PHASE 16 — ANDROID PRODUCTION RELEASE BUILD REPORT

**Project:** Bondhoo Android Application  
**Application ID:** `com.jugajug.android`  
**Version:** `versionName = "1.0.1"`, `versionCode = 2`  
**Phase:** 16.9  
**Date:** 2026-11-20

---

## 1. Release Build Configuration Audit (`android/app/build.gradle.kts`)

| Parameter | Configured Value | Status |
| :--- | :--- | :--- |
| `applicationId` | `"com.jugajug.android"` | **PASS** |
| `versionCode` | `2` | **PASS** |
| `versionName` | `"1.0.1"` | **PASS** |
| `minSdk` / `targetSdk` / `compileSdk` | `24` / `35` / `35` | **PASS** |
| `isMinifyEnabled` (release) | `true` (R8 code shrinking enabled) | **PASS** |
| `isShrinkResources` (release) | `true` | **PASS** |
| Production API Base URL | `https://bondhoo.com/api/v1/` (`NetworkModule.PROD_HOST = "bondhoo.com"`) | **PASS** |
| Production WebSocket URL | `wss://bondhoo.com/app/connectly_reverb_key` | **PASS** |
| Network Security Config (`network_security_config.xml`) | `cleartextTrafficPermitted="false"` for `bondhoo.com`, system trust anchors only | **PASS** |
| Debug Keystore Fallback Removed | Removed from `android/app/build.gradle.kts` (`hasReleaseKeystore` check enforced) | **PASS** |

---

## 2. Compilation & Unit Test Verification

- `:feature:messenger:testDebugUnitTest` — **PASS**
- `:feature:profile:testDebugUnitTest` — **PASS**
- `:app:compileReleaseKotlin` — **PASS** (`0` errors)

---

## 3. Release Signing Status

- Because `android/keystore.properties` and the original production `.jks` file are not present on this machine, `assembleRelease` produces an unsigned release artifact (`app-release-unsigned.apk`) rather than silently signing with the debug certificate.
- Once the operator provides `android/keystore.properties` pointing to their production `.jks` file and runs `./android/gradlew -p android assembleRelease bundleRelease`, Gradle will produce signed `app-release.apk` and `app-release.aab` artifacts.
