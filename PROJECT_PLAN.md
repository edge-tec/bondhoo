# Project Plan: Professional Social Network Platform (Bondhoo / Jugajug)

## 1. Executive Summary & Vision

The objective of this project is to build an enterprise-grade, high-performance, Facebook-inspired social networking platform. The platform serves two primary frontends:
1. **Modern Web Application**: Powered by Vue 3, TypeScript, Inertia.js, and Tailwind CSS.
2. **Mobile Applications**: Cross-platform Flutter client for Android and iOS.

Both clients consume a unified, highly optimized, secure, and versioned Laravel backend (`/api/v1`).

---

## 2. Environment & Technical Stack Audit

An exhaustive audit of the host environment and workspace reveals the following baseline:

| Component | Installed / Active Version | Target Specification | Status |
| :--- | :--- | :--- | :--- |
| **PHP Runtime** | 8.5.6 (Homebrew CLI) | PHP 8.2+ / 8.5 | ✅ Aligned |
| **Composer** | 2.10.0 | Latest 2.x | ✅ Aligned |
| **Framework** | Laravel 13.32.0 | Latest stable Laravel | ✅ Aligned |
| **Node.js** | v22.22.0 | Node LTS (v20+) | ✅ Aligned |
| **Package Manager** | npm 10.9.4 | npm 10+ | ✅ Aligned |
| **Database** | SQLite (3.53.4) locally / MySQL in `.env.example` | MySQL 8.0+ production | ✅ Configured |
| **Cache & Queue** | Predis 3.6.0 / Redis driver available | Redis | ✅ Ready |
| **API Auth** | Laravel Sanctum 4.3.3 | Laravel Sanctum | ✅ Active |
| **Real-time** | Pusher PHP Server 7.3.0 / Laravel Reverb ready | Reverb & Echo | ✅ Configured |
| **Object Storage** | `league/flysystem-aws-s3-v3` 3.35.3 | S3-compatible / MinIO | ✅ Installed |
| **Test Suite** | PHPUnit 12.5.35 / 1,195 passing tests | PHPUnit / Pest | ✅ 1,195 tests passing |
| **Frontend Stack** | Vite 8, Tailwind CSS 4, Blade views existing | Vue 3, TypeScript, Inertia.js | ⚠️ Needs Inertia setup |

---

## 3. Phased Implementation Roadmap

The system implementation is divided into small, verifiable phases to ensure maximum stability, backward compatibility, and rigorous test coverage.

### Phase 1: Authentication & Identity Lifecycle (Immediate Focus)
- **Scope**:
  - User registration with validation (`username`, `email`, `password`, `name`).
  - Standardized email, username, or phone login via `/api/v1/auth/login`.
  - Secure token creation via Laravel Sanctum (device-tagged plain text tokens for API/mobile, session cookies for web).
  - Logout (`/api/v1/auth/logout`) and multi-device revocation (`/api/v1/auth/logout-all`).
  - Standardized email verification endpoint (`/api/v1/auth/verify-email` and signed web verification).
  - Password reset flow via OTP / secure reset tokens (`/api/v1/auth/forgot-password`, `/api/v1/auth/reset-password`).
- **Deliverables**: Form Requests, Auth Controllers, AuthService, Mailables / Notifications, Feature Tests.
- **Verification**: Dedicated PHPUnit feature tests for valid registration, duplicate checks, brute-force rate limiting, password reset invalidation, and Sanctum token revocation.

### Phase 2: User Profiles, Customization & Privacy Settings
- **Scope**:
  - Profile retrieval and update (`/api/v1/profile`, `/api/v1/users/{username}`).
  - Unique username management with reserved-word validation and history tracking.
  - Avatar and cover photo updates with aspect ratio metadata and thumbnail generation.
  - Granular privacy settings (`profile_visibility`, `post_default_privacy`, `friends_list_visibility`, `search_engine_indexing`).
- **Deliverables**: `ProfileController`, `UserProfile` model enhancements, `UpdateProfileRequest`, `PrivacySettingPolicy`.
- **Verification**: Test profile visibility enforcement across public, friends-only, and private scopes.

### Phase 3: Social Graph & Relationships
- **Scope**:
  - Friend request lifecycle: Send request, accept request, decline/reject request, cancel pending request.
  - Unfriend action with mutual link teardown.
  - Follow / Unfollow system for asymmetric public subscriptions.
  - Block / Unblock system with bidirectional visibility cutoff.
  - Mutual friends computation and friend suggestions engine.
- **Deliverables**: `FriendshipController`, `FriendshipService`, Friendship & Block Policies, event broadcasting (`friend.request.sent`, `friend.accepted`).
- **Verification**: Tests for blocked-user isolation, mutual friend counts, and duplicate request prevention.

### Phase 4: Post Authoring & Core Content Engine
- **Scope**:
  - Create, view, update, and soft-delete text and image posts (`/api/v1/posts`).
  - Post audience scoping: `public`, `friends`, `followers`, `only_me`.
  - Content sanitization, hashtag extraction, and user tagging.
  - Post edit histories and pin-to-profile functionality.
- **Deliverables**: `PostController`, `PostService`, `PostPolicy`, `CreatePostRequest`, `PostResource`.
- **Verification**: Tests ensuring unauthorized users cannot edit or delete posts, and soft-deleted posts are hidden from feeds.

### Phase 5: Multi-Media Upload & Storage Pipeline
- **Scope**:
  - S3 / MinIO compatible storage adapter.
  - Support for multiple image attachments per post.
  - File signature (magic bytes) validation, MIME type enforcement (`image/jpeg`, `image/png`, `image/webp`).
  - Chunked upload session support for resilient mobile network uploads.
  - Asynchronous background thumbnailing and optimization jobs.
- **Deliverables**: `MediaController`, `MediaUploadService`, `ProcessMediaJob`, `MediaResource`.
- **Verification**: Tests rejecting spoofed file extensions, malicious payloads, and oversized attachments.

### Phase 6: Social Engagement (Reactions & Comments)
- **Scope**:
  - Polymorphic reactions on posts (`like`, `love`, `care`, `haha`, `wow`, `sad`, `angry`).
  - Comments on posts with rich formatting and timestamps.
  - Nested comment replies (1-level or multi-level threaded discussion).
  - Reaction counters and comment counters cached/denormalized with atomic increments.
- **Deliverables**: `ReactionController`, `CommentController`, `CommentPolicy`, `ReactionPolicy`.
- **Verification**: Tests verifying reaction toggle behavior, counter consistency, and comment author deletion rights.

### Phase 7: Chronological & Algorithmic News Feed Engine
- **Scope**:
  - Home feed endpoint (`/api/v1/feed`) with cursor-based pagination.
  - Post aggregation: Viewer's own posts + accepted friends' posts + followed users' posts.
  - Strict privacy enforcement: Exclude posts from blocked users and respect post-level audience.
  - Reverse-chronological ordering as baseline; smart engagement sorting as optional parameter.
  - Optimized composite indexes for index-only scans and zero N+1 queries.
  - Redis feed caching layer with tag-based invalidation upon new post or friendship change.
- **Deliverables**: `FeedService`, `FeedController`, composite database indexes, Redis cache decorator.
- **Verification**: Tests verifying feed excludes blocked users, friends-only posts from non-friends, and cursor continuity.

### Phase 8: Comprehensive Content Visibility & Authorization Hardening
- **Scope**:
  - Universal Policy audit across Posts, Comments, Media, Profiles, and Friendships.
  - IDOR vulnerability regression test suite.
  - Cross-user permission boundaries and privilege escalation defense.
- **Deliverables**: Complete Laravel Policies, Global Query Scopes, Policy Unit Tests.
- **Verification**: Matrix of automated tests asserting HTTP 403 Forbidden on cross-tenant operations.

### Phase 9: Real-Time Notifications
- **Scope**:
  - Multi-channel notification pipeline (Database, Broadcast via Reverb/Echo, Email via SMTP/Queue).
  - Notification triggers: Friend requests, post reactions, comments, mentions.
  - Unread badge counters, mark-as-read, and mark-all-read endpoints.
- **Deliverables**: Notification classes, `NotificationController`, broadcast channels.
- **Verification**: Tests verifying notification delivery, broadcast payload integrity, and unread counter accuracy.

### Phase 10: Search & Discovery Engine
- **Scope**:
  - Universal search endpoint (`/api/v1/search`) across Users, Posts, and Hashtags.
  - Full-text search and prefix matching on usernames and display names.
  - Privacy-aware post search results.
- **Deliverables**: `SearchController`, Search indexes, Search Resources.
- **Verification**: Tests verifying blocked users and private posts are excluded from search results.

### Phase 11: Moderation, Reporting & Trust & Safety
- **Scope**:
  - Reporting endpoints for posts, comments, and user profiles (`/api/v1/reports`).
  - Categorized report reasons (Spam, Harassment, Inappropriate Content).
  - Automated throttling and abuse detection.
- **Deliverables**: `ReportController`, `ReportService`, `ReportPolicy`.
- **Verification**: Tests preventing duplicate reports and confirming reporter confidentiality.

### Phase 12: Real-Time Messaging (Planned Subsequent Phase)
- **Scope**:
  - One-on-one and group conversation threads.
  - Real-time message broadcasting, typing indicators, presence, and read receipts via Reverb.
- **Deliverables**: `ConversationController`, `MessageController`, WebRTC signaling stubs.

### Phase 13: Flutter Mobile Application (Android & iOS)
- **Scope**:
  - Clean architecture Flutter codebase consuming `/api/v1`.
  - Secure storage for Sanctum Bearer tokens.
  - Feed infinite scroll, offline caching, push notifications integration.
- **Deliverables**: Flutter client project structure, Dio/Http client, state management (Bloc or Riverpod).

### Phase 14: Administrative & Moderation Console
- **Scope**:
  - Admin dashboard with RBAC (Super Admin, Moderator).
  - Moderation queue for handling reports.
  - User suspension, audit log inspection, and system metrics.
- **Deliverables**: Admin controllers, RBAC middleware, analytics views.

---

## 4. Current Repository State & Gap Analysis

The codebase currently possesses an extensive database foundation (171 tables) and a passing test suite (1,195 tests).

### What is Already Built & Operational:
- **Core Database Tables**: `users`, `user_profiles`, `friendships`, `user_followers`, `blocked_users`, `posts`, `media`, `comments`, `reactions`, `saved_items`, `notifications`, `reports`.
- **Auth Foundation**: `AuthService`, `LoginController`, `RegisterController`, `ForgotPasswordController`, `TwoFactorController`.
- **Testing Baseline**: 1,195 passing PHPUnit feature tests.

### Key Gaps to Close for Phase 1 Alignment:
1. **API v1 Verification Route Standard**:
   - Currently, `/api/v2/auth/verify-email` exists alongside web route `/email/verify/{id}/{hash}`. A dedicated, unified `/api/v1/auth/verify-email` endpoint must be registered and documented in the v1 API spec for web and mobile consistency.
2. **Inertia.js + Vue 3 Frontend Foundation**:
   - The repository currently renders Blade views with custom CSS. To support the requested Vue 3 + Inertia + TypeScript + Tailwind stack, Inertia Laravel adapter and Vue 3 dependencies should be installed in a non-disruptive manner without breaking existing working Blade interfaces.
3. **News Feed Block Filtering**:
   - In `FeedService`, block check filtering must be explicitly applied so that public posts from blocked or blocking users do not appear in the feed.

---

## 5. Risk Assessment & Mitigation

| Risk | Impact | Mitigation Strategy |
| :--- | :--- | :--- |
| **Breaking Existing 1,195 Tests** | High | Run targeted test suites before and after every modification. Follow existing conventions strictly. |
| **Frontend Disruption** | Medium | Maintain existing Blade views while establishing Inertia/Vue routes progressively. |
| **Cross-Client Parity** | High | Standardize all responses with consistent API Resources (`success`, `message`, `data`, `meta`). |
| **Performance Degradation on Feed** | High | Use cursor pagination exclusively, eager-load polymorphic relations, and apply composite indexes. |
