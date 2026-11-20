# PHASE 15.11 — Live VPS Deployment Report

- **Production Domain:** `https://bondhoo.com`
- **Deployment Status:** **BLOCKED (SSH Access Not Configured in Local Environment)**
- **Evidence:** `~/.ssh/config` contains only `Host github.com`; previous SSH attempts to `hostvra` / `bondhoo.com` port 22 timed out or had no configured SSH key in this workspace. Per Phase 15 Rule 11 & 14 (*"Never fabricate production server access. Never mark a deployment successful if the deployed commit is unverified"*), live VPS deployment is marked **BLOCKED**.

## Verified Commands for Operator Execution on HostVra VPS (`/var/www/bondhoo.com`)
```bash
cd /var/www/bondhoo.com
mkdir -p /backups
mysqldump --single-transaction -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" | gzip > /backups/bondhoo_pre_phase15_$(date +%Y%m%d_%H%M%S).sql.gz
git rev-parse HEAD > /backups/bondhoo_pre_phase15_commit.txt

git fetch origin
git checkout main
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan reverb:restart
```
