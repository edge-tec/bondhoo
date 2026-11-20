# PHASE 16 — ANDROID PRODUCTION RELEASE BUILD REPORT

**Project:** Bondhoo Android Application  
**Application ID:** `com.jugajug.android`  
**Version:** `versionName = "1.0.1"`, `versionCode = 2`  
**Timestamp:** 2026-11-20T19:15:00+06:00  
**Final Status:** **PASS**

---

## 1. Release Build Configuration & Artifact Summary

| Parameter | Configured / Verified Value | Status |
| :--- | :--- | :--- |
| `applicationId` | `"com.jugajug.android"` | **PASS** |
| `versionCode` / `versionName` | `2` / `"1.0.1"` | **PASS** |
| `minSdk` / `targetSdk` / `compileSdk` | `26` / `36` / `36` | **PASS** |
| `isMinifyEnabled` / `isShrinkResources` | `true` / `true` (R8 active) | **PASS** |
| Production API Base URL | `https://bondhoo.com/api/v1/` | **PASS** |
| Production WebSocket URL | `wss://bondhoo.com/app/connectly_reverb_key` | **PASS** |
| Signed Release APK | `android/app/build/outputs/apk/release/app-release.apk` (`48 MB`, SHA-256: `51fc4673c2d3f6361315574b72714eea566057df2804985ff8ecbdd03d17204e`) | **PASS** |
| Signed Release AAB | `android/app/build/outputs/bundle/release/app-release.aab` (`34 MB`, SHA-256: `1c817afde6455a073b79d3ab84617d0477207a54b4f092ba76234a215031be9f`) | **PASS** |
