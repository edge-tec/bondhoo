# PHASE 16 — APK PUBLICATION REPORT

**Project:** Bondhoo Social Platform  
**Public APK URL:** `https://bondhoo.com/bondhoo.apk`  
**Timestamp:** 2026-11-20T19:15:00+06:00  
**Final Status:** **CONDITIONAL PASS (Staged Locally in `public/bondhoo.apk`; Remote VPS Upload Pending SSH Access)**

---

## 1. Local & Remote Publication Status

| Check | Command | Observed Result | Status |
| :--- | :--- | :--- | :--- |
| **1. Production-Signed APK Staged Locally** | `cp -f android/app/build/outputs/apk/release/app-release.apk public/bondhoo.apk && shasum -a 256 public/bondhoo.apk` | `51fc4673c2d3f6361315574b72714eea566057df2804985ff8ecbdd03d17204e  public/bondhoo.apk` (`48 MB`, signed with `CN=Connectly Release`) | **PASS** |
| **2. Live VPS URL Probe (`HEAD https://bondhoo.com/bondhoo.apk`)** | `curl -I -sS https://bondhoo.com/bondhoo.apk` | `HTTP/2 404` (Because `/public/bondhoo.apk` is excluded in `.gitignore` to avoid bloating Git history with 48 MB binary blobs, and direct SSH/SCP to HostVra VPS is unreachable from this local network) | **BLOCKED (Requires Operator SCP / Upload to HostVra VPS)** |

---

## 2. Operator Command to Publish `public/bondhoo.apk` to HostVra VPS

From a network/machine with SSH access to `bondhoo.com` (or via HostVra file upload):

```bash
scp public/bondhoo.apk root@bondhoo.com:/var/www/bondhoo.com/public/bondhoo.apk
ssh root@bondhoo.com "chown www-data:www-data /var/www/bondhoo.com/public/bondhoo.apk && chmod 644 /var/www/bondhoo.com/public/bondhoo.apk"
curl -I https://bondhoo.com/bondhoo.apk
```
