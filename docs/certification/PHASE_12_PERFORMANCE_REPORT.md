# PHASE 12.8 — Performance & Reliability Report

## Measured Local & Read-Only Metrics

| Metric | Measurement | Environment |
|---|---|---|
| **Laravel Route Boot** | 1,515 routes loaded cleanly | Local PHP 8.5 |
| **Messenger Suite Execution** | 173 tests, 1,054 assertions in **5.99s** | Local SQLite |
| **Enterprise & Parity/Security Tests** | 12 tests, 63 assertions in **1.08s** | Local SQLite |
| **Full Backend Test Suite** | 2,466 tests, 16,680 assertions in **89.74s** | Local SQLite |
| **Website Vite Production Build** | 680 modules built in **4.24s** (`app.js`: 195.16 kB / 66.92 kB gzip; `app.css`: 207.59 kB / 27.76 kB gzip) | Local Node / Vite 8.3.0 |
| **Android R8 Release Build** | 665 tasks, R8 minified APK = **48 MB** in **2m 24s** | Local Gradle 8.11.1 |
| **Live `https://bondhoo.com/api/v1/search` RTT** | **0.899s** (single read-only request via Cloudflare) | Live Production |

## Database & Query Reliability
- `database/migrations/2026_11_20_143000_create_ultra_enterprise_messenger_tables.php` includes indexes on `messages.expires_at`, foreign keys on `chat_polls`, `chat_poll_options`, `chat_poll_votes` (with unique vote constraint), and `scheduled_messages`.
- `MessengerService::getMessages()` eager-loads `poll.options.votes.user` to prevent N+1 queries.
- `ReverbWebSocketManager.kt` deduplicates `subscribeUserChannel()` to eliminate 4-second auth request spam.
- **Controlled Load Testing:** Staging load testing was not executed locally to avoid overloading production (`bondhoo.com`).
