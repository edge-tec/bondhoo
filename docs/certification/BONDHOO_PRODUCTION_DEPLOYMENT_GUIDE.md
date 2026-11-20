# BONDHOO — Production Deployment & Rollback Guide

> **Note:** Per production safety rules, no changes were deployed directly to `bondhoo.com` without authorization. Run the steps below on the production server when ready.

## 1. Pre-Deployment Backup (Mandatory)
```bash
cd /var/www/bondhoo.com
mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" | gzip > /backups/bondhoo_$(date +%Y%m%d_%H%M%S).sql.gz
git rev-parse HEAD > /backups/bondhoo_last_good_commit.txt
```

## 2. Pull Code, Build Assets & Run Non-Destructive Migrations
```bash
cd /var/www/bondhoo.com
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 3. Enable Live Laravel Reverb WebSocket Server
1. Ensure `.env` on production contains:
   ```env
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=bondhoo_app
   REVERB_APP_KEY=connectly_reverb_key
   REVERB_APP_SECRET=<strong-production-secret>
   REVERB_HOST=bondhoo.com
   REVERB_PORT=443
   REVERB_SCHEME=https
   REVERB_SERVER_HOST=0.0.0.0
   REVERB_SERVER_PORT=8080
   ```
2. Install Reverb if not yet present: `composer require laravel/reverb`
3. Run under Supervisor:
   - `php artisan reverb:start --host=0.0.0.0 --port=8080`
   - `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`
4. Ensure Cron runs Laravel Scheduler every minute (handles `messenger:process-queue` for scheduled & disappearing messages):
   ```cron
   * * * * * cd /var/www/bondhoo.com && php artisan schedule:run >> /dev/null 2>&1
   ```
5. Nginx WebSocket proxy block inside `server { ... }` for `bondhoo.com`:
   ```nginx
   location /app {
       proxy_http_version 1.1;
       proxy_set_header Host $http_host;
       proxy_set_header Upgrade $http_upgrade;
       proxy_set_header Connection "Upgrade";
       proxy_pass http://127.0.0.1:8080;
   }
   ```
6. Reload services: `sudo systemctl reload nginx php8.5-fpm && sudo supervisorctl restart all`

## 4. Upload Updated Release Android APK
```bash
scp "/Volumes/EDGE/mac mini  bondhoo/jugajug/public/bondhoo.apk" root@[2a02:c207:2327:8704::1]:/var/www/bondhoo.com/public_html/
```

## 5. Rollback Procedure
```bash
cd /var/www/bondhoo.com
git checkout $(cat /backups/bondhoo_last_good_commit.txt)
php artisan migrate:rollback --step=1 --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo supervisorctl restart all
```
