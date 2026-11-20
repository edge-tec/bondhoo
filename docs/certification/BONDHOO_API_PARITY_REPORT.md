# BONDHOO — API Parity Report (Android ↔ Website ↔ Laravel Backend)

- **Laravel Registered Routes:** 1,515 (verified via `php artisan route:list --json`)
- **Android `ConnectlyApi.kt` Endpoints:** 355 (100% matched to registered Laravel routes)
- **Android `ParentalApiService.kt` Endpoints:** 11 (prefix fixed from `api/v1/parental/...` to `v1/parental/...`)

## Verified Parity Matrix

| Domain | Backend Route | Website Status | Android Status | Notes |
|---|---|---|---|---|
| **Auth & Sanctum** | `/api/v1/auth/*`, `/api/v2/auth/*` | PASS | PASS | Token & Cookie auth verified |
| **Messenger Core** | `/api/v1/conversations/*`, `/api/v1/messages/*` | PASS | PASS | Multipart & voice fixed in `AuthInterceptor` |
| **Enterprise Messenger** | `/api/v1/conversations/{id}/polls|location|theme|disappearing-timer|scheduled-messages`, `/api/v1/polls/{id}/vote|close`, `/api/v1/messages/{id}/transcribe|translate` | PASS | PASS | Full CRUD + tests passing |
| **Friends** | `/api/v1/friends/{id}/request|accept|decline|reject` | PASS (Fixed 404s) | PASS | `Friends.vue` & `ProfileFriendsCard.vue` aligned |
| **Marketplace Offers** | `/api/v1/marketplace/products/{id}/offers`, `/api/v1/marketplace/offers/*` | PASS (Fixed 404s) | PASS | `Show.vue` & `Orders.vue` aligned to `/api/v1/` |
| **Notifications** | `/api/v1/notifications/*`, `/api/v1/notifications/preferences` | PASS (Fixed 404) | PASS | Friend request accept/reject wired on Android |
| **Profile & Media** | `/api/v1/profile/*`, `/api/v2/uploads/chunks` | PASS (Fixed 404) | PASS | `avatar_url`/`cover_url` resolved; multipart fixed |
| **Pages & Groups** | `/api/v1/groups/{id}/join`, `/api/v2/pages/{id}/inbox|roles` | PASS (Fixed 404) | PASS | Payload mismatches resolved |
| **Live Streaming** | `/api/v1/live/streams/{id}/join|comments|reactions` | PASS | PASS | Reaction mapping & comment DTO aligned |
| **Search** | `/api/v1/search` (public + auth) | PASS | PASS | Shadowed auth-only duplicate route removed |
| **Saved & Memories** | `/api/v1/saved`, `/api/v1/memories` | PASS | PASS | Hilt ViewModel API injection fixed |
