# PHASE 15.14 — Disaster Recovery & Rollback Procedures

1. **Application Code Rollback:**
   ```bash
   cd /var/www/bondhoo.com
   php artisan down
   git checkout $(cat /backups/bondhoo_pre_phase15_commit.txt)
   php artisan optimize
   php artisan queue:restart
   php artisan up
   ```
2. **Database Recovery:**
   ```bash
   gunzip < /backups/bondhoo_pre_phase15_<timestamp>.sql.gz | mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"
   ```
3. **Reverb & Queue Worker Recovery:**
   ```bash
   sudo supervisorctl restart all
   php artisan queue:restart
   php artisan reverb:restart
   ```
4. **Nginx & PHP-FPM Recovery:**
   ```bash
   sudo nginx -t && sudo systemctl reload nginx php8.5-fpm
   ```
5. **Android Release Rollback:**
   - Re-upload the previous archived signed APK to `/var/www/bondhoo.com/public_html/bondhoo.apk` and increment `versionCode` for any Play Store hotfix release.
