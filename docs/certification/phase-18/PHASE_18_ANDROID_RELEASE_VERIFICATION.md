# PHASE 18 — ANDROID PRODUCTION RELEASE VERIFICATION

**Project:** Bondhoo Android Application (`com.jugajug.android`)  
**Artifact Verified:** `public/bondhoo.apk` (`versionName = "1.0.1"`, `versionCode = 2`)  
**Timestamp:** 2026-11-20T20:25:23+06:00  
**Final Status:** **PASS**

---

## 1. Cryptographic & Checksum Verification Evidence

**Commands Executed:**
```bash
shasum -a 256 public/bondhoo.apk android/app/build/outputs/apk/release/app-release.apk android/app/build/outputs/bundle/release/app-release.aab
/Users/mizanurrahman/Library/Android/sdk/build-tools/35.0.0/apksigner verify --verbose --print-certs public/bondhoo.apk
```

| Verification Item | Expected | Verified Result | Status |
| :--- | :--- | :--- | :---: |
| **`public/bondhoo.apk` SHA-256** | Matches Phase 17 (`51fc4673...`) | `51fc4673c2d3f6361315574b72714eea566057df2804985ff8ecbdd03d17204e` (Exact match) | **PASS** |
| **`app-release.aab` SHA-256** | Matches Phase 17 (`1c817afd...`) | `1c817afde6455a073b79d3ab84617d0477207a54b4f092ba76234a215031be9f` (Exact match) | **PASS** |
| **APK Signature Scheme v2** | `true` | `Verified using v2 scheme (APK Signature Scheme v2): true` | **PASS** |
| **Signer Certificate DN** | `CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD` | Exact match (Expires `2054-03-19`) | **PASS** |
| **Certificate SHA-256 Digest** | `3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741` | `3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741` (Exact match) | **PASS** |
| **No Debug Signing / No Cleartext HTTP** | Enforced in `build.gradle.kts` & `network_security_config.xml` | Verified | **PASS** |
