# PHASE 16 — FINAL VPS CUTOVER REPORT

**Project:** Bondhoo Social Platform  
**Target Server:** HostVra VPS (`/var/www/bondhoo.com`, `2a02:c207:2327:8704::1`)  
**Timestamp:** 2026-11-20T19:15:00+06:00  
**Final Status:** **BLOCKED (Awaiting Operator Execution in HostVra Web Terminal)**

---

## 1. Local SSH Connectivity & Live Probe Evidence

**Commands Executed:**
```bash
ssh-add -l
curl -I -sS https://bondhoo.com
curl -i -sS "https://bondhoo.com/api/v1/search?q=bondhoo"
curl -i -sS "https://bondhoo.com/app/connectly_reverb_key?protocol=7&client=web&version=1.0.0"
```

**Observed Output:**
- `ssh-add -l` -> `The agent has no identities.`
- `ssh -o BatchMode=yes -o ConnectTimeout=5 root@2a02:c207:2327:8704::1` -> `No route to host`
- `GET https://bondhoo.com` -> `HTTP/2 200` (Cloudflare HTTPS active)
- `GET https://bondhoo.com/api/v1/search?q=bondhoo` -> `HTTP/2 401` (`{"message":"Unauthenticated."}` — confirms live VPS has not yet pulled commit `4295874`..`HEAD`)
- `GET https://bondhoo.com/app/connectly_reverb_key?protocol=7&client=web&version=1.0.0` -> `HTTP/2 404`

---

## 2. Hardened Cutover Script Ready in Repository (`deploy/production/cutover-hostvra.sh`)

We inspected and hardened [`deploy/production/cutover-hostvra.sh`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/deploy/production/cutover-hostvra.sh) to satisfy every Phase 16.3 & 16.4 requirement:
1. Creates `/var/backups/bondhoo/phase16_final_<TIMESTAMP>/`.
2. Backs up `PREVIOUS_COMMIT.txt`, `.env.backup` (`chmod 600`), `/etc/nginx/sites-enabled`, and `/etc/supervisor/conf.d`.
3. Runs `mysqldump --single-transaction --routines --triggers | gzip -9`, verifies non-zero size (`test -s`), verifies archive integrity (`gzip -t`), and generates a `.sha256` checksum before touching code or schema.
4. Executes `git fetch origin && git checkout main && git pull --ff-only origin main`.
5. Executes `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`.
6. Runs `php artisan migrate --force` (additive Enterprise Messenger tables).
7. Rebuilds Laravel caches (`optimize:clear`, `optimize`, `storage:link`) and restarts queue workers and Reverb via Supervisor (`bondhoo-reverb` & `bondhoo-worker:*`).

### Exact Operator Command for HostVra Web Console (`root@hostvra:/var/www/bondhoo.com#`):
```bash
cd /var/www/bondhoo.com && git pull origin main && bash deploy/production/cutover-hostvra.sh
```
