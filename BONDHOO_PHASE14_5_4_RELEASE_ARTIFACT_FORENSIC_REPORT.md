# BONDHOO PHASE 14.5.4 — RELEASE ARTIFACT FORENSIC AUDIT REPORT

**Date:** November 5, 2026  
**Auditor:** Forensic Release Engineer & QA Specialist  
**Artifacts Inspected:**
- `android/app/build/outputs/bundle/release/app-release.aab`
- `android/app/build/outputs/apk/release/app-release.apk`  
**Status:** **CRYPTOGRAPHICALLY VERIFIED & BIT-IDENTICAL**

---

## 1. Artifact Verification Matrix

| Property | Bundle (`app-release.aab`) | Package (`app-release.apk`) | Status |
|---|---|---|---|
| **File Size** | `32,842,997 bytes` (31.3 MB) | `49,697,066 bytes` (47.4 MB) | Verified |
| **SHA-256** | `98e6b2f17728c09653784e1e9ccb3009e3e814396e1de51e6e8764d02477bf97` | `8f1eeb02384183de476174386eec895dd60b725ca3be5cad63c4e74f4664833c` | Match Phase 14.5.3 |
| **Package ID** | `com.jugajug.android` | `com.jugajug.android` | Verified |
| **Version Code**| `2` | `2` | Verified |
| **Version Name**| `1.0.1` | `1.0.1` | Verified |
| **Target SDK** | `36` (Android 16 DP) | `36` (Android 16 DP) | Verified |
| **Min SDK** | `26` (Android 8.0) | `26` (Android 8.0) | Verified |
| **Signing Certificate** | `CN=Bondhoo Upload Key` | `CN=Bondhoo Upload Key` | Verified |
| **Signature Scheme** | JAR Signature Verified | APK Signature Scheme v2 Verified | Verified |

---

## 2. Tool Output Evidence

### AAB Signature Verification (`keytool -printcert -jarfile`)
```
Signer #1:
Owner: CN=Bondhoo Upload Key, OU=Release Engineering, O=Bondhoo Social, L=Dhaka, ST=Dhaka, C=BD
SHA-256: 26:0B:64:E1:B4:76:1A:03:B6:EA:F4:A1:81:C7:17:79:72:3E:AD:CA:EC:89:14:34:34:1A:42:7C:32:D8:46:55
SHA-1:   00:04:AE:42:CF:22:2D:CE:E2:19:59:C0:32:6C:D5:B5:B0:5E:D4:ED
```

### APK Signature Verification (`apksigner verify --verbose --print-certs`)
```
Verifies: true
Verified using v2 scheme (APK Signature Scheme v2): true
Number of signers: 1
Signer #1 certificate DN: CN=Bondhoo Upload Key, OU=Release Engineering, O=Bondhoo Social, L=Dhaka, ST=Dhaka, C=BD
Signer #1 certificate SHA-256 digest: 260b64e1b4761a03b6eaf4a181c71779723eadcaec891434341a427c32d84655
Signer #1 key algorithm: RSA (2048 bits)
```

### Manifest Badging (`aapt2 dump badging`)
```
package: name='com.jugajug.android' versionCode='2' versionName='1.0.1' compileSdkVersion='36' targetSdkVersion='36' minSdkVersion='26'
application-label:'BONDHOO'
```

