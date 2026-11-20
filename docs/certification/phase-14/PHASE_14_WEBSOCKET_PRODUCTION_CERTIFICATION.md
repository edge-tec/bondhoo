# PHASE 14.3 — Real-Time Messenger & Reverb WebSocket Production Certification

## 1. Local & Protocol Verification (PASS)
- **Reverb Server Config:** `config/reverb.php` created and aligned with `config/broadcasting.php` (`REVERB_SERVER_PORT=8080`, `REVERB_APP_KEY=connectly_reverb_key`).
- **Private Channel HMAC Auth Verification (`Phase14CrossPlatform30ScenariosTest`):**
  - Tested `POST /api/v1/broadcasting/auth` with real `PusherBroadcaster` and `routes/channels.php`:
    - Authorized conversation participant receives **HTTP 200** with valid `{"auth": "connectly_reverb_key:<hmac>"}`.
    - Unauthorized user receives **HTTP 403 Forbidden**.
    - Unauthenticated user receives **HTTP 401 Unauthorized**.
- **Website Client (`public/js/bondhoo-echo-client.js`):** Pusher Protocol v7 `window.Echo` client loaded in `layouts/app.blade.php` and `app.blade.php`.
- **Android Client (`ReverbWebSocketManager.kt`):** Pusher Protocol v7 WebSocket client with aligned event/payload keys, deduplicated user channel auth, and real-time profile channel subscription (`ProfileViewModel.kt`).

## 2. Live Production WebSocket Status (`https://bondhoo.com`)
- **Status:** **BLOCKED (HTTP 404 on `https://bondhoo.com/app/connectly_reverb_key`)**
- **Required Server Action:** Install `laravel/reverb`, set `BROADCAST_CONNECTION=reverb` in production `.env`, run `php artisan reverb:start --host=0.0.0.0 --port=8080` under Supervisor, and add the Nginx `/app` WebSocket proxy block.
