# PHASE 16 — ANDROID SIGNING CERTIFICATION REPORT

**Project:** Bondhoo Android Application (`com.jugajug.android`)  
**Timestamp:** 2026-11-20T19:15:00+06:00  
**Final Status:** **PASS**

---

## 1. Signing Verification Results

| Requirement | Expected | Actual Verified Result | Status |
| :--- | :--- | :--- | :--- |
| **Debug Certificate Prohibition** | Must NOT be `CN=Android Debug,O=Android,C=US` | Signed with `CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD` | **PASS** |
| **Production `.jks` Keystore Signing** | Signed with owner's production release key (`connectly-release.jks`) | Verified via `apksigner verify --verbose --print-certs` and `jarsigner -verify` | **PASS** |
| **APK Signature Scheme v2** | `Verified using v2 scheme (APK Signature Scheme v2): true` | `true` | **PASS** |
| **Certificate SHA-256 Digest** | Consistent across Phase 11–16 releases | `3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741` | **PASS** |
| **Signed APK SHA-256** | Recorded deterministic checksum | `51fc4673c2d3f6361315574b72714eea566057df2804985ff8ecbdd03d17204e` | **PASS** |
| **Signed AAB SHA-256** | Recorded deterministic checksum | `1c817afde6455a073b79d3ab84617d0477207a54b4f092ba76234a215031be9f` | **PASS** |
