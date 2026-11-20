# PHASE 16 — OPERATOR PREREQUISITES & ACCESS VERIFICATION

**Project:** Bondhoo Social Platform  
**Phase:** 16.2  
**Date:** 2026-11-20

---

## 1. Prerequisite Verification Matrix

| Prerequisite | Verification Command / Method | Observed Result | Status |
| :--- | :--- | :--- | :--- |
| **1. Production VPS SSH Access** | `ssh-add -l`; `~/.ssh/config`; `ssh -o BatchMode=yes -o ConnectTimeout=5 root@2a02:c207:2327:8704::1` | `The agent has no identities.`; `~/.ssh/config` only configures `github.com`; IPv6 connection from local Mac returns `No route to host`. Operator accesses `root@hostvra:/var/www/bondhoo.com#` via HostVra Web Console. | **BLOCKED (Requires Operator Execution in HostVra Console)** |
| **2. Production `.env` Access** | Remote `/var/www/bondhoo.com/.env` | Resides on HostVra VPS (`/var/www/bondhoo.com/.env`), inaccessible from local machine without SSH tunnel. | **BLOCKED (Remote VPS Only)** |
| **3. Production Android Keystore** | Check `android/keystore.properties`, `BONDHOO_UPLOAD_*` env vars, and `find . -name "*.jks" -o -name "*.keystore"` | `MISSING android/keystore.properties`; no `BONDHOO_UPLOAD_*` environment variables set; `0` `.jks` or `.keystore` files in workspace. | **BLOCKED (Missing Original Play Store `.jks` Keystore)** |
| **4. Production Domain & HTTPS Access** | `curl -I -sS https://bondhoo.com` | `HTTP/2 200` via Cloudflare edge (`server: cloudflare`), valid SSL/TLS certificate. | **PASS** |

---

## 2. Automated Cutover Tooling Prepared in Repository

Because direct SSH from this local workstation to `2a02:c207:2327:8704::1` is blocked by network routing (`No route to host`) and the operator manages the VPS directly via the HostVra web terminal (`root@hostvra:/var/www/bondhoo.com#`), all production cutover scripts and process configs have been committed to `deploy/production/`:

1. [`deploy/production/cutover-hostvra.sh`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/deploy/production/cutover-hostvra.sh) — Automated 8-step production cutover script (pre-flight check, timestamped MySQL backup + `gzip -t` integrity check, `.env` backup, `git pull --ff-only origin main`, `composer install --no-dev`, `php artisan migrate --force`, cache rebuild, and Supervisor Reverb + Queue Worker installation).
2. [`deploy/production/supervisor-bondhoo-reverb.conf`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/deploy/production/supervisor-bondhoo-reverb.conf) — Supervisor daemon definition for `php artisan reverb:start --host=127.0.0.1 --port=8080`.
3. [`deploy/production/supervisor-bondhoo-worker.conf`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/deploy/production/supervisor-bondhoo-worker.conf) — Supervisor daemon definition for `php artisan queue:work --queue=high,default,notifications,media`.
4. [`deploy/production/nginx-reverb-websocket.conf`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/deploy/production/nginx-reverb-websocket.conf) — Nginx `/app` and `/apps` WebSocket Upgrade proxy configuration for `bondhoo.com`.
