# BONDHOO PHASE 14.5.2 — CERTIFICATE MATCH & CRYPTOGRAPHIC VALIDATION REPORT
**Execution Timestamp:** 2026-11-05T14:41:39+06:00  
**Auditor / Engineer:** Principal Application Security Auditor & Release Security Engineer  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Executive Summary

This report establishes the forensic cross-validation of the X.509 public signing certificates embedded within:
1. The **Keystore file** (`android/app/keystore/connectly-release.jks`, alias: `connectly`)
2. The **Google Play App Bundle** (`android/app/build/outputs/bundle/release/app-release.aab`)
3. The **Standalone Device APK** (`android/app/build/outputs/apk/release/app-release.apk`)

To avoid false identity assumptions based on subject names, all certificates were extracted to DER/X.509 binary representations and evaluated by their cryptographic **SHA-1** and **SHA-256 digests**.

---

## 2. Cryptographic Match Matrix

| Signing Target | Public Key Algorithm & Size | SHA-1 Fingerprint (Hex) | SHA-256 Fingerprint (Hex) | Exact Binary Match? |
|---|---|---|---|---|
| **Keystore (`connectly-release.jks`)** | RSA 2048 bits (`SHA384withRSA`) | `D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C` | `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41` | **BASELINE REFERENCE** |
| **Release AAB (`CONNECTL.RSA`)** | RSA 2048 bits (`SHA384withRSA`) | `D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C` | `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41` | **MATCH (100% Byte-Identical)** |
| **Release APK (Scheme v2 Block)** | RSA 2048 bits (`SHA384withRSA`) | `D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C` | `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41` | **MATCH (100% Byte-Identical)** |

---

## 3. Certificate Properties & Validity Audit

- **Subject / Owner DN:** `CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD`
- **Issuer DN:** `CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD` (Self-Signed Root)
- **Serial Number:** `a03b8bdc731fd79`
- **Validity Window:**
  - **Not Before:** `Sun Nov 01 08:26:58 GMT+06:00 2026`
  - **Not After:** `Thu Mar 19 08:26:58 GMT+06:00 2054`
  - **Total Active Span:** 28 Years (~10,000 days)
- **APK Signature Schemes Supported:**
  - Scheme v1 (JAR signing): Disabled (AAB handles JAR signing for Play Store)
  - Scheme v2 (APK Signature v2): **Active & Verified**
  - Scheme v3 / v4: Not enabled (Standard for multi-ABI universal APKs)

---

## 4. Conclusion

All three targets are signed by the exact same cryptographic keypair. There is zero certificate drift across the release artifacts.

**Stage 03 Determination:** **PASS (CERTIFICATE MATCH VALIDATED & CONFIRMED IDENTICAL)**.

