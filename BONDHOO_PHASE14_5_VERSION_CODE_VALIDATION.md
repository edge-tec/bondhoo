# BONDHOO PHASE 14.5 — STAGE 03: VERSION CODE VALIDATION & PROTOCOL REPORT
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Android Release Engineer & Quality Assurance Lead  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Executive Summary

This report establishes the protocol for validating the application's **Version Code** (`versionCode`) and **Version Name** (`versionName`) against the target Google Play Console account.

In adherence to Absolute Safety Rule #6:
- The version code will **NOT** be modified without verifiable evidence that Version Code 2 has already been consumed in a prior Play Console upload.
- Version Code 2 is currently baked into both `app-release.aab` and `app-release.apk`.

---

## 2. Version Attribute Matrix

| Build Property | Current Verified Value | Policy & Operational Rule |
|---|---|---|
| **Version Code (`versionCode`)** | `2` | Integer strictly increasing with every Play Store release. |
| **Version Name (`versionName`)** | `1.0.1` | User-facing semantic version string. |
| **Target SDK** | `36` (Android 16) | Meets and exceeds Play Store minimum requirement. |
| **Package Name** | `com.jugajug.android` | Unique application identifier. |

---

## 3. Owner Verification Protocol Prior to Upload

Before uploading `app-release.aab` to Google Play Console, the owner must verify the following:

### Case A: New App or Fresh Release (Expected Scenario)
- **Condition:** The app `BONDHOO` (`com.jugajug.android`) is newly created in Play Console, or no draft release has been published with Version Code 2.
- **Action:** Retain **Version Code 2** without change. Upload `android/app/build/outputs/bundle/release/app-release.aab`.

### Case B: Version Code 2 Already Consumed (Conflict Scenario)
- **Condition:** Play Console displays the error:
  `"Version code 2 has already been used for package com.jugajug.android. Please try another version code."`
- **Action Required:**
  1. The owner informs the engineering team of the conflict with screenshot/error text.
  2. The engineering team executes a controlled version increment in `android/app/build.gradle.kts`:
     - `versionCode = 3`
     - `versionName = "1.0.2"`
  3. Recompiles the bundle: `./gradlew :app:bundleRelease` (preserving prior artifacts).
  4. Generates fresh checksums and delivers `app-release.aab` with Version Code 3.

---

## 4. Conclusion

Version Code 2 is currently valid and ready for the initial upload attempt. No preemptive version change will be made without actual Play Console error evidence.

**Stage 03 Determination:** **PASS (VERSION PROTOCOL DEFINED & VERSION CODE 2 VALIDATED)**.

