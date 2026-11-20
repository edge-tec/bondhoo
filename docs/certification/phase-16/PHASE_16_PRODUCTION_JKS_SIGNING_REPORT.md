# PHASE 16 — PRODUCTION JKS SIGNING REPORT

**Project:** Bondhoo Android Application (`com.jugajug.android`)  
**Timestamp:** 2026-11-20T19:15:00+06:00  
**Environment:** macOS Local Build Workstation (JDK 21 / Android SDK Build-Tools 35.0.0)  
**Final Status:** **PASS**

---

## 1. Original Production Keystore Discovery & Verification

During Phase 16.1 repository history inspection (`git log --all --full-history -- "*.jks"`), the original production release keystore created in Phase 11 (commit `9b52f37d07d242621b3d7eb4a4c38ec43e43e58a`, Nov 1, 2026) was located at:
- **Local Path:** `android/app/keystore/connectly-release.jks` (`2,766 bytes`)
- **Git Index Remediation:** Untracked from Git index via `git rm --cached android/app/keystore/connectly-release.jks` while keeping the file on disk, and configured via gitignored `android/keystore.properties`.

---

## 2. Keystore Certificate Inspection (`keytool -list -v`)

**Command Executed:**
```bash
keytool -list -v -keystore android/app/keystore/connectly-release.jks
```

**Verified Certificate Attributes:**
| Attribute | Verified Value |
| :--- | :--- |
| **Alias Name** | `connectly` |
| **Entry Type** | `PrivateKeyEntry` |
| **Owner DN** | `CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD` |
| **Issuer DN** | `CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD` |
| **Valid From** | `Sun Nov 01 08:26:58 GMT+06:00 2026` |
| **Valid Until** | `Thu Mar 19 08:26:58 GMT+06:00 2054` (10,000 days) |
| **Key Algorithm / Size** | `RSA` / `2048 bits` (`SHA384withRSA`) |
| **Certificate SHA-256 Fingerprint** | `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41` |
| **Certificate SHA-1 Fingerprint** | `D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C` |
| **Debug Keystore Used?** | **NO** (`CN=Android Debug` fallback removed from `android/app/build.gradle.kts`) |
