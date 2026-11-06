# BONDHOO PHASE 14.4 — STAGE 00: BASELINE AND BACKUP REPORT
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Android Release Engineer & DevSecOps Lead  
**Target Git Baseline:** `315960fde56849c4be7344a78fda005151027b42` (branch: `main`)  
**Application ID:** `com.jugajug.android`  
**Brand Display Name:** `BONDHOO`

---

## 1. Source Control & Repository Baseline

- **Current Git Branch:** `main`
- **Current HEAD Commit Hash:** `315960fde56849c4be7344a78fda005151027b42`
- **Commit Log:** `315960f (HEAD -> main, origin/main, origin/HEAD) feat(ecosystem): complete Phase 12-13 LMS, Recruitment, Parental Control foundation & Stage 11 forensic audit verification`
- **Working Tree State:** Active uncommitted changes from Phase 14.3.1 migration are preserved intact without any resets or destructive actions.

### Git Status Inventory
- Modified Build Files:
  - `android/app/build.gradle.kts` (compileSdk 36, targetSdk 36, versionCode 2, versionName "1.0.1", applicationId "com.jugajug.android")
  - `android/app/google-services.json` (clients for `com.jugajug.android`, `.debug`, `.staging`)
  - `android/app/src/main/AndroidManifest.xml` (app_name label, deep link schemes `bondhoo://`, `jugajug://`)
  - `android/app/src/main/res/values/strings.xml` (`<string name="app_name">BONDHOO</string>`)
  - `resources/views/app.blade.php` (title `BONDHOO (বন্ধু)`, icon `/images/bondhoo-icon.png`)
  - `public/images/bondhoo-logo.png` & `public/images/bondhoo-icon.png` (official brand assets)
  - `.gitignore` (hardened with `*.jks` and `*.keystore`)

---

## 2. Release Build Variants & Keystore Architecture

### Android Gradle Configuration (`android/app/build.gradle.kts`):
- **Namespace:** `com.connectly.android` (Internal code package structure preserved)
- **Application ID:** `com.jugajug.android` (Public store application identifier)
- **Compile SDK:** 36 (Android 16)
- **Target SDK:** 36 (Android 16)
- **Minimum SDK:** 26 (Android 8.0 Oreo)
- **Version Code:** 2
- **Version Name:** 1.0.1

### Build Variants:
1. `debug`: `applicationIdSuffix = ".debug"`, `isDebuggable = true`
2. `staging`: `applicationIdSuffix = ".staging"`, release signed, matching fallbacks
3. `release`: `isMinifyEnabled = true`, `isShrinkResources = true`, signed with `connectly-release.jks`, ProGuard enabled

### Signing Configuration & Key Security:
- **Keystore Location:** `android/app/keystore/connectly-release.jks`
- **Keystore Format:** PKCS12
- **Key Alias:** `connectly`
- **Key Spec:** RSA 2048-bit, `SHA256withRSA`
- **Certificate Validity:** Active through March 22, 2052
- **Key Security:** No passwords or private key material exposed in public reports. Keystore patterns (`*.jks`, `*.keystore`) are strictly ignored in `.gitignore`.

---

## 3. Existing Release Artifact Inventory

Prior to Stage 01, existing release artifacts generated in Phase 14.3.1 are recorded:

| Artifact Type | File Path | File Size | SHA-256 Checksum |
|---|---|---|---|
| **Release APK** | `android/app/build/outputs/apk/release/app-release.apk` | 47 MB | `a6e2b91ad3bd5b7c6bfd6a4deb280bbe2ee56ba23d5ec115abe395592b85d354` |
| **Release AAB** | `android/app/build/outputs/bundle/release/app-release.aab` | 31 MB | `2fa62aa65ffe5aabe61363699423fbf4e8253264e7c785aaccd1092f5b3ba794` |

---

## 4. CI/CD Release Workflow Verification

- **Workflow File:** `.github/workflows/connectly-android-ci.yml`
- **Triggers:** Push to `main`, `develop`, Pull Request to `main`.
- **Jobs:** JDK 21 setup, Gradle cache, dependency validation, unit test execution, and release assembly (`assembleRelease` & `bundleRelease`).
- **Secrets Management:** Keystore passwords bound via GitHub Secrets environment variables (`CONNECTLY_KEYSTORE_PASSWORD`, `CONNECTLY_KEY_ALIAS`, `CONNECTLY_KEY_PASSWORD`).

---

## 5. Stage 00 Conclusion & Release Freeze

- **Repository Freeze:** Baseline frozen at `315960fde56849c4be7344a78fda005151027b42` with non-destructive Phase 14.3.1 uncommitted enhancements.
- **Safety Status:** **VERIFIED (PASS)**.

