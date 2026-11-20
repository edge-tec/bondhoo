# PHASE 19 — ANDROID RELEASE CERTIFICATION

**Project:** Bondhoo Android Application (`com.jugajug.android`)  
**Version:** `versionName = "1.0.1"`, `versionCode = 2`  
**Execution Timestamp:** 2026-11-20T20:35:53+06:00  
**Final Status:** **PASS**

---

## 1. Verified Signed Release Artifacts

| Artifact | Path | SHA-256 Checksum | Signer Certificate | Status |
| :--- | :--- | :--- | :--- | :---: |
| **Signed Release APK** | `public/bondhoo.apk` (`48 MB`) | `51fc4673c2d3f6361315574b72714eea566057df2804985ff8ecbdd03d17204e` | `CN=Connectly Release` (`3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741`), Scheme v2 `true` | **PASS** |
| **Signed Release AAB** | `android/app/build/outputs/bundle/release/app-release.aab` (`34 MB`) | `1c817afde6455a073b79d3ab84617d0477207a54b4f092ba76234a215031be9f` | `CN=Connectly Release` (Expires `2054-03-19`), `jarsigner` verified | **PASS** |

- **Production Endpoints:** `https://bondhoo.com/api/v1/` and `wss://bondhoo.com/app/connectly_reverb_key`.
- **Network Security Config:** Cleartext HTTP disabled for `bondhoo.com`; system CAs only.
- **Signing Hygiene:** `connectly-release.jks` and `android/keystore.properties` excluded from Git; `debug.keystore` fallback removed.
