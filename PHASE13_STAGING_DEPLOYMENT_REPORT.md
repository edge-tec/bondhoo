# CONNECTLY PHASE 13 — CONTROLLED STAGING DEPLOYMENT REPORT

**System:** Connectly Native Android & JUGAJUG Laravel 13 Platform  
**Target Environment:** Isolated Controlled Staging  
**Date:** November 2026  
**Status:** `CONTROLLED STAGING DEPLOYED — LIVE HARDWARE VALIDATION BLOCKED`  

---

## 1. Executive Summary

Phase 13 establishes the controlled staging deployment of Connectly Native Android (`com.connectly.android.staging`) and the JUGAJUG Laravel 13 backend. This deployment isolates staging configurations, verifies cryptographic signatures of release and staging artifacts, confirms R8/ProGuard obfuscation, and audits all four enterprise real-time engines.

In strict compliance with safety directives, **no simulated or fabricated hardware test results are accepted**. While automated test suites and build pipelines achieve 100% PASS rates, live physical testing across two real devices, cellular CGNAT relaying, and cloud Coturn servers are documented with their exact operational statuses (`PASS`, `NOT RUN`, `NOT VERIFIED`, or `BLOCKED`).

---

## 2. Staging Infrastructure & Deployment Matrix

| Subsystem | Staging Target | Verification Method | Status |
| :--- | :--- | :--- | :---: |
| **Backend API & App Runtime** | Laravel 13 / PHP 8.5.0 | Feature Tests (47 tests, 286 assertions) | `PASS` |
| **Staging Database** | MySQL (Isolated schema) | Migration & Seeder isolation | `PASS` |
| **WebSocket Engine** | Laravel Reverb (Redis backplane) | Channel authorization closure tests | `PASS` |
| **TURN Credential Engine** | RFC 5766 REST API (`/api/v1/calls/ice-servers`) | Unit test dynamic HMAC-SHA1 generation | `PASS` |
| **Android Staging APK** | `com.connectly.android.staging` (59 MB) | Build & APK Signature Scheme v2 verify | `PASS` |
| **Android Staging AAB** | `com.connectly.android.staging` (37 MB) | Bundletool & Gradle package verify | `PASS` |
| **Live Coturn Server Host** | Dedicated Cloud VM with static IP | Cloud VM unprovisioned (approval required) | `BLOCKED` |
| **Two-Phone Physical Audio/Video** | Physical Android fleet in acoustic isolation | Requires physical devices in test lab | `NOT RUN` |
| **Live Cellular Carrier CGNAT** | Mobile 4G/5G SIM media relaying | Requires physical devices & live Coturn | `NOT RUN` |
| **Production Firebase Cloud Delivery** | FCM v1 push to sleeping OS | Staging FCM key configuration pending | `NOT VERIFIED` |

---

## 3. Pre-Deployment Repository & Artifact Verification

Both the production release artifacts (from Phase 12) and the newly generated staging artifacts (Phase 13) were cryptographically verified using `shasum` and Android SDK `apksigner`:

| Artifact | Variant | Application ID | Version | SHA-256 Checksum | Signature | Status |
| :--- | :---: | :--- | :---: | :--- | :---: | :---: |
| `app-release.apk` | Release | `com.connectly.android` | `1.0.0 (1)` | `790034aa0aa140501662500f2bb42dafe73e31e19d1f431e20c5145a3655d9ae` | v2 Signed | `PASS` |
| `app-release.aab` | Release | `com.connectly.android` | `1.0.0 (1)` | `13709fe9c669fc0dc68f9b2a6164a029c2840280a6925de8c1227dab3af4322e` | v2 Signed | `PASS` |
| `app-staging.apk` | Staging | `com.connectly.android.staging` | `1.0.0 (1)` | `06430f6be362fe12f5401b5d4246b002fe35a5bb68883d5093e7e55ebdb66f60` | v2 Signed | `PASS` |
| `app-staging.aab` | Staging | `com.connectly.android.staging` | `1.0.0 (1)` | `167173ad998090bd147f361ab18b5a649375e8512179645ab0da80fd312ad172` | v2 Signed | `PASS` |

### Signing Certificate Details:
- **Distinguished Name:** `CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD`
- **Certificate SHA-256:** `3D:34:5D:67:03:AB:E6:F3:46:7B:6E:60:D3:54:90:2A:39:44:F0:1F:22:71:93:4C:A3:82:7B:52:54:19:C7:41`
- **Algorithm:** 2048-bit RSA key with `SHA384withRSA`
- **Validity:** Valid through year 2054.

---

## 4. Staging Isolation Controls

1. **Zero Production Contamination:**
   - The staging Android build utilizes a distinct Application ID suffix (`.staging`), allowing it to be installed side-by-side with production or debug builds without database or SharedPreference collisions.
   - The staging environment relies on segregated `.env.staging` database connections and Redis namespaces (`staging_database`, `staging_cache`).
2. **Secrets Protection:**
   - Keystore passwords, TURN shared secrets, and JWT private keys are referenced exclusively via server environment variables or isolated local keystore configurations.
   - Zero hardcoded production secrets exist in the source repository or decompiled APK assets.

---

## 5. Next Steps & Release Gate Status

The staging release artifacts are built and ready for distribution to internal QA testers. Live hardware validation is temporarily held pending cloud TURN host allocation.

**Staging Deployment Gate:** `APPROVED FOR INTERNAL TEST DISTRIBUTION`  
**General Production Gate:** `BLOCKED`

