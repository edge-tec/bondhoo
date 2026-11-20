# PHASE 16 — LARAVEL REVERB & NGINX WEBSOCKET DEPLOYMENT REPORT

**Project:** Bondhoo Social Platform  
**Phase:** 16.6  
**Date:** 2026-11-20

---

## 1. Repository Configuration & Deployment Files

All required Laravel Reverb and Nginx reverse-proxy configurations are present and verified in the repository:

1. **Reverb Application Config:** [`config/reverb.php`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/config/reverb.php)
   - Internal bind: `127.0.0.1:8080`
   - Public scheme/host: `https://bondhoo.com:443` (`wss://bondhoo.com/app/{key}`)
   - Default App Key: `connectly_reverb_key`
2. **Supervisor Program Config:** [`deploy/production/supervisor-bondhoo-reverb.conf`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/deploy/production/supervisor-bondhoo-reverb.conf)
   - Command: `php /var/www/bondhoo.com/artisan reverb:start --host=127.0.0.1 --port=8080`
   - Auto-start & auto-restart enabled (`autostart=true`, `autorestart=true`, `minfds=10000`)
3. **Nginx Reverse Proxy Snippet:** [`deploy/production/nginx-reverb-websocket.conf`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/deploy/production/nginx-reverb-websocket.conf)
   - Proxies `/app` and `/apps` to `http://127.0.0.1:8080` with `Upgrade` and `Connection "Upgrade"` headers and `proxy_read_timeout 60s`.

---

## 2. Live Production Verification Status

| Check | Target | Observed Response | Status |
| :--- | :--- | :--- | :--- |
| **Live WSS Handshake Probe** | `https://bondhoo.com/app/connectly_reverb_key?protocol=7&client=web&version=1.0.0` | `HTTP/2 404` | **BLOCKED (Not Yet Activated on HostVra VPS)** |
| **Automated Supervisor Setup** | `deploy/production/cutover-hostvra.sh` Step 7 | Copies `supervisor-bondhoo-reverb.conf` to `/etc/supervisor/conf.d/` and runs `supervisorctl reread && supervisorctl update && supervisorctl restart bondhoo-reverb` | **READY FOR OPERATOR EXECUTION** |

---

## 3. Enabling Nginx `/app` Location on HostVra

After running `bash deploy/production/cutover-hostvra.sh`, include [`deploy/production/nginx-reverb-websocket.conf`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/deploy/production/nginx-reverb-websocket.conf) inside the `server { listen 443 ssl; server_name bondhoo.com; ... }` block of `/etc/nginx/sites-available/bondhoo.com` and run:

```bash
nginx -t && systemctl reload nginx
```
