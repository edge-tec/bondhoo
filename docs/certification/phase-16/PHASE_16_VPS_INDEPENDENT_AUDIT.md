# PHASE 16 — INDEPENDENT PRODUCTION VPS AUDIT

**Project:** Bondhoo Social Platform  
**Target Domain:** `https://bondhoo.com` (`2a02:c207:2327:8704::1`)  
**Phase:** 16.3  
**Date:** 2026-11-20

---

## 1. Live External Read-Only Probe Evidence

| Probe Target | Command | HTTP / Network Response | Finding |
| :--- | :--- | :--- | :--- |
| **Landing Page (`GET /`)** | `curl -I -sS https://bondhoo.com` | `HTTP/2 200` (`content-type: text/html; charset=UTF-8`, `server: cloudflare`, `x-frame-options: SAMEORIGIN`, `x-content-type-options: nosniff`) | **PASS** — Nginx + PHP-FPM + Cloudflare SSL active and serving requests. |
| **Public Search API (`GET /api/v1/search?q=bondhoo`)** | `curl -i -sS "https://bondhoo.com/api/v1/search?q=bondhoo"` | `HTTP/2 401` (`{"message":"Unauthenticated."}`) | **DRIFT CONFIRMED** — Live VPS is running an older commit prior to `4295874` / `ebb853c` where `/api/v1/search` was shadowed by an auth-only route. Locally, `GET /api/v1/search?q=bondhoo` returns `HTTP 200`. |
| **WebSocket Endpoint (`GET /app/connectly_reverb_key`)** | `curl -i -sS "https://bondhoo.com/app/connectly_reverb_key?protocol=7&client=web&version=1.0.0"` | `HTTP/2 404` | **NOT ACTIVE** — Nginx `/app` proxy block and/or `bondhoo-reverb` Supervisor daemon have not yet been activated on the HostVra VPS. |
| **Public APK Download (`HEAD /bondhoo.apk`)** | `curl -I -sS https://bondhoo.com/bondhoo.apk` | `HTTP/2 404` | **NOT PUBLISHED** — Signed production APK has not yet been uploaded to `/var/www/bondhoo.com/public/bondhoo.apk`. |
| **Direct SSH from Local Workstation** | `ssh -o BatchMode=yes -o ConnectTimeout=5 root@2a02:c207:2327:8704::1` | `ssh: connect to host 2a02:c207:2327:8704::1 port 22: No route to host` | **BLOCKED** — Direct SSH from local Mac mini is unreachable; operator must run [`deploy/production/cutover-hostvra.sh`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/deploy/production/cutover-hostvra.sh) inside the HostVra web terminal. |

---

## 2. HostVra Server Inspection Checklist (In-Script Pre-Flight)

When the operator runs `bash deploy/production/cutover-hostvra.sh` on `root@hostvra:/var/www/bondhoo.com#`, Step 1 automatically audits and prints:
- Hostname, OS (`uname -a`), uptime, CPU/memory (`free -h`), disk space (`df -h /var/www/bondhoo.com`)
- Currently deployed Git commit (`git rev-parse --short HEAD`)
- PHP, Composer, Nginx, MySQL, Redis, and Supervisor versions and service states
