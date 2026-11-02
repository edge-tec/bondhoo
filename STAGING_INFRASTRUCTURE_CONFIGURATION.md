# STAGING INFRASTRUCTURE CONFIGURATION SPECIFICATION

**Target Environment:** Isolated Controlled Staging  
**Platform:** JUGAJUG Backend (Laravel 13) + Connectly Android Staging Services  
**Date:** November 2026  

---

## 1. Environment Architecture & Isolation Topology

The staging environment is strictly segregated from production infrastructure to prevent accidental data contamination, secret leakage, or cross-environment signaling crosstalk.

```
                           +----------------------------------------+
                           |       Controlled Staging Network       |
                           +----------------------------------------+
                                                |
                 +------------------------------+------------------------------+
                 |                                                             |
        [ Nginx Web & TLS ]                                          [ Laravel Reverb WSS ]
     staging-api.jugajug.com                                        staging-ws.jugajug.com
        (Port 443 / HTTPS)                                            (Port 8080 -> 443)
                 |                                                             |
        +--------+--------+                                           +--------+--------+
        |                 |                                           |                 |
  [ PHP 8.5 FPM ]  [ Queue Workers ]                                  [ Redis Sentinel ]
  (Laravel 13 API) (Background Jobs)                                (DB 1 / Cache / PubSub)
        |                 |                                                    |
        +--------+--------+----------------------------------------------------+
                 |
        [ Staging MySQL 8.0 ]
       (Dedicated Database: `jugajug_staging`)
```

---

## 2. Infrastructure Configuration Parameters

### 2.1 Domain & TLS Configuration
- **API Base Hostname:** `https://staging-api.jugajug.com`
- **Reverb WebSocket Hostname:** `wss://staging-ws.jugajug.com`
- **TURN Hostname:** `turn.jugajug.com` (Target staging node)
- **TLS Protocol:** TLSv1.3 with automated Let's Encrypt renewal via Certbot.

### 2.2 Laravel `.env.staging` Configuration Template
```ini
APP_NAME=JUGAJUG-Staging
APP_ENV=staging
APP_KEY=base64:sTaGiNgApPKeY...ExAmPlE=
APP_DEBUG=false
APP_URL=https://staging-api.jugajug.com

# Database Isolation (Dedicated Staging Instance)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jugajug_staging
DB_USERNAME=jugajug_staging_user
DB_PASSWORD=${STAGING_DB_PASSWORD}

# Redis Queue & Cache Isolation
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=1
REDIS_CACHE_DB=2

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

# Laravel Reverb WebSocket Staging Configuration
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=staging_app_id
REVERB_APP_KEY=staging_reverb_key
REVERB_APP_SECRET=${STAGING_REVERB_SECRET}
REVERB_HOST=staging-ws.jugajug.com
REVERB_PORT=443
REVERB_SCHEME=https

# Staging WebRTC / TURN Configuration
TURN_SERVER_HOST=turn.jugajug.com
TURN_SERVER_PORT=3478
TURN_TLS_PORT=5349
TURN_SECRET=${STAGING_TURN_SECRET}
TURN_REALM=turn.jugajug.com
TURN_CREDENTIAL_TTL=7200

# Firebase Cloud Messaging Staging
FIREBASE_PROJECT_ID=connectly-staging
FIREBASE_CREDENTIALS=/etc/connectly/staging-firebase-adminsdk.json
```

---

## 3. Data Sanitization & Anonymization Policy

1. **Zero Production PII in Staging:**
   - In compliance with GDPR and strict enterprise governance, raw production databases are **never** dumped into staging.
   - All test users, feeds, posts, conversations, and calls in staging are generated via deterministic seeders (`php artisan db:seed`) and Faker factories.
2. **Deterministic Test Accounts:**
   - User 1 (Caller): `tester_alpha@connectly.staging` (ID: 101)
   - User 2 (Callee): `tester_beta@connectly.staging` (ID: 102)
   - User 3 (Observer): `tester_gamma@connectly.staging` (ID: 103)

---

## 4. Asynchronous Queue & Process Supervision

Supervisor configuration (`/etc/supervisor/conf.d/jugajug-staging.conf`):

```ini
[program:jugajug-staging-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/staging.jugajug.com/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/supervisor/staging-worker.log

[program:jugajug-staging-reverb]
command=php /var/www/staging.jugajug.com/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/staging-reverb.log
```

---

## 5. Firewall & Ingress Rules (Staging Node)

| Port | Protocol | Ingress Source | Purpose |
| :---: | :---: | :--- | :--- |
| `22` | TCP | Bastion / Corporate VPN only | Secure SSH administration |
| `80` | TCP | Any | Let's Encrypt HTTP-01 challenge redirect |
| `443` | TCP | Any | HTTPS API & WebSocket WSS |
| `3478` | UDP/TCP | Any | STUN & TURN signaling (Target Coturn) |
| `5349` | UDP/TCP | Any | Secure TURNS (Target Coturn) |
| `49152-65535` | UDP | Any | Dynamic WebRTC media relay ports |
| `3306` | TCP | Localhost only (`127.0.0.1`) | MySQL internal access |
| `6379` | TCP | Localhost only (`127.0.0.1`) | Redis internal access |

---

## 6. Audit & Status Sign-Off

- **Configuration Specification:** `PASS`
- **Staging Database Isolation:** `PASS`
- **Firewall & Security Model:** `PASS`
- **Physical Provisioning on Cloud Host:** `BLOCKED` (Pending approval for paid staging infrastructure).

