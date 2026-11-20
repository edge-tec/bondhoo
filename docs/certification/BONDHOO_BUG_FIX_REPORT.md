# BONDHOO — Bug Fix Report

## 1. Critical Android Network & Serialization Fixes
- **`AuthInterceptor.kt`:** Removed unconditional `.header("Content-Type", "application/json")` override that broke all 8 `@Multipart` file upload endpoints (avatar, cover, media, marketplace media, chunk upload, video upload, message attachments, voice messages).
- **`ConnectlyApi.kt` (`ApiResponseSerializer`):** Implemented custom `ApiResponseSerializer<T>` so `ApiResponse<Unit>` gracefully handles `"data": null` or omitted `"data"` across 72 endpoints without throwing `JsonDecodingException` / `MissingFieldException`.
- **`ConnectlyApi.kt` (`MediaUploadResponse` & `LiveCommentDto`):** Added `avatar_url`/`cover_url` resolution and `live_stream_id`/`message` fallbacks.
- **`ParentalApiService.kt`:** Removed duplicate `api/` prefix across all 11 endpoints (`api/v1/parental/...` -> `v1/parental/...`).

## 2. Backend API, Routes & Real-Time Fixes
- **`routes/channels.php`:** Added `['guards' => ['web', 'sanctum']]` to 10 private channels (`page.*`, `group.*`, `family.*`, `device.*`).
- **`app/Services/RealtimeService.php`:** Fixed `broadcastToUser()` ignoring `$event` parameter.
- **`app/Http/Controllers/Api/v1/PresenceController.php`:** Normalized string `"true"`/`"false"` for `is_typing` to eliminate 422 errors from Android.
- **`app/Http/Controllers/Api/v2/Page/PageInboxV2ApiController.php`:** Added `text` fallback for `body`.
- **`app/Http/Controllers/Api/v2/Page/PageTeamV2ApiController.php`:** Added `user_id` lookup and `permissions` fallback for `custom_permissions`.
- **`app/Http/Controllers/Api/v2/LiveStreamingV2Controller.php`:** Added `reaction` fallback and mapped `heart`/`star`/`fire`.
- **`app/Services/Creator/CreatorStudioService.php`:** Broadcast live gifts to `live.{contextId}` (`gift.received` & `live.gift.received`).
- **`routes/web.php`:** Added missing `use App\Models\Page;` import.
- **`routes/api.php`:** Removed duplicate shadowed `/groups/{id}/moderation/reports` and `/search` routes.

## 3. Website Frontend Fixes (Blade & Vue 3)
- Fixed 404 API endpoints in `Friends.vue`, `ProfileFriendsCard.vue`, `Marketplace/Show.vue`, `Marketplace/Orders.vue`, `Pages/Show.vue`, `settings/notifications.blade.php`, and `dashboard.blade.php`.
- Fixed broken navigation URLs: `/stories/create` -> `/feed?create_story=1`, `/settings/devices` -> `/devices`, `/watch/studio` -> `/reels?create=1`, dead Events link -> `/events`, Register terms/privacy -> `/terms` & `/privacy`.
- Fixed undefined JS handlers: `openMessengerSoundModal()`, `toggleHeaderNotificationDropdown()`, Avatar Guard API toggle, and all missing Messenger handlers (`toggleReaction`, `initReply`, `cancelReply`, `openCreatePollModal`, `votePoll`, `shareMyLocation`, `openScheduleModal`, `openDisappearingTimerModal`, `openChatThemeModal`, `cycleVoiceSpeed`, `transcribeVoiceMessage`).
- Fixed `.message-hover-toolbar` -> `.message-action-toolbar` selector bug in `messages/index.blade.php`.
- Added native Pusher/Reverb v7 `window.Echo` client (`public/js/bondhoo-echo-client.js`) loaded in both `layouts/app.blade.php` and `app.blade.php`.

## 4. Android Feature & UI Fixes
- **`MainActivity.kt`:** Wired Reels tab "Create Reel" to `navigateToCreateReel()` instead of `navigateToCreateStory()`.
- **`SavedPostsScreen.kt` & `MemoriesScreen.kt`:** Added Hilt ViewModels so live `ConnectlyApi` is injected instead of `api == null`.
- **`ProfileNavigation.kt`:** Wired `onNavigateToChat` for `FriendsScreen`.
- **`NotificationsRepositoryImpl.kt` & `NotificationsNavigation.kt`:** Wired real `acceptFriendRequest`/`rejectFriendRequest` APIs, removed hardcoded mock notifications & unread count `3`, and wired Search user click to `profile_route?username=$userId`.
- **`ProfileRepositoryImpl.kt` & `ProfileScreen.kt`:** Removed hardcoded fake cover, bio, occupation, location, and counts (`2540`, `865`, `128`).
- **`LiveRepositoryImpl.kt`:** Wired `joinStream()` to real `api.getLiveStreamDetails(streamId)` instead of returning `getSampleLiveStream()`.
- **`ReelsRepositoryImpl.kt`, `StoriesRepositoryImpl.kt`, `EventsRepositoryImpl.kt`, `SearchViewModel.kt`:** Removed hardcoded `12501` like count, hardcoded recent searches, and error swallowing.
- **`ChatScreen.kt`, `ChatViewModel.kt`, `MessengerRepositoryImpl.kt`:** Wired full Enterprise Messenger UI and APIs (Poll creation/voting/closing, Location sharing/stopping, Disappearing Timer, Chat Theme + Reset, Scheduled Messages, Voice Speed 0.5x–2x with `MediaPlayer.playbackParams`, Voice Transcription, Message Translation).
