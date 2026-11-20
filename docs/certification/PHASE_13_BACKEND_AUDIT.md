# PHASE 13.2 — Complete Laravel Backend Audit

## Verified Regressions & New Fixes in Phase 13

| Module | Issue Found | Root Cause | Fix Implemented | Status |
|---|---|---|---|---|
| **Content Moderation** (`ContentModerationService.php`) | Non-image media (videos/docs) or empty `file_path` rejected posts as `'illicit'` | `file_exists()` matched directory `storage/app/public/` and sent non-image MIME types to `ImageModerationService` | Checked `!empty($media->file_path)`, `str_starts_with($mime, 'image/')`, and `is_file($filePath)` | **PASS** |
| **Communities / Groups** (`GroupService.php`) | User-supplied `community_type` (`project`, `interest`, `organization`) overwritten with `public` | `createGroup()` hardcoded `match($privacy)` ignoring `$data['community_type']` | Preserved `$data['community_type']` when provided | **PASS** |
| **Profile Analytics & Social Links** (`SocialWebController.php`) | Web Inertia profile visits did not record `profile_views` or expose `social_links` | `SocialWebController::profile()` omitted `ProfileAnalyticsService::recordView()` | Added `recordView()` for non-owners and included `social_links` in `$profileDetails` | **PASS** |
| **Story Viewers Parity** (`StoryEnterpriseService.php`) | Android `getStoryViewers` deserialized empty `UserDto` objects | Backend returned viewer info nested under `user: {...}` only | Added top-level `id`, `name`, `username`, `avatar_url`, `avatar` alongside nested `user` object | **PASS** |
| **Direct Video Upload** (`UploadApiController.php`) | Android `ConnectlyApi.uploadVideo` (`POST /api/v2/uploads/direct/complete`) failed with 422 | Controller only handled JSON `{ session_id, parts }` S3 multipart completion | Added `@Multipart` single-file upload handler creating `Media` record while preserving S3 multipart flow | **PASS** |
| **Presence & Typing** (`PresenceController.php`) | Android typing requests failed with 422 | Android sent `"true"`/`"false"` strings; Laravel `boolean` rule rejected strings | Normalized string booleans via `filter_var(..., FILTER_VALIDATE_BOOLEAN)` | **PASS** |
| **Page Inbox, Roles & Live Reactions** | Payload field mismatches (`text` vs `body`, `user_id` vs `identifier`, `heart`/`star`/`fire`) | Strict single-field validation | Added backward-compatible field fallbacks and reaction mapping | **PASS** |
