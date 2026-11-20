# PHASE 16 — LIVE REVERB WSS CERTIFICATION REPORT

**Project:** Bondhoo Social Platform  
**Production WSS Endpoint:** `wss://bondhoo.com/app/connectly_reverb_key`  
**Timestamp:** 2026-11-20T19:15:00+06:00  
**Final Status:** **BLOCKED (Code & Automated Tests PASS; Live HostVra Daemon & Nginx `/app` Proxy Pending Operator Activation)**

---

## 1. Local & Automated Reverb/Broadcaster Verification (`PASS`)

| Verification Check | Evidence | Status |
| :--- | :--- | :---: |
| **`config/reverb.php` & `config/broadcasting.php`** | Internal bind `127.0.0.1:8080`, public host `bondhoo.com:443` (`https`), key `connectly_reverb_key` | **PASS** |
| **Private Channel Auth (`routes/channels.php`)** | `['guards' => ['web', 'sanctum']]` enforced on all private/presence channels; verified in `Phase14CrossPlatform30ScenariosTest` (Scenarios 29 & 30: HTTP 200 HMAC signature for participant, HTTP 403 for non-participant) | **PASS** |
| **Website Pusher v7 Client (`public/js/bondhoo-echo-client.js`)** | Connects to `wss://bondhoo.com/app/connectly_reverb_key`, authenticates private channels via `/broadcasting/auth`, auto-reconnects with exponential backoff | **PASS** |
| **Android Reverb Client (`ReverbWebSocketManager.kt`)** | Connects to `wss://bondhoo.com/app/connectly_reverb_key`, authenticates via Sanctum Bearer token, 4-second auth deduplication | **PASS** |

---

## 2. Live Production WSS Endpoint Probe (`BLOCKED`)

**Command Executed:**
```bash
curl -i -sS "https://bondhoo.com/app/connectly_reverb_key?protocol=7&client=web&version=1.0.0"
```
**Observed Output:**
`HTTP/2 404` via Cloudflare/Nginx on `https://bondhoo.com`.

**Operator Activation Steps on HostVra Console:**
1. Run `cd /var/www/bondhoo.com && git pull origin main && bash deploy/production/cutover-hostvra.sh` (which installs and starts `supervisor-bondhoo-reverb.conf` on `127.0.0.1:8080`).
2. Include `/var/www/bondhoo.com/deploy/production/nginx-reverb-websocket.conf` inside `/etc/nginx/sites-available/bondhoo.com` (`listen 443 ssl` block) and run `nginx -t && systemctl reload nginx`.
