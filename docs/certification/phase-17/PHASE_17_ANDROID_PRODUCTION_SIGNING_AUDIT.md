# PHASE 17 — ANDROID PRODUCTION SIGNING & RELEASE ARTIFACT AUDIT

**Project:** Bondhoo Android Application (`com.jugajug.android`)  
**Version:** `versionName = "1.0.1"`, `versionCode = 2`  
**Keystore:** `android/app/keystore/connectly-release.jks` (Local disk only; untracked from Git)  
**Timestamp:** 2026-11-20T20:17:00+06:00  
**Final Status:** **PASS**

---

## 1. Keystore & Signing Identity Verification

| Check | Expected | Actual Verified Result | Status |
| :--- | :--- | :--- | :---: |
| **Keystore Exists Locally** | `android/app/keystore/connectly-release.jks` | Present (`2,766 bytes`, created Nov 1, 2026) | **PASS** |
| **Keystore Untracked in Git** | `git ls-files "*.jks"` returns empty | Empty (`0` `.jks` files tracked in `HEAD`) | **PASS** |
| **`android/keystore.properties` Gitignored** | Protected by `.gitignore` | Verified (`git status` does not list `android/keystore.properties`) | **PASS** |
| **Key Alias** | `connectly` | `connectly` (`PrivateKeyEntry`, `RSA 2048-bit`) | **PASS** |
| **Signer Certificate DN** | `CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD` | `CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD` | **PASS** |
| **Certificate SHA-256 Digest** | `3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741` | `3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741` (Exact match) | **PASS** |
| **Certificate SHA-1 Digest** | `d3497eb462ca34961c9f2e96c6e2911ce9e8345c` | `d3497eb462ca34961c9f2e96c6e2911ce9e8345c` | **PASS** |
| **Certificate Validity** | Valid through `2054-03-19` | `The signer certificate will expire on 2054-03-19.` | **PASS** |
| **No Debug Signing Fallback** | No `CN=Android Debug` fallback in `android/app/build.gradle.kts` | Verified removed | **PASS** |

---

## 2. Signed Release Artifacts & Checksums

| Artifact | Path | Size | SHA-256 Checksum | Signature Verification | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **Release APK** | `android/app/build/outputs/apk/release/app-release.apk` | `48 MB` | `51fc4673c2d3f6361315574b72714eea566057df2804985ff8ecbdd03d17204e` | `apksigner verify`: **Verifies (v2 scheme: true)** | **PASS** |
| **Staged Public APK** | `public/bondhoo.apk` | `48 MB` | `51fc4673c2d3f6361315574b72714eea566057df2804985ff8ecbdd03d17204e` | Identical copy of signed `app-release.apk` | **PASS** |
| **Release AAB** | `android/app/build/outputs/bundle/release/app-release.aab` | `34 MB` | `1c817afde6455a073b79d3ab84617d0477207a54b4f092ba76234a215031be9f` | `jarsigner -verify -verbose -certs`: **Signed by `CN=Connectly Release`** | **PASS** |
