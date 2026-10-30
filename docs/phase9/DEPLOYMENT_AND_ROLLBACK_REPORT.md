# JUGAJUG Phase 9.0 — Deployment and Rollback Report

**Deployment Script:** `deploy.sh`  
**Execution Architecture:** Zero-Downtime Atomic Symlink Deployment  

---

## 1. Zero-Downtime Pipeline Stages
1. **Release Provisioning:** Creates timestamped directory `/var/www/jugajug/releases/$(date +%Y%m%d%H%M%S)`.
2. **Persistent Storage Linking:** Symlinks `/shared/storage` and `/shared/.env` into active release.
3. **Application Cache Warmup:** Runs `config:cache`, `route:cache`, `view:cache`, and `event:cache`.
4. **Database Migrations:** Safely executes `php artisan migrate --force`.
5. **Atomic Release Switch:** Switches symlink `ln -nfs ${RELEASE_DIR} /var/www/jugajug/current`.
6. **Graceful Worker Restart:** Executes `horizon:terminate` and `queue:restart`.
7. **PHP-FPM Reload:** Reloads PHP-FPM and OPcache.
8. **Automated Health Probe & Rollback:**
   Probes `http://localhost/api/v1/health`. If HTTP 200 OK, deployment succeeds. If probe fails, automatically switches symlink back to previous release and restarts workers.
