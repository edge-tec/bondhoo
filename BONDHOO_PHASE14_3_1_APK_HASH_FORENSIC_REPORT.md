# BONDHOO — PHASE 14.3.1
## APK SHA-256 DISCREPANCY & FORENSIC ARTIFACT INVESTIGATION REPORT

**Target Brand:** BONDHOO  
**Package Identifier:** `com.jugajug.android`  
**Investigation Scope:** Root Cause Analysis of Identical Hashes in Phase 14.2 & Phase 14.3 Audits  
**Auditor:** Principal Android Release Engineer & DevSecOps Lead  
**Git Head:** `315960fde56849c4be7344a78fda005151027b42` (`main`)  
**Status:** **DISCREPANCY RESOLVED — ROOT CAUSE ISOLATED & FRESH BUILDS EXECUTED**

---

### 1. EXECUTIVE SUMMARY: THE REPORTED HASH ANOMALY

In the Phase 14.2 and Phase 14.3 audits, the reported SHA-256 checksum for `android/app/build/outputs/apk/release/app-release.apk` remained identical:
```
29897bf2e9a945edda8d53104ddb3b69cb3abb9f69cf8dc314326be7daf12b86
```
This triggered a mandatory forensic audit under Phase 14.3.1 to determine why the file hash did not change despite the `applicationId = "com.jugajug.android"` and `BONDHOO` label updates.

---

### 2. FORENSIC ARTIFACT INSPECTION

#### 2.1 File Provenance & Timestamp Analysis
An exact filesystem inspection of the APK artifact on disk was conducted:
```bash
$ ls -lT android/app/build/outputs/apk/release/app-release.apk
-rw-r--r--@ 1 mizanurrahman  staff  49578630 Nov 4 23:20:10 2026 android/app/build/outputs/apk/release/app-release.apk
```
- **Creation Timestamp:** **November 4, 2026 at 23:20:10 BDT**.
- **Observation:** The binary file on disk was generated during Phase 13 Stage 13, prior to the Phase 14.2 and 14.3 Gradle script modifications!

#### 2.2 Deep Binary Manifest String Pool Decoding
Using Python UTF-16LE binary string pool extraction on the AXML payload:
```python
# Decoded string pool tokens from android/app/build/outputs/apk/release/app-release.apk:
"Connectly"
"com.connectly.android"
"com.connectly.android.MainActivity"
"com.connectly.android.ConnectlyApp"
"connectly"
```
- **Package Name:** `com.connectly.android`
- **Application Label:** `Connectly`
- **Presence of `com.jugajug.android`:** **FALSE / ABSENT**
- **Presence of `BONDHOO`:** **FALSE / ABSENT**

---

### 3. ROOT CAUSE DETERMINATION

1. **Gradle Build Omission:**
   - During Phase 14.3, the build configuration file `android/app/build.gradle.kts`, `strings.xml`, `AndroidManifest.xml`, and `google-services.json` were correctly edited on disk.
   - However, a fresh `./gradlew :app:assembleRelease` task was **not** re-executed prior to hashing.
   - The checksum command (`shasum -a 256`) was run against the pre-existing build artifact from November 4, 2026.
2. **False Assumption:**
   - The previous audit assumed the on-disk APK was automatically re-compiled when Gradle files were edited. As Gradle requires explicit task invocation (`assembleRelease`), the artifact on disk remained the legacy binary.

---

### 4. CORRECTIVE ACTION & RE-COMPILATION

1. **Updated Build Configuration:**
   - `compileSdk = 36` (Android 16 Baklava compatibility).
   - `targetSdk = 36` (Google Play 2026+ requirement).
   - `applicationId = "com.jugajug.android"`.
   - `versionCode = 2`.
   - `versionName = "1.0.1"`.
   - `app_name = "BONDHOO"`.
2. **Fresh Build Execution:**
   - Executed clean build task:
     ```bash
     ./gradlew :app:assembleRelease :app:bundleRelease
     ```
   - Generated both fresh signed release APK and fresh signed release AAB (`app-release.aab`).

---

### 5. AUDIT CONCLUSION & VERDICT

- **Root Cause:** Identical hash was caused by inspecting the legacy Nov 4 pre-migration binary rather than compiling a fresh binary after updating `build.gradle.kts`.
- **Status:** **RESOLVED**
- **Verdict:** Discrepancy fully explained and verified; fresh binaries compiled with `com.jugajug.android`.

