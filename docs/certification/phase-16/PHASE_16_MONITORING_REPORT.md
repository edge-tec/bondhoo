# PHASE 16 — PRODUCTION MONITORING & OBSERVABILITY REPORT

**Project:** Bondhoo Social Platform  
**Phase:** 16.17  
**Date:** 2026-11-20

---

## 1. Production Log & Health Locations on HostVra VPS

| Component | Log File / Health Command on HostVra VPS | Purpose |
| :--- | :--- | :--- |
| **Laravel Application Log** | `/var/www/bondhoo.com/storage/logs/laravel.log` | Exceptions, stack traces, validation errors |
| **Laravel Reverb WebSocket Log** | `/var/www/bondhoo.com/storage/logs/reverb.log` | WebSocket connections, channel subscriptions, broadcast events |
| **Laravel Queue Worker Log** | `/var/www/bondhoo.com/storage/logs/worker.log` | Background jobs, notifications, media processing |
| **Supervisor Daemon Status** | `supervisorctl status bondhoo-reverb bondhoo-worker:*` | Live process uptime & PID monitoring |
| **Failed Queue Jobs Inspection** | `php /var/www/bondhoo.com/artisan queue:failed` | Inspect and retry (`queue:retry all`) any failed jobs |
| **Nginx Access & Error Logs** | `/var/log/nginx/access.log`, `/var/log/nginx/error.log` | HTTP/WebSocket status codes and upstream proxy health |

---

## 2. Post-Cutover Live Verification Commands

After running `bash deploy/production/cutover-hostvra.sh` on `root@hostvra:/var/www/bondhoo.com#`, run:

```bash
supervisorctl status
curl -i "https://bondhoo.com/api/v1/search?q=bondhoo"
curl -i "https://bondhoo.com/app/connectly_reverb_key?protocol=7&client=web&version=1.0.0"
tail -n 50 /var/www/bondhoo.com/storage/logs/reverb.log
```
