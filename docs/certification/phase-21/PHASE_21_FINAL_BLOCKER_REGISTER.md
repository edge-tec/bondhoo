# PHASE 21 — FINAL BLOCKER REGISTER

- **Date & Time:** 2026-11-20T21:12:36+06:00
- **Status:** **7 External Operator-Dependent Blockers Open (`0` Local Code/Security/Test Blockers)**

## 1. Mandatory Phase 20/21 Blocker Register
| # | Blocker | Root Cause | Status | Required Operator Action |
| :---: | :--- | :--- | :---: | :--- |
| 1 | HostVra live VPS deployment | Local workstation has no SSH route (`No route to host`) to `2a02:c207:2327:8704::1` | **BLOCKED** | Run `cd /var/www/bondhoo.com && git pull --ff-only origin main && bash deploy/production/cutover-hostvra.sh` in HostVra Web Terminal |
| 2 | Nginx Reverb proxy activation | `/etc/nginx/sites-available/bondhoo.com` has not yet included `nginx-reverb-websocket.conf` | **BLOCKED** | Add `include /var/www/bondhoo.com/deploy/production/nginx-reverb-websocket.conf;` in SSL block and run `nginx -t && systemctl reload nginx` |
| 3 | Live WSS connectivity (`wss://bondhoo.com/app`) | Depends on Blockers #1 and #2 (live Upgrade probe returns `HTTP 404`) | **BLOCKED** | Resolved immediately after Blockers #1 and #2 |
| 4 | Live Messenger E2E on production WSS | Depends on Blocker #3 | **BLOCKED** | Test 2 accounts on `https://bondhoo.com/messages` after Blocker #3 |
| 5 | Search API live `HTTP 401` | HostVra VPS still running pre-`4295874` commit | **BLOCKED** | Resolved immediately after Blocker #1 |
| 6 | Production APK upload (`https://bondhoo.com/bondhoo.apk`) | `public/bondhoo.apk` (`48 MB`, gitignored) has not yet been uploaded to HostVra | **BLOCKED** | Upload `public/bondhoo.apk` to `/var/www/bondhoo.com/public/bondhoo.apk` |
| 7 | Physical Android device testing | `adb devices -l` shows `0` connected physical Android phones | **NOT TESTED** | Connect Android phone via USB and run `adb install -r public/bondhoo.apk` |
