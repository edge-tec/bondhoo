# PHASE 15.14 — Monitoring & Observability Certification

## 1. Application Health & Metrics Endpoints (`routes/api.php`)
- `GET /api/v1/health` (`SystemController@health`)
- `GET /api/v1/metrics` (`InfrastructureController@metrics`)
- `GET /api/v1/system/status` (`InfrastructureController@status`)

## 2. Production Log & Process Monitoring Checklist
- **Laravel Application Logs:** `/var/www/bondhoo.com/storage/logs/laravel.log`
- **Nginx Access / Error Logs:** `/var/log/nginx/access.log`, `/var/log/nginx/error.log`
- **Supervisor Worker & Reverb Logs:** `/var/log/supervisor/supervisord.log` (`sudo supervisorctl status`)
- **Scheduled Queue Processor:** `php artisan schedule:list` (verifies `messenger:process-queue` runs every minute)
