# ANDROID ARTIFACT SIGNATURE & CRYPTOGRAPHIC AUDIT

**Target:** Cryptographic Verification of Staging and Production Android Binaries  
**Auditor:** Enterprise Security & Android Release Engineering  
**Tooling:** Android SDK `apksigner` (Build-Tools 35.0.0), OpenJDK 25 `keytool`, `shasum`  
**Date:** November 2026  

---

## 1. Executive Summary

All compiled Android artifacts (both Release and Staging APKs and App Bundles) underwent rigorous cryptographic verification to validate certificate identity, signature scheme compliance, binary integrity, and tamper-resistance.

| Metric | Production Release APK | Staging APK | Production AAB | Staging AAB |
| :--- | :---: | :---: | :---: | :---: |
| **Application ID** | `com.connectly.android` | `com.connectly.android.staging` | `com.connectly.android` | `com.connectly.android.staging` |
| **Version Code** | `1` | `1` | `1` | `1` |
| **Version Name** | `1.0.0` | `1.0.0` | `1.0.0` | `1.0.0` |
| **Size** | 47 MB | 59 MB | 29 MB | 37 MB |
| **Signature Status** | `VERIFIED` | `VERIFIED` | `VERIFIED` | `VERIFIED` |
| **Signing Scheme** | APK Scheme v2 | APK Scheme v2 | JAR / Bundle v1 | JAR / Bundle v1 |

---

## 2. Checksum Directory & Integrity Records

### 2.1 SHA-256 Digests
```text
# Production Release Artifacts
790034aa0aa140501662500f2bb42dafe73e31e19d1f431e20c5145a3655d9ae  android/app/build/outputs/apk/release/app-release.apk
13709fe9c669fc0dc68f9b2a6164a029c2840280a6925de8c1227dab3af4322e  android/app/build/outputs/bundle/release/app-release.aab

# Staging Artifacts
06430f6be362fe12f5401b5d4246b002fe35a5bb68883d5093e7e55ebdb66f60  android/app/build/outputs/apk/staging/app-staging.apk
167173ad998090bd147f361ab18b5a649375e8512179645ab0da80fd312ad172  android/app/build/outputs/bundle/staging/app-staging.aab
```

---

## 3. Signing Certificate Analysis

Both production and staging variants are signed using the verified enterprise keystore (`keystore/connectly-release.jks`):

```text
Keystore Type:             PKCS12
Owner Distinguished Name:  CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD
Issuer Distinguished Name: CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD
Certificate Serial:        a03b8bdc731fd79
Signature Algorithm:       SHA384withRSA
Public Key Algorithm:      2048-bit RSA key
Valid From:                Sun Nov 01 08:26:58 GMT+06:00 2026
Valid To:                  Thu Mar 19 08:26:58 GMT+06:00 2054 (28 years validity)

Certificate Fingerprints:
  SHA-256: 3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41
  SHA-1:   D3:49:7E:B4:62:CA:34:96:1C:9F:2E:96:C6:E2:91:1C:E9:E8:34:5C
  MD5:     26:B1:66:A1:1B:37:C7:7B:D5:77:6E:C2:33:3C:5E:C4
```

---

## 4. `apksigner` Verification Telemetry

### 4.1 Production Release APK Output:
```text
$ apksigner verify --verbose --print-certs app-release.apk
Verifies: true
Verified using v1 scheme (JAR signing): false
Verified using v2 scheme (APK Signature Scheme v2): true
Verified using v3 scheme (APK Signature Scheme v3): false
Verified using v4 scheme (APK Signature Scheme v4): false
Number of signers: 1
Signer #1 certificate DN: CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD
Signer #1 certificate SHA-256: 3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741
```

### 4.2 Staging APK Output:
```text
$ apksigner verify --verbose --print-certs app-staging.apk
Verifies: true
Verified using v1 scheme (JAR signing): false
Verified using v2 scheme (APK Signature Scheme v2): true
Verified using v3 scheme (APK Signature Scheme v3): false
Verified using v4 scheme (APK Signature Scheme v4): false
Number of signers: 1
Signer #1 certificate DN: CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD
Signer #1 certificate SHA-256: 3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741
```

---

## 5. Security & Static Analysis Findings

1. **Embedded Secrets Audit:** Decompilation string analysis confirmed zero raw AWS keys, Firebase private service accounts, or TURN shared secrets reside within the binary packages.
2. **Signature Scheme Compatibility:** APK Signature Scheme v2 satisfies Google Play's security mandates, protecting the APK from zip-metadata modifications post-signing.
3. **Debuggable Flag:** Checked via AAPT dump. Both `app-release.apk` and `app-staging.apk` have `android:debuggable="false"`.

**Cryptographic Audit Gate:** `PASS`

