#!/usr/bin/env bash
# ==============================================================================
# BONDHOO — Phase 16 Safe Production Cutover Script (HostVra VPS)
# Run from the production application root (e.g. /var/www/bondhoo.com):
#   bash deploy/production/cutover-hostvra.sh
# ==============================================================================
set -euo pipefail

APP_DIR="${APP_DIR:-$(pwd)}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/bondhoo}"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"

echo "=== [1/8] Pre-flight check in ${APP_DIR} ==="
cd "${APP_DIR}"
if [ ! -f "artisan" ]; then
    echo "ERROR: artisan not found in ${APP_DIR}. Please cd to the Laravel project root (e.g. /var/www/bondhoo.com)."
    exit 1
fi

mkdir -p "${BACKUP_DIR}"
git rev-parse HEAD > "${BACKUP_DIR}/pre_cutover_commit_${TIMESTAMP}.txt"
cp -a .env "${BACKUP_DIR}/env_backup_${TIMESTAMP}"
chmod 600 "${BACKUP_DIR}/env_backup_${TIMESTAMP}"

echo "=== [2/8] Creating verified MySQL database backup ==="
DB_NAME=$(php -r 'require "vendor/autoload.php"; $app = require_once "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo config("database.connections.mysql.database");')
DB_USER=$(php -r 'require "vendor/autoload.php"; $app = require_once "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo config("database.connections.mysql.username");')
DB_PASS=$(php -r 'require "vendor/autoload.php"; $app = require_once "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo config("database.connections.mysql.password");')

if [ -n "${DB_NAME}" ] && command -v mysqldump >/dev/null 2>&1; then
    DUMP_FILE="${BACKUP_DIR}/db_${DB_NAME}_${TIMESTAMP}.sql.gz"
    MYSQL_PWD="${DB_PASS}" mysqldump --single-transaction --routines --triggers -u "${DB_USER}" "${DB_NAME}" | gzip > "${DUMP_FILE}"
    gzip -t "${DUMP_FILE}"
    chmod 600 "${DUMP_FILE}"
    echo "Verified database backup: ${DUMP_FILE} ($(du -h "${DUMP_FILE}" | cut -f1))"
fi

echo "=== [3/8] Fetching & fast-forwarding origin/main ==="
git fetch origin
git checkout main
git pull --ff-only origin main

echo "=== [4/8] Installing production Composer dependencies ==="
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "=== [5/8] Running non-destructive database migrations ==="
php artisan migrate:status
php artisan migrate --force

echo "=== [6/8] Rebuilding caches & restarting workers/Reverb ==="
php artisan optimize:clear
php artisan optimize
php artisan storage:link 2>/dev/null || true
php artisan queue:restart
php artisan reverb:restart 2>/dev/null || true

echo "=== [7/8] Installing Supervisor configs if Supervisor is active ==="
if [ -d "/etc/supervisor/conf.d" ]; then
    cp -f deploy/production/supervisor-bondhoo-reverb.conf /etc/supervisor/conf.d/bondhoo-reverb.conf
    cp -f deploy/production/supervisor-bondhoo-worker.conf /etc/supervisor/conf.d/bondhoo-worker.conf
    supervisorctl reread || true
    supervisorctl update || true
    supervisorctl restart bondhoo-reverb bondhoo-worker:* || true
fi

echo "=== [8/8] Post-cutover verification ==="
echo "Deployed commit: $(git rev-parse --short HEAD)"
php artisan migrate:status | tail -n 5
echo "Cutover completed successfully."
