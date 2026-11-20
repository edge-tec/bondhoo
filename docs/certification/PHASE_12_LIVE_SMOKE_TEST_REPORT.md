# PHASE 12.10 — Live Production Smoke Test Report

- **Target:** `https://bondhoo.com`
- **Execution Time:** 2026-11-20 16:34 (+06:00)
- **Scope:** Read-only unauthenticated HTTP/HTTPS probes only (no production data modified; no real customer accounts touched).

## Live Probe Results

| Check | Endpoint | Observed Result | Interpretation |
|---|---|---|---|
| **Homepage & TLS** | `GET https://bondhoo.com` | **HTTP/2 200** (Cloudflare valid HTTPS) | **PASS** — Site is online and serving Inertia frontend. |
| **Public Search API** | `GET https://bondhoo.com/api/v1/search?q=bondhoo` | **HTTP 401** (0.899s) | **PENDING DEPLOY** — Live server is still running pre-`deff93a` code where `/api/v1/search` was shadowed by auth middleware. |
| **Android APK Download** | `HEAD https://bondhoo.com/bondhoo.apk` | **HTTP/2 404** | **PENDING UPLOAD** — `bondhoo.apk` has not yet been uploaded to `/var/www/bondhoo.com/public_html/`. |
| **WebSocket / Reverb Handshake** | `GET https://bondhoo.com/app/connectly_reverb_key?protocol=7&client=web&version=1.0.0` | **HTTP 404** | **PENDING REVERB SETUP** — Reverb server and Nginx `/app` WebSocket proxy are not yet active on production. |
| **Authenticated Live E2E (Login, Profile, Messenger, Uploads)** | `https://bondhoo.com` | **NOT EXECUTED** | Requires authorized production deployment & dedicated test accounts. |
