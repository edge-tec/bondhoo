# PHASE 14.1 — Git Baseline & Repository Audit

- **Audit Date:** 2026-11-20
- **Repository:** `/Volumes/EDGE/mac mini  bondhoo/jugajug`
- **Branch:** `main` (`origin/main`)
- **Verified Baseline Commit:** `3271817` (Previous Baseline: `8632b12`)
- **Working Tree State at Phase 14 Start:** Clean (0 uncommitted changes)
- **Secrets & Credential Audit:** Verified `.env`, `*.jks`, `*.keystore`, `keystore.properties`, and `android/keystore.properties` are excluded in `.gitignore`. Zero production secrets committed.

## Newly Discovered & Fixed Items in Phase 14
1. **Poll Vote Payload Normalization (`MessengerEnterpriseApiController.php` & `messages/index.blade.php`):**
   - `POST /api/v1/polls/{id}/vote` previously required `option_ids` array only, while the Blade frontend sent `{ option_id: optionId }`. Added backward-compatible normalization in `MessengerEnterpriseApiController::votePoll` and updated Blade JS to send both `option_ids: [optionId]` and `option_id`.
2. **Laravel Reverb Server Configuration (`config/reverb.php`):**
   - Created standard Laravel 12 `config/reverb.php` server configuration matching `config/broadcasting.php` (`REVERB_APP_KEY=connectly_reverb_key`, port `8080`, Redis scaling support).
3. **Android Production Keystore Integration (`android/app/build.gradle.kts` & `android/keystore.properties.example`):**
   - Upgraded `signingConfigs.create("release")` to load `android/keystore.properties` (in addition to `BONDHOO_UPLOAD_*` environment variables) with clean fallback to debug keystore only when neither is present.
4. **Android ProfileViewModel Cleanup (`ProfileViewModel.kt`):**
   - Removed hardcoded `"dixie.mann"` owner check and fabricated non-owner view counts (`1450L`, `65L`, `420L`), and wired `reverbWebSocketManager.subscribeProfileChannel(user.username)`.
