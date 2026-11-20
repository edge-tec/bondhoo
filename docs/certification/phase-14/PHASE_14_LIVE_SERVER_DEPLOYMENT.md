# PHASE 14.2 — Live Server Deployment Report

- **Target Domain:** `https://bondhoo.com`
- **Deployment Execution Status:** **BLOCKED / NOT EXECUTED**
- **Blocker Reason:** Local `~/.ssh/config` contains only `Host github.com`; no SSH private key or authorized session for `bondhoo.com` (`2a02:c207:2327:8704::1`) is available in this environment.

## Exact Operator Deployment & Rollback Sequence
Execute these commands on the production server (`/var/www/bondhoo.com`):

```bash
# 1. Pre-Deployment Database & Commit Backup
cd /var/www/bondhoo.com
mkdir -p /backups
mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" | gzip > /backups/bondhoo_pre_phase14_$(date +%Y%m%d_%H%M%S).sql.gz
git rev-parse HEAD > /backups/bondhoo_pre_phase14_commit.txt

# 2. Pull Verified Main Branch & Install Dependencies
php artisan down --retry=60
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Run Non-Destructive Migrations & Rebuild Caches
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Restart Workers & Services
sudo supervisorctl restart all
sudo systemctl reload nginx php8.5-fpm
php artisan up
```

### Rollback Sequence
```bash
cd /var/www/bondhoo.com
php artisan down
git checkout $(cat /backups/bondhoo_pre_phase14_commit.txt)
php artisan migrate:rollback --step=1 --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo supervisorctl restart all
php artisan up
```
