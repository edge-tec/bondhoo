# PHASE 16 — APK PUBLICATION REPORT

**Project:** Bondhoo Social Platform  
**Public APK URL:** `https://bondhoo.com/bondhoo.apk`  
**Phase:** 16.10  
**Date:** 2026-11-20

---

## 1. Live Publication Status

| Check | Command | Observed Result | Status |
| :--- | :--- | :--- | :--- |
| **Production Signed APK Prerequisite** | Check for production-signed `app-release.apk` (non-debug cert) | Production `.jks` keystore not available locally; per Phase 16.10 rules, a debug-signed or unsigned APK must **never** be published to `https://bondhoo.com/bondhoo.apk`. | **BLOCKED (Requires Production-Signed APK)** |
| **Live URL Probe (`HEAD /bondhoo.apk`)** | `curl -I -sS https://bondhoo.com/bondhoo.apk` | `HTTP/2 404` | **BLOCKED** |

---

## 2. Publication Command Once Signed with Production `.jks`

Once `android/app/build/outputs/apk/release/app-release.apk` is signed with the production `.jks` certificate and verified via `apksigner verify --verbose --print-certs`:

```bash
scp android/app/build/outputs/apk/release/app-release.apk root@bondhoo.com:/var/www/bondhoo.com/public/bondhoo.apk
ssh root@bondhoo.com "chown www-data:www-data /var/www/bondhoo.com/public/bondhoo.apk && chmod 644 /var/www/bondhoo.com/public/bondhoo.apk"
curl -I https://bondhoo.com/bondhoo.apk
```
