# BONDHOO PHASE 14.5.3 — AAB REBUILD & ARTIFACT INVENTORY REPORT
**Execution Timestamp:** 2026-11-05T16:16:13+06:00  
**Auditor / Engineer:** Principal Android Release Engineer & Quality Assurance Lead  
**Product:** BONDHOO Professional Social Networking Platform  
**Target Package:** `com.jugajug.android`

---

## 1. Executive Summary

Under **Option B**, a fresh release compilation was executed using the newly created, uncommitted Upload Key (`bondhoo-upload.jks`). 

In strict adherence to Safety Rule #5 and #12, the historical release artifacts from Phase 14.5.1 were safely archived prior to compilation and remain preserved on disk.

---

## 2. Release Artifact Inventory & Cryptographic Measurement

### A. New Release Artifacts (Signed with New Upload Key `bondhoo-upload`):
| Artifact Name | File Path | Exact Byte Size | SHA-256 Checksum | Signer Certificate |
|---|---|---|---|---|
| **Fresh Release AAB** | `android/app/build/outputs/bundle/release/app-release.aab` | **32,842,997 bytes** (~32.8 MB) | `98e6b2f17728c09653784e1e9ccb3009e3e814396e1de51e6e8764d02477bf97` | `CN=Bondhoo Upload Key` (SHA-256: `26:0B:64:E1...`) |
| **Fresh Release APK** | `android/app/build/outputs/apk/release/app-release.apk` | **49,697,066 bytes** (~49.7 MB) | `8f1eeb02384183de476174386eec895dd60b725ca3be5cad63c4e74f4664833c` | `CN=Bondhoo Upload Key` (SHA-256: `26:0B:64:E1...`) |

### B. Archived Historical Artifacts (Preserved from Phase 14.5.1):
| Artifact Name | Archive Path | Exact Byte Size | SHA-256 Checksum | Signer Certificate |
|---|---|---|---|---|
| **Archived AAB (14.5.1)** | `android/app/build/outputs/archive_phase14_5_1/app-release-phase14-5-1.aab` | **32,842,975 bytes** | `2fa62aa65ffe5aabe61363699423fbf4e8253264e7c785aaccd1092f5b3ba794` | `CN=Connectly Release` (SHA-256: `3D:34:5D:67...`) |
| **Archived APK (14.5.1)** | `android/app/build/outputs/archive_phase14_5_1/app-release-phase14-5-1.apk` | **49,697,066 bytes** | `a6e2b91ad3bd5b7c6bfd6a4deb280bbe2ee56ba23d5ec115abe395592b85d354` | `CN=Connectly Release` (SHA-256: `3D:34:5D:67...`) |

---

## 3. Gradle Compilation Execution Evidence

- **Executed Command:** `./gradlew :app:bundleRelease :app:assembleRelease`
- **Execution Exit Code:** `0` (Success)
- **Actionable Tasks:** 566 actionable tasks (all completed successfully)
- **Active Build Features:** ProGuard/R8 minification enabled, resource shrinking enabled, ART baseline profiles packaged.
- **Package & Version Metadata:**
  - `packageName`: `com.jugajug.android`
  - `versionCode`: `2`
  - `versionName`: `1.0.1`
  - `compileSdk`: `36`
  - `targetSdk`: `36`
  - `minSdk`: `26`

**Stage 05 Determination:** **PASS (NEW AAB & APK COMPILED, SIGNED & HISTORICAL ARTIFACTS ARCHIVED)**.

