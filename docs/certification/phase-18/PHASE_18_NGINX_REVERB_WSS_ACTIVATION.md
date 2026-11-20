# PHASE 18 — NGINX REVERB WEBSOCKET ACTIVATION REPORT

**Project:** Bondhoo Social Platform  
**Target Endpoint:** `wss://bondhoo.com/app/connectly_reverb_key`  
**Timestamp:** 2026-11-20T20:25:16+06:00  
**Local Config Verification:** **PASS**  
**Live HostVra Nginx Reload:** **BLOCKED (Awaiting Operator Execution in HostVra Web Terminal)**

---

## 1. Inspected Repository Configurations (`PASS`)

1. **[`deploy/production/nginx-reverb-websocket.conf`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/deploy/production/nginx-reverb-websocket.conf):**
   - Defines `location /app` and `location /apps` with `proxy_http_version 1.1`, `Upgrade $http_upgrade`, `Connection "Upgrade"`, `proxy_read_timeout 60s`, `proxy_send_timeout 60s`, and upstream `http://127.0.0.1:8080`.
2. **[`deploy/production/supervisor-bondhoo-reverb.conf`](file:///Volumes/EDGE/mac%20mini%20%20bondhoo/jugajug/deploy/production/supervisor-bondhoo-reverb.conf):**
   - Runs `/usr/bin/php /var/www/bondhoo.com/artisan reverb:start --host=127.0.0.1 --port=8080` under `www-data` with `autostart=true`, `autorestart=true`, and `minfds=10000`.

---

## 2. Exact HostVra Web Terminal Nginx Activation Procedure

Inside `root@hostvra:/var/www/bondhoo.com#`:

```bash
# 1. Verify if nginx-reverb-websocket.conf is already included
grep -n "nginx-reverb-websocket.conf" /etc/nginx/sites-available/bondhoo.com || echo "NOT INCLUDED YET"

# 2. If not included, add this line inside the `server { listen 443 ssl; server_name bondhoo.com; ... }` block:
#    include /var/www/bondhoo.com/deploy/production/nginx-reverb-websocket.conf;

# 3. Validate and reload Nginx
nginx -t && systemctl reload nginx

# 4. Verify Reverb is listening on 127.0.0.1:8080
ss -tlnp | grep 8080
```
