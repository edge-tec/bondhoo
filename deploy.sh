#!/usr/bin/env bash
# =====================================================================
# Jugajug Social Network — Zero-Downtime Atomic Deployment Script
# =====================================================================
set -e

echo ">>> Starting Jugajug Zero-Downtime Deployment..."

APP_DIR="/var/www/jugajug"
RELEASE_DIR="${APP_DIR}/releases/$(date +%Y%m%d%H%M%S)"
SHARED_DIR="${APP_DIR}/shared"

if [ "$1" == "--verify-only" ]; then
    echo ">>> Running in verification mode..."
    php artisan test --compact
    echo ">>> Verification successful!"
    exit 0
fi

echo ">>> 1. Creating new release directory: ${RELEASE_DIR}"
mkdir -p "${RELEASE_DIR}"

echo ">>> 2. Linking shared storage and .env"
ln -nfs "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"
ln -nfs "${SHARED_DIR}/storage" "${RELEASE_DIR}/storage"

echo ">>> 3. Optimizing Laravel caches"
cd "${RELEASE_DIR}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo ">>> 4. Executing pending database migrations safely"
php artisan migrate --force

echo ">>> 5. Atomic symlink switch to new release"
ln -nfs "${RELEASE_DIR}" "${APP_DIR}/current"

echo ">>> 6. Graceful restart of Horizon & Queue workers"
php artisan horizon:terminate || true
php artisan queue:restart || true

echo ">>> 7. Reloading PHP-FPM and OPcache"
sudo systemctl reload php8.4-fpm || true

echo ">>> 8. Verifying deployment health"
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/v1/health || echo "500")

if [ "$HTTP_STATUS" == "200" ]; then
    echo ">>> Deployment successfully verified with 200 OK!"
else
    echo ">>> Health check failed with status ${HTTP_STATUS}! Triggering automatic rollback..."
    PREVIOUS_RELEASE=$(ls -td ${APP_DIR}/releases/* | head -n 2 | tail -n 1)
    ln -nfs "${PREVIOUS_RELEASE}" "${APP_DIR}/current"
    php artisan queue:restart || true
    echo ">>> Rolled back to ${PREVIOUS_RELEASE}."
    exit 1
fi

echo ">>> Zero-downtime deployment completed successfully!"
