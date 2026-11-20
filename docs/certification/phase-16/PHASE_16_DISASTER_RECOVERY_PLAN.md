# PHASE 16 — DISASTER RECOVERY PLAN

**Project:** Bondhoo Social Platform  
**Production Path:** `/var/www/bondhoo.com`  
**Backup Root:** `/var/backups/bondhoo/phase16_<TIMESTAMP>`  
**Phase:** 16.4  
**Date:** 2026-11-20

---

## 1. Recovery Objectives

- **Recovery Point Objective (RPO):** `0 minutes` for pre-cutover state (full `--single-transaction` MySQL snapshot captured immediately prior to `git pull` and `php artisan migrate --force`).
- **Recovery Time Objective (RTO):** `< 3 minutes` for code/config rollback; `< 10 minutes` for full MySQL database restore.

---

## 2. Step-by-Step Emergency Rollback Procedure

If any anomaly is detected during or after the Phase 16 cutover on `root@hostvra:/var/www/bondhoo.com#`:

### Step 1: Enter Maintenance Mode
```bash
cd /var/www/bondhoo.com
php artisan down --retry=60
```

### Step 2: Roll Back Phase 16 Migration (if needed)
All 4 tables in `2026_11_20_143000_create_ultra_enterprise_messenger_tables.php` (`chat_polls`, `chat_poll_options`, `chat_poll_votes`, `scheduled_messages`) and `messages.expires_at` are purely additive and reversible:
```bash
php artisan migrate:rollback --step=1 --force
```

### Step 3: Restore Previous Git Commit & Dependencies
```bash
PREV_COMMIT=$(cat /var/backups/bondhoo/phase16_<TIMESTAMP>/PREVIOUS_COMMIT.txt)
git checkout "$PREV_COMMIT"
composer install --no-dev --optimize-autoloader --no-interaction
```

### Step 4: Restore `.env` (Only if `.env` was modified)
```bash
cp /var/backups/bondhoo/phase16_<TIMESTAMP>/.env.backup /var/www/bondhoo.com/.env
```

### Step 5: Full Database Restore (Only if full DB restoration is required)
```bash
DB_DATABASE=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"' | tr -d "'")
DB_USERNAME=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"' | tr -d "'")
DB_PASSWORD=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"' | tr -d "'")
gunzip < /var/backups/bondhoo/phase16_<TIMESTAMP>/db_pre_phase16_<TIMESTAMP>.sql.gz | MYSQL_PWD="$DB_PASSWORD" mysql -u"$DB_USERNAME" "$DB_DATABASE"
```

### Step 6: Rebuild Caches & Exit Maintenance Mode
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```
