# PHASE 13: LMS PRODUCTION DEPLOYMENT GUIDE
**Platform:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform  
**System Module:** Professional Learning & Development Platform (LMS)  
**Date:** 2026-11-04  
**Author:** DevOps & Lead Release Manager  

---

## 1. Prerequisites & Environment

- PHP >= 8.5 with extensions: `pdo`, `mbstring`, `openssl`, `bcmath`, `curl`, `json`, `gd`/`imagick`.
- MySQL >= 8.0 or PostgreSQL >= 15 (SQLite in-memory for testing).
- Redis >= 7.0 (for Horizon queue workers and search caching).
- S3-compatible object storage for video and course attachments.

---

## 2. Deployment Steps

### Step 1: Code Deployment
Pull latest code including Phase 13 migrations and services:
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
```

### Step 2: Database Migration
Run the 8 reversible migrations:
```bash
php artisan migrate --force
```

### Step 3: Cache Optimization
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 4: Worker & WebSocket Reload
```bash
php artisan horizon:terminate
php artisan queue:restart
```

### Step 5: Android Client Distribution
Build release APK / AAB:
```bash
cd android
./gradlew assembleRelease
# Output located at: android/app/build/outputs/apk/release/app-release-unsigned.apk
```

---

## 3. Rollback Procedure
If rollback is required:
```bash
php artisan migrate:rollback --step=8
php artisan route:clear
php artisan config:clear
```

