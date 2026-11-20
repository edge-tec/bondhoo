# PHASE 17 — LIVE VPS DEPLOYMENT CERTIFICATION

**Project:** Bondhoo Social Platform  
**Target Production Server:** HostVra VPS (`/var/www/bondhoo.com`, `2a02:c207:2327:8704::1`)  
**Production Domain:** `https://bondhoo.com`  
**Timestamp:** 2026-11-20T20:17:00+06:00  
**Final Status:** **BLOCKED (Awaiting Operator Execution of `cutover-hostvra.sh` in HostVra Web Terminal)**

---

## 1. Independent Live Production Probe Evidence

**Commands Executed at `2026-11-20T20:17:04+06:00`:**
```bash
curl -I -sS https://bondhoo.com
curl -i -sS "https://bondhoo.com/api/v1/search?q=bondhoo"
curl -i -sS "https://bondhoo.com/app/connectly_reverb_key?protocol=7&client=web&version=1.0.0"
curl -I -sS https://bondhoo.com/bondhoo.apk
```

| Live Endpoint | Observed HTTP Response | Technical Interpretation | Status |
| :--- | :--- | :--- | :---: |
| `GET https://bondhoo.com` | `HTTP/2 200` (`server: cloudflare`, `x-content-type-options: nosniff`, `x-correlation-id: 112940f6...`) | Nginx, PHP-FPM, and Cloudflare SSL are online and serving requests. | **PASS** |
| `GET https://bondhoo.com/api/v1/search?q=bondhoo` | `HTTP/2 401` (`{"message":"Unauthenticated."}`) | Confirms the live HostVra VPS has **not yet pulled** `origin/main` (`4295874`..`4aa8f72`), where the duplicate auth-only `/api/v1/search` route collision was fixed. | **BLOCKED (Pending VPS `git pull`)** |
| `GET https://bondhoo.com/app/connectly_reverb_key` | `HTTP/2 404` | Confirms the Nginx `/app` WebSocket proxy and `bondhoo-reverb` Supervisor daemon have not yet been activated on the live VPS. | **BLOCKED (Pending VPS Cutover)** |
| `HEAD https://bondhoo.com/bondhoo.apk` | `HTTP/2 404` | Confirms the locally staged `public/bondhoo.apk` (`48 MB`, gitignored) has not yet been uploaded to `/var/www/bondhoo.com/public/bondhoo.apk`. | **BLOCKED (Pending SCP/Upload)** |
| Direct SSH (`root@2a02:c207:2327:8704::1`) | `No route to host` (`ssh-add -l` has no identities) | Local development workstation cannot SSH directly into the HostVra IPv6 VPS; operator accesses `root@hostvra:/var/www/bondhoo.com#` via the HostVra Web Terminal. | **BLOCKED** |

---

## 2. Exact HostVra Web Terminal Cutover Procedure

Inside your HostVra Web Terminal (`root@hostvra:/var/www/bondhoo.com#`), run:

```bash
cd /var/www/bondhoo.com
git pull origin main
bash deploy/production/cutover-hostvra.sh
```

### What `deploy/production/cutover-hostvra.sh` Executes Automatically:
1. **Pre-Flight Check:** Verifies `/var/www/bondhoo.com/artisan` and creates `/var/backups/bondhoo/phase16_final_<TIMESTAMP>/`.
2. **Config & Code State Snapshot:** Saves `PREVIOUS_COMMIT.txt`, `.env.backup` (`chmod 600`), `/etc/nginx/sites-enabled`, and `/etc/supervisor/conf.d`.
3. **Consistent MySQL Backup & Checksum:** Runs `mysqldump --single-transaction --routines --triggers | gzip -9`, verifies non-zero size (`test -s`), verifies gzip integrity (`gzip -t`), and writes `.sha256` checksum.
4. **Fast-Forward Code Update:** `git fetch origin && git checkout main && git pull --ff-only origin main`.
5. **Composer Dependencies:** `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`.
6. **Non-Destructive Database Migrations:** `php artisan migrate:status && php artisan migrate --force`.
7. **Cache Rebuild & Service Restart:** `php artisan optimize:clear && php artisan optimize && php artisan storage:link && php artisan queue:restart && php artisan reverb:restart`.
8. **Supervisor Daemon Installation:** Installs `bondhoo-reverb.conf` and `bondhoo-worker.conf` into `/etc/supervisor/conf.d/` and restarts `bondhoo-reverb` and `bondhoo-worker:*`.
