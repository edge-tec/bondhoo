# BONDHOO PHASE 14.5.4 — UPLOAD KEY SECURITY VERIFICATION REPORT

**Date:** November 5, 2026  
**Auditor:** Android Release Signing Specialist & DevSecOps Lead  
**Scope:** Android Upload Key Storage, Permissions, Cryptographic Fingerprints & Fail-Closed Enforcement  
**Status:** **PASS (CRYPTOGRAPHICALLY VERIFIED)**

---

## 1. External Keystore & Certificate Verification

The dedicated Android Upload Key was verified in its secure location outside the repository:

- **Keystore Location:** `/Users/mizanurrahman/.android/keystores/bondhoo-upload.jks`
- **File Permissions:** `-rw-------` (600 - Owner read/write only)
- **Public Certificate:** `/Users/mizanurrahman/.android/keystores/bondhoo-upload-cert.pem`
- **Git Tracking Status:** Outside repository tree; not tracked in Git.

### Cryptographic Fingerprint Verification
```
Owner: CN=Bondhoo Upload Key, OU=Release Engineering, O=Bondhoo Social, L=Dhaka, ST=Dhaka, C=BD
Issuer: CN=Bondhoo Upload Key, OU=Release Engineering, O=Bondhoo Social, L=Dhaka, ST=Dhaka, C=BD
Serial number: b50c7e9940e4246c
Valid from: Thu Nov 05 16:12:30 GMT+06:00 2026 until: Mon Mar 23 16:12:30 GMT+06:00 2054
Algorithm: 2048-bit RSA key (SHA384withRSA)
SHA-1:   00:04:AE:42:CF:22:2D:CE:E2:19:59:C0:32:6C:D5:B5:B0:5E:D4:ED
SHA-256: 26:0B:64:E1:B4:76:1A:03:B6:EA:F4:A1:81:C7:17:79:72:3E:AD:CA:EC:89:14:34:34:1A:42:7C:32:D8:46:55
```
**Verdict:** 100% match with Phase 14.5.3 authorized generation.

---

## 2. Fail-Closed Release Signing Enforcement

The Gradle build configuration in `android/app/build.gradle.kts` enforces a strict Fail-Closed policy:
```kotlin
val storeFilePath = System.getenv("BONDHOO_UPLOAD_STORE_FILE")
val storePass = System.getenv("BONDHOO_UPLOAD_STORE_PASSWORD")
val kAlias = System.getenv("BONDHOO_UPLOAD_KEY_ALIAS")
val kPass = System.getenv("BONDHOO_UPLOAD_KEY_PASSWORD")

if (!storeFilePath.isNullOrBlank() && File(storeFilePath).exists() &&
    !storePass.isNullOrBlank() && !kAlias.isNullOrBlank() && !kPass.isNullOrBlank()) {
    storeFile = File(storeFilePath)
    ...
} else {
    if (isReleaseTask) {
        throw GradleException("BONDHOO Security Enforcement: Release builds require secure signing credentials...")
    }
}
```

### Controlled Fail-Closed Test Evidence
- **Command:** `env -i JAVA_HOME="..." ./gradlew :app:validateSigningRelease`
- **Result:** `BUILD FAILED in 615ms`
- **Output:** `BONDHOO Security Enforcement: Release builds require secure signing credentials.`
- **Verdict:** PASS. No fallback to debug keys or hardcoded passwords occurs.

