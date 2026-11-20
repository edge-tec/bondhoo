# PHASE 21 — ANDROID APK & AAB CERTIFICATION REPORT

- **Date & Time:** 2026-11-20T21:12:36+06:00
- **Application ID:** `com.jugajug.android` (`versionName = "1.0.1"`, `versionCode = 2`)
- **Local Signed Artifacts:** **PASS**
- **Live `https://bondhoo.com/bondhoo.apk` Download:** **BLOCKED (`HTTP 404`, `0.833s`)**

## 1. Verified Artifact Checksums & Signatures
- **APK (`public/bondhoo.apk`, `48 MB`):** `51fc4673c2d3f6361315574b72714eea566057df2804985ff8ecbdd03d17204e`
- **AAB (`android/app/build/outputs/bundle/release/app-release.aab`, `34 MB`):** `1c817afde6455a073b79d3ab84617d0477207a54b4f092ba76234a215031be9f`
- **Signer Certificate:** `CN=Connectly Release, OU=Mobile, O=Connectly Inc, L=Dhaka, ST=Dhaka, C=BD` (`SHA-256: 3d345d6703abe6f3467b6e60d354902a3944f01f2271934ca3827b525419c741`, Scheme v2 `true`, Expires `2054-03-19`).

## 2. Responsible Operator Action
Upload `public/bondhoo.apk` to `/var/www/bondhoo.com/public/bondhoo.apk` on HostVra VPS (`chmod 644`, `chown www-data:www-data`).
