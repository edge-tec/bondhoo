# PHASE 18 — HOSTVRA LIVE VPS CUTOVER REPORT

**Project:** Bondhoo Social Platform  
**Production Domain:** `https://bondhoo.com`  
**Target VPS:** HostVra (`/var/www/bondhoo.com`, `2a02:c207:2327:8704::1`)  
**Timestamp:** 2026-11-20T20:25:16+06:00  
**Final Status:** **BLOCKED (No Local SSH Route to HostVra VPS; Requires Operator Execution in HostVra Web Terminal)**

---

## 1. Live Production HTTP Probes (`2026-11-20T20:25:13–16+06:00`)

| Endpoint | Observed Live Response | Interpretation | Status |
| :--- | :--- | :--- | :---: |
| `GET https://bondhoo.com` | `HTTP/2 200` (`server: cloudflare`, `x-content-type-options: nosniff`) | Live Nginx + PHP-FPM + Cloudflare SSL active. | **PASS** |
| `GET https://bondhoo.com/api/v1/search?q=bondhoo` | `HTTP/2 401` (`{"message":"Unauthenticated."}`) | Live VPS has **not yet pulled** `origin/main` (commits `4295874`..`cc19024`). | **BLOCKED** |
| `GET https://bondhoo.com/app/connectly_reverb_key?protocol=7&client=web&version=1.0.0` | `HTTP/2 404` | Nginx `/app` proxy & `bondhoo-reverb` not yet activated on live VPS. | **BLOCKED** |
| `HEAD https://bondhoo.com/bondhoo.apk` | `HTTP/2 404` | Signed `public/bondhoo.apk` not yet uploaded to `/var/www/bondhoo.com/public/bondhoo.apk`. | **BLOCKED** |

---

## 2. Exact HostVra Web Terminal Cutover Commands

Execute inside your HostVra Web Terminal (`root@hostvra:/var/www/bondhoo.com#`):

```bash
cd /var/www/bondhoo.com
git fetch origin
git pull --ff-only origin main
bash deploy/production/cutover-hostvra.sh
```

Then verify post-cutover health:

```bash
php artisan about --only=environment
php artisan migrate:status
supervisorctl status bondhoo-reverb bondhoo-worker:*
curl -i "https://bondhoo.com/api/v1/search?q=bondhoo"
```
