# PHASE 20 — ANDROID RELEASE & SIGNING CERTIFICATION

**Project:** Bondhoo Android Application (`com.jugajug.android`)
**Version:** `versionName = "1.0.1"`, `versionCode = 2`
**Execution Timestamp:** 2026-11-20T20:42:56+06:00
**Local Signed Release APK & AAB Certification:** **PASS**
**Live `https://bondhoo.com/bondhoo.apk` Publication:** **BLOCKED (HTTP 404 Until Uploaded to HostVra VPS)**

---

## 1. Verified Release Artifacts & Cryptographic Signatures

| Artifact | Path | Size | SHA-256 Checksum | Signer Certificate | Status |
| :--- | :--- | :---: | :--- | :--- | :---: |
| **Signed Release APK** | `public/bondhoo.apk` (`android/app/build/outputs/apk/release/app-release.apk`) | `48 MB` | `51fc4673c2d3f6361315574b72714eea566057df2804985ff8ecbdd03d17204e` | `CN=Connectly Release` (`3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741`), Scheme v2 `true` | **PASS** |
| **Signed Release AAB** | `android/app/build/outputs/bundle/release/app-release.aab` | `34 MB` | `1c817afde6455a073b79d3ab84617d0477207a54b4f092ba76234a215031be9f` | `CN=Connectly Release` (Expires `2054-03-19`), `jarsigner` verified | **PASS** |
| **Live Public URL** | `HEAD https://bondhoo.com/bondhoo.apk` | — | — | Returned `HTTP 404` (`1.509s`) — awaiting SCP upload to `/var/www/bondhoo.com/public/bondhoo.apk` | **BLOCKED** |
