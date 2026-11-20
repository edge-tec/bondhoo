# PHASE 14.7 — Performance & Infrastructure Report

## Measured Benchmarks (Actual Executions)
- **Full Backend PHPUnit Suite (2,473 tests, 16,765 assertions):** ~92.5s (~37.4ms per test)
- **Phase 14 30-Scenario Cross-Platform Suite (3 tests, 45 assertions):** **1.69s**
- **Website Vite Production Bundle (680 modules):** **4.24s** (`app.js`: 195.16 kB / 66.92 kB gzip; `app.css`: 207.59 kB / 27.76 kB gzip)
- **Android Release Build (`:app:assembleRelease` with R8 minification & resource shrinking):** **48 MB APK**
- **Live Production Cloudflare Edge RTT (`https://bondhoo.com`):** **0.899s**

## Infrastructure & Query Optimizations Verified
- Eager loading on `MessengerService::getMessages()` (`poll.options.votes.user`, `sender.profile`, `media`, `attachments`, `reactions.user`, `replyTo.sender`) prevents N+1 queries.
- Database indexes verified on `messages.expires_at`, `scheduled_messages(status, scheduled_at)`, and unique vote index on `chat_poll_votes(poll_id, poll_option_id, user_id)`.
- WebSocket user channel subscription deduplicated in `ReverbWebSocketManager.kt`, preventing 4-second `/api/v1/broadcasting/auth` spam.
- *Note:* No synthetic high-concurrency load test was run against live `https://bondhoo.com` to protect production availability.
