# BONDHOO PHASE 14.5.1 — SIGNATURE REVALIDATION REPORT
**Execution Timestamp:** 2026-11-05T14:36:16+06:00  
**Auditor / Engineer:** Principal Application Security Auditor & DevSecOps Lead  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Executive Summary

This report delivers the verified cryptographic execution proofs for both release artifacts using official Android Studio and Android SDK platform tools.

---

## 2. Android App Bundle (AAB) Jarsigner Execution Proof

- **Command:** `"/Applications/Android Studio.app/Contents/jbr/Contents/Home/bin/jarsigner" -verify -verbose -certs android/app/build/outputs/bundle/release/app-release.aab`
- **Execution Exit Code:** **`0` (Successful)**
- **Output Verdict:** `jar verified.`
- **Signer Identity Details:**
  - **Certificate Type:** `X.509`
  - **Distinguished Name (DN):** `CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD`
  - **Signature Algorithm:** `SHA384withRSA`
  - **Key Specification:** `2048-bit RSA key`
  - **Certificate Validity Window:** `Sun Nov 01 08:26:58 GMT+06:00 2026` until `Thu Mar 19 08:26:58 GMT+06:00 2054` (Valid for 28 years)
- **Upload Key Match:** **CONFIRMED (100% Match with `connectly-release.jks` alias `connectly`)**

---

## 3. Standalone APK Apksigner Execution Proof

- **Command:** `/Users/mizanurrahman/Library/Android/sdk/build-tools/36.0.0/apksigner verify --verbose --print-certs android/app/build/outputs/apk/release/app-release.apk`
- **Execution Exit Code:** **`0` (Successful)**
- **Verifies:** `true`
- **Supported Signature Schemes Breakdown:**
  - `Scheme v1 (JAR signing): false`
  - `Scheme v2 (APK Signature Scheme v2): true`
  - `Scheme v3 (APK Signature Scheme v3): false`
  - `Scheme v3.1: false`
  - `Scheme v4: false`
- **Signer #1 Certificate DN:** `CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD`
- **Signer #1 Certificate Digest (SHA-1):** `d3497eb462ca34961c9f2e96c6e2911ce9e8345c`
- **Signer #1 Certificate Digest (SHA-256):** `3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741`
- **Key Algorithm & Size:** `RSA, 2048 bits`

---

## 4. Package & Version Match Against Baseline

Inspection via `aapt2 dump badging`:
- **Package Name:** `com.jugajug.android` (Matches baseline)
- **Version Code:** `2` (Matches baseline)
- **Version Name:** `1.0.1` (Matches baseline)
- **Compile SDK:** `36` (Android 16)
- **Target SDK:** `36` (Android 16)
- **Min SDK:** `26` (Android 8.0)
- **Application Label:** `BONDHOO`

---

## 5. Signature Revalidation Determination

**Status:** **PASS (ALL SIGNATURES AND CERTIFICATES CRYPTOGRAPHICALLY VALIDATED)**.

