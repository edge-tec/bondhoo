# PHASE 12.3 — Complete API Functionality & Parity Certification

- **Total Laravel Routes Verified:** 1,515 (`php artisan route:list --json`)
- **Android Retrofit Interfaces:** `ConnectlyApi.kt` (355 endpoints), `ParentalApiService.kt` (11 endpoints)
- **Local Automated API Tests:** Messenger Suite (173/173 PASS), Enterprise & Parity/Security Suite (12/12 PASS)

## Core API Contract Matrix (Local Verified vs Production Status)

| Method & Route | Auth | Validation / Authz | Website | Android | Local Test | Production (`bondhoo.com`) |
|---|---|---|---|---|---|---|
| `GET /api/v1/search` | Optional | Throttled (`search`) | Compatible | Compatible | **PASS** (200) | **401** (Pre-`deff93a` code running) |
| `POST /api/v1/presence/typing` | Sanctum/Web | Participant check (403 IDOR) + string/bool normalization | Compatible | Compatible | **PASS** (200 / 403) | Pending deploy |
| `POST /api/v1/conversations/{id}/polls` | Sanctum/Web | Participant check + options validation | Compatible | Compatible | **PASS** (201 / 403) | Pending deploy |
| `POST /api/v1/polls/{id}/vote` | Sanctum/Web | Participant check + option validation | Compatible | Compatible | **PASS** (200) | Pending deploy |
| `POST /api/v1/polls/{id}/close` | Sanctum/Web | Creator/Admin check | Compatible | Compatible | **PASS** (200) | Pending deploy |
| `POST /api/v1/conversations/{id}/disappearing-timer` | Sanctum/Web | Participant check | Compatible | Compatible | **PASS** (200 / 403) | Pending deploy |
| `POST /api/v1/conversations/{id}/scheduled-messages` | Sanctum/Web | Participant check + future datetime | Compatible | Compatible | **PASS** (201) | Pending deploy |
| `POST /api/v1/conversations/{id}/location` | Sanctum/Web | Participant check + lat/lng | Compatible | Compatible | **PASS** (201) | Pending deploy |
| `POST/DELETE /api/v1/conversations/{id}/theme` | Sanctum/Web | Participant check + `theme_color` | Compatible | Compatible | **PASS** (200 / 403) | Pending deploy |
| `POST /api/v1/messages/{id}/transcribe` & `translate` | Sanctum/Web | Participant check | Compatible | Compatible | **PASS** (200) | Pending deploy |
| `POST /api/v1/friends/{id}/accept\|decline\|request` | Sanctum/Web | Auth user check | Fixed 404s | Compatible | **PASS** | Pending deploy |
| `POST /api/v1/marketplace/products/{id}/offers` | Sanctum/Web | Auth + offer amount | Fixed 404 | Compatible | **PASS** | Pending deploy |
| `GET/POST /api/v1/marketplace/offers/*` | Sanctum/Web | Buyer/Seller authz | Fixed 404s | Compatible | **PASS** | Pending deploy |
| `PUT /api/v1/notifications/preferences` | Sanctum/Web | Auth user check | Fixed 404 | Compatible | **PASS** | Pending deploy |
| `POST /api/v1/broadcasting/auth` | Sanctum | Returns 401 when unauthenticated | Compatible | Compatible | **PASS** (401 unauth) | Pending Reverb setup |
| Multipart Uploads (8 Android endpoints) | Sanctum | Fixed `AuthInterceptor` header override | Compatible | Fixed | **PASS** (Compile + Test) | Pending APK upload |
