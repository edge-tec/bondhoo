# PHASE 12.9 — Production Server Deployment Report

- **Deployment Status:** **NOT EXECUTED**
- **Target Domain:** `https://bondhoo.com`
- **Target Commit:** `deff93a` (plus Phase 12 certification commit on `origin/main`)

## Why Production Deployment Was Not Executed (Verified Blockers)
1. **No Local SSH Access to Production Server:** Inspection of `~/.ssh/config` confirmed only `Host github.com` is configured locally; no production SSH host, key, or credentials are available in this workspace.
2. **Authorization Policy:** Per Phase 12.9 rules, service-impacting production deployments, database migrations, and server restarts require explicit operator execution/authorization.

## Verified Production Deployment & Rollback Commands
Run these commands directly on the production server (`/var/www/bondhoo.com`) once authorized:

```bash
# 1. Mandatory Pre-Deployment Backup
cd /var/www/bondhoo.com
mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" | gzip > /backups/bondhoo_$(date +%Y%m%d_%H%M%S).sql.gz
git rev-parse HEAD > /backups/bondhoo_last_good_commit.txt

# 2. Deploy Verified Main Branch
php artisan down --retry=60
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache

# 3. Install/Start Laravel Reverb (if not yet active)
# composer require laravel/reverb
# Set BROADCAST_CONNECTION=reverb & REVERB_APP_KEY=connectly_reverb_key in .env
sudo supervisorctl restart all
sudo systemctl reload nginx php8.5-fpm
php artisan up
```

```bash
# 4. Upload Signed Production APK from Mac to Server
scp "/Volumes/EDGE/mac mini  bondhoo/jugajug/public/bondhoo.apk" root@[2a02:c207:2327:8704::1]:/var/www/bondhoo.com/public_html/
```

```bash
# 5. Rollback Procedure (if needed)
cd /var/www/bondhoo.com
php artisan down
git checkout $(cat /backups/bondhoo_last_good_commit.txt)
php artisan migrate:rollback --step=1 --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo supervisorctl restart all
php artisan up
```
