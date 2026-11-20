# BONDHOO — Real-Time WebSocket / Reverb Certification

## 1. Protocol & Architecture Verification
- **Protocol:** Pusher Protocol v7 compatible with Laravel Reverb (`wss://bondhoo.com/app/<key>?protocol=7`).
- **Website Client:** `public/js/bondhoo-echo-client.js` (loaded in both `resources/views/layouts/app.blade.php` and `resources/views/app.blade.php`).
  - Exposes `window.Echo.channel()`, `window.Echo.private()`, `window.Echo.join()`, `window.Echo.leave()`.
  - Automatically activates when `BROADCAST_CONNECTION` is `reverb` or `pusher` (read via `<meta name="broadcast-connection">`).
  - Authenticates private channels via `/broadcasting/auth` (CSRF cookie) or `/api/v1/broadcasting/auth` (Sanctum Bearer token).
  - Implements exponential backoff reconnection, channel re-subscription on reconnect, and 25s `pusher:ping`/`pusher:pong` keepalive.
- **Android Client:** `ReverbWebSocketManager.kt`
  - Fixed event/payload compatibility: `notification.read_all`, `id`/`notification_id`, `avatar_url`/`avatar`, `duration_seconds`/`duration`, `live.comment.created`/`live.comment`, `gift.received`/`live.gift`/`live.gift.received`.
  - Fixed `subscribeGroupChannel` to use public channel subscription matching backend `Channel("group.{id}")`.
  - Deduplicated `subscribeUserChannel()` to eliminate 4-second `/api/v1/broadcasting/auth` request spam.

## 2. Backend Channel Authorization
- `routes/channels.php`: All private & presence channels now explicitly authorize both `['guards' => ['web', 'sanctum']]`.
- `PlatformParityAndSecurityTest::test_broadcasting_auth_rejects_unauthenticated_requests`: **PASSED** (HTTP 401 on unauthenticated auth attempt).

## 3. Production Requirement for Live WebSocket Delivery
- Local development `.env` currently uses `BROADCAST_CONNECTION=log` (no local Reverb daemon running).
- **To activate live WebSocket push on `bondhoo.com`:**
  1. Ensure `laravel/reverb` is installed (`composer require laravel/reverb`) and configured in `.env`:
     ```env
     BROADCAST_CONNECTION=reverb
     REVERB_APP_ID=bondhoo_app
     REVERB_APP_KEY=connectly_reverb_key
     REVERB_APP_SECRET=<strong-random-secret>
     REVERB_HOST=bondhoo.com
     REVERB_PORT=443
     REVERB_SCHEME=https
     ```
  2. Run `php artisan reverb:start --host=0.0.0.0 --port=8080` under Supervisor.
  3. Reverse-proxy `/app` and `/apps` in Nginx to `http://127.0.0.1:8080` with `Upgrade` / `Connection "upgrade"` headers.
