# BONDHOO — Baseline Audit & Master Issue Tracker

- **Date:** 2026-11-20
- **Branch:** `main`
- **Baseline Commit:** `ce7b44a` (Recovery Tag: `baseline-pre-repair-20261120`)
- **Total Registered Laravel Routes:** 1,515
- **Android Retrofit Endpoints:** 355 (`ConnectlyApi.kt`) + 11 (`ParentalApiService.kt`)

## Platform Component Inventory
1. **Laravel Backend:** Laravel 12 / PHP 8.5 (`app/`, `routes/api.php`, `routes/web.php`, `routes/channels.php`, `routes/parental.php`, `routes/console.php`)
2. **Website Frontend:** Hybrid Blade (`resources/views/`) + Inertia/Vue 3 + TypeScript (`resources/js/`)
3. **Real-Time WebSocket:** Pusher/Reverb protocol (`config/broadcasting.php`, `routes/channels.php`, `app/Services/RealtimeService.php`, `public/js/bondhoo-echo-client.js`, `ReverbWebSocketManager.kt`)
4. **Android Application:** Multi-module Jetpack Compose + Hilt + Room + Retrofit + Kotlinx Serialization (`android/app`, `android/core/*`, `android/feature/*`)

## Master Issue Tracker (Verified Against Source Code)

| ID | Layer | Severity | Description | Status |
|---|---|---|---|---|
| ISS-001 | Android Network | P0 | `AuthInterceptor` overwrites `Content-Type: application/json` breaking all 8 `@Multipart` uploads | FIXED |
| ISS-002 | Android Network | P0 | `ApiResponse<Unit>` crashes deserialization on `"data": null` across 72 endpoints | FIXED |
| ISS-003 | Android Network | P1 | `MediaUploadResponse` missing `avatar_url`/`cover_url` keys; `LiveCommentDto` field mismatch | FIXED |
| ISS-004 | Android Network | P1 | `ParentalApiService` double `api/` prefix on all 11 endpoints | FIXED |
| ISS-005 | Backend / WS | P0 | 10 private channels in `routes/channels.php` missing `sanctum` guard | FIXED |
| ISS-006 | Backend / WS | P0 | `RealtimeService::broadcastToUser()` ignores `$event` parameter | FIXED |
| ISS-007 | Backend / WS | P0 | `PresenceController::typing()` rejects string `"true"`/`"false"` from Android (422) | FIXED |
| ISS-008 | Backend / API | P1 | `PageInboxV2ApiController`, `PageTeamV2ApiController`, `LiveStreamingV2Controller` payload mismatches (422) | FIXED |
| ISS-009 | Backend / Routes | P1 | `routes/web.php` missing `use App\Models\Page;`; duplicate `moderation/reports` & `/v1/search` shadowing in `routes/api.php` | IN PROGRESS |
| ISS-010 | WebSocket / Web | P0 | `window.Echo` never initialized on Web; all Echo listeners dead | IN PROGRESS |
| ISS-011 | WebSocket / Android | P0 | Event/payload mismatches (`notification.read_all`, `id`, `avatar_url`, `duration_seconds`, `live.comment.created`, `gift.received`, 4s auth spam) | FIXED |
| ISS-012 | Website Frontend | P0 | 404 API endpoints in `Friends.vue`, `ProfileFriendsCard.vue`, `settings/notifications.blade.php`, `Marketplace/Show.vue`, `Marketplace/Orders.vue`, `dashboard.blade.php`, `Pages/Show.vue` | IN PROGRESS |
| ISS-013 | Website Frontend | P1 | Broken links (`/stories/create`, `/settings/devices`, `/watch/studio`, dead Events link, Register terms/privacy) & undefined JS handlers in `messages/index.blade.php`, `profile/show.blade.php`, `pages/show.blade.php` | IN PROGRESS |
| ISS-014 | Messenger (Web+Android) | P0 | Enterprise Messenger features (Polls, Location, Themes, Disappearing Timer, Scheduled Messages, Voice Speed, Transcription, Translation) incomplete in UI & ViewModel stubs | IN PROGRESS |
| ISS-015 | Android Features | P0 | `SavedPostsScreen` & `MemoriesScreen` permanently empty (`api == null`); Create Reel routed to Create Story; Notification friend request stub; Search user click ignored | FIXED |
| ISS-016 | Android Features | P1 | Hardcoded mock/sample fallbacks & error swallowing in `ProfileRepositoryImpl`, `LiveRepositoryImpl`, `NotificationsRepositoryImpl`, `SearchRepositoryImpl`, `ReelsRepositoryImpl`, `FeedRepositoryImpl`, `EventsRepositoryImpl`, `StoriesRepositoryImpl` | IN PROGRESS |
| ISS-017 | Security | P1 | `network_security_config.xml` allows global cleartext traffic in release builds | IN PROGRESS |
