# Enterprise System Architecture: Bondhoo (Jugajug)

## 1. Executive Summary & Architectural Philosophy

Bondhoo (Jugajug) is engineered as an enterprise-grade, high-throughput social networking platform built on top of **Laravel 13** and **PHP 8.5**. 

The architecture follows a **Modular Monolith** pattern with explicit Domain-Driven Design (DDD) layers. This design choice provides several distinct advantages:
- **Zero Network Overhead**: Eliminates inter-service network hops and serialized JSON latency found in fragmented microservice deployments.
- **Strict Data Consistency**: Guarantees ACID transactional integrity across multi-entity operations (e.g., friendship acceptance, post deletion with media cascade, and notifications).
- **Domain Decoupling**: Business logic is encapsulated in isolated domain services (`App\Services`), shielded from HTTP controllers and database persistence details.
- **Dual Client Support**: Serves both a state-of-the-art **Vue 3 / Inertia.js** web application and a stateless **JSON REST API (`/api/v1` and `/api/v2`)** for mobile (Flutter Android/iOS) and third-party integrations.

---

## 2. System Architecture Diagram

```
+----------------------------------------------------------------------------------------------------+
|                                           CLIENT LAYER                                             |
|   +---------------------------------------------+    +------------------------------------------+  |
|   |   Web Client (Vue 3 + Inertia.js + TS)      |    |      Mobile Apps (Flutter Cross-Platform)|  |
|   |      Tailwind CSS v4 + Lucide Icons         |    |            Android & iOS                 |  |
|   +---------------------------------------------+    +------------------------------------------+  |
+--------------------------------------------------+-------------------------------------------------+
                                                   | HTTP(S) & WebSockets (WSS)
                                                   v
+----------------------------------------------------------------------------------------------------+
|                                    GATEWAY & INGRESS LAYER                                         |
|  - Cloudflare / Reverse Proxy (WAF, DDoS Mitigation, SSL Termination)                               |
|  - Rate Limiting (`EnterpriseRateLimiterService`, IP & User throttling)                             |
|  - Global Middleware: `SecurityHeadersMiddleware`, `CorrelationIdMiddleware`, `ActiveUserMiddleware` |
|  - Cookie Handling: State-encrypted cookies + exceptions (`jugajug_token`, `bondhoo_token`)        |
+--------------------------------------------------+-------------------------------------------------+
                                                   |
                                                   v
+----------------------------------------------------------------------------------------------------+
|                                 APPLICATION & PRESENTATION LAYER                                   |
|  +---------------------------------------+  +---------------------------------------------------+  |
|  |           Web Routes (/app, /)        |  |               API Routes (/api/v1, /api/v2)       |  |
|  | - SocialWebController (Inertia SSR/SPA)|  | - 35+ Api Controllers (JsonResource Formatted)     |  |
|  | - Blade Fallbacks (Legacy Dashboard)  |  | - Form Requests (Strict server validation)        |  |
|  +---------------------------------------+  +---------------------------------------------------+  |
|                                                  |                                                 |
|  +-----------------------------------------------+-----------------------------------------------+  |
|  |                             AUTHORIZATION & POLICY ENGINE                                      |  |
|  |  UserPolicy, PostPolicy, CommentPolicy, PagePolicy, GroupPolicy, ProfilePrivacyService        |  |
|  +-----------------------------------------------------------------------------------------------+  |
+--------------------------------------------------+-------------------------------------------------+
                                                   |
                                                   v
+----------------------------------------------------------------------------------------------------+
|                                    DOMAIN & SERVICE LAYER                                          |
|  Encapsulated Business Logic, Invariants, and Transactions:                                         |
|  - `AuthService` / `AuthServiceV2` (Registration, Login, Multi-Device Sessions, Two-Factor TOTP)   |
|  - `FeedService` (Smart engagement-weighted algorithmic ranking & Reverse-chronological feeds)     |
|  - `PostService`, `CommentService`, `ReactionService` (UGC management & counter triggers)           |
|  - `FriendshipService` (Bidirectional social graph, mutual calculations, friend suggestions)       |
|  - `NotificationService` (Multi-channel notification routing, preference matrices, unread counts)  |
|  - `MessengerService` (Realtime encrypted chat, sync events, attachments, message tombstones)      |
|  - `LiveStreamingService`, `ReelEnterpriseService`, `StoryEnterpriseService`                       |
+--------------------------------------------------+-------------------------------------------------+
                         |                                           |
                         v                                           v
+----------------------------------------+       +---------------------------------------------------+
|            DATA STORAGE LAYER          |       |         ASYNC QUEUE & REAL-TIME PIPELINE          |
|  - Relational Database (155+ Tables):  |       |  - Redis 7+ (Predis Driver):                      |
|    - MySQL 8.0+ / SQLite (Dev)         |       |    - Distributed Cache & State Caching            |
|    - 88 Migrations, Foreign Key Cascades|       |    - Laravel Horizon Queue Worker Orchestration   |
|  - Object Storage (S3 / MinIO):        |       |  - Event Broadcasting & WebSockets:               |
|    - Flysystem AWS S3 driver           |       |    - Laravel Reverb / Pusher Protocol (7.3)       |
|    - Normalized media URLs             |       |    - 10+ Private/Presence WebSocket Channels      |
|  - Vector Embeddings (Search & AI)     |       |    - WebRTC Mesh/SFU Calling Signaling           |
+----------------------------------------+       +---------------------------------------------------+
```

---

## 3. Layer Breakdown & Component Responsibilities

### 3.1 HTTP Gateway & Routing Layer
- **`bootstrap/app.php`**: The unified application configuration point for Laravel 13.
  - Registers Web (`routes/web.php`), API (`routes/api.php`), Commands (`routes/console.php`), Channels (`routes/channels.php`), and Health endpoints (`/up`).
  - Configures the middleware pipeline, stateful API middleware, and custom aliases.
- **Dual Ingress Routes**:
  - **Inertia Web Routes (`/app/*`)**: Renders modern Vue 3 pages (`Feed`, `Profile`, `Friends`, `Notifications`) via [`SocialWebController`](file:///Users/mizanurrahman/claude/jugajug/app/Http/Controllers/Web/SocialWebController.php).
  - **API v1 / v2 Routes (`/api/v1/*`, `/api/v2/*`)**: High-throughput REST API serving JSON endpoints with standardized resource schemas.
  - **Legacy Blade Routes (`/`)**: Maintained for administrative dashboard and backward compatibility without code disruption.

### 3.2 Security, Identity & Authentication Architecture
- **Multi-Guard Setup (`config/auth.php`)**:
  - `web` Guard: State-managed sessions with `remember_token` and sliding expiration.
  - `admin` Guard: Dedicated session-based administrator authentication isolated from standard user sessions.
  - `sanctum` Guard: Cryptographic Bearer token management with custom capabilities (`jugajug_token`, `bondhoo_token`).
- **Security Hardening**:
  - **Two-Factor Authentication (2FA)**: RFC 6238 TOTP with encrypted secrets (`two_factor_secret`) and emergency recovery codes (`recovery_codes`).
  - **Device Session Management**: Device fingerprinting (`user_sessions`, `device_sessions`, `trusted_devices`) with remote revocation (`/devices/logout-all`).
  - **Account Lockout & Protection**: Automatic brute-force throttling (`failed_login_attempts`, `locked_until`).
  - **HTTP Security Headers**: `Strict-Transport-Security`, `X-Content-Type-Options`, `X-Frame-Options`, `Content-Security-Policy`.

### 3.3 Business Logic: The Domain Service Layer
The application strictly avoids fat controllers by delegating all operations to dedicated domain services:

| Domain Service | File Path | Core Responsibilities |
| :--- | :--- | :--- |
| **`FeedService`** | [`app/Services/FeedService.php`](file:///Users/mizanurrahman/claude/jugajug/app/Services/FeedService.php) | Cursor pagination (`CursorPaginator`), smart ranking algorithm `((likes*2)+(comments*3)+(shares*5))`, audience privacy filtering (`public`, `friends`, `followers`, `only_me`), block list isolation. |
| **`FriendshipService`** | [`app/Services/FriendshipService.php`](file:///Users/mizanurrahman/claude/jugajug/app/Services/FriendshipService.php) | Concurrency-safe transactions (`DB::transaction`) for friend requests, accept, decline, block/unblock, mutual calculations, and friend suggestions. |
| **`PostService`** | [`app/Services/PostService.php`](file:///Users/mizanurrahman/claude/jugajug/app/Services/PostService.php) | Post authoring, draft conversion, scheduling, media attachment linking, mentions, hashtags, and atomic counter updates. |
| **`NotificationService`** | [`app/Services/NotificationService.php`](file:///Users/mizanurrahman/claude/jugajug/app/Services/NotificationService.php) | Database notification persistence, unread counters, category filtering, real-time dispatch via queue jobs. |
| **`MessengerService`** | [`app/Services/MessengerService.php`](file:///Users/mizanurrahman/claude/jugajug/app/Services/MessengerService.php) | Direct and group conversations, message encryption, tombstone soft-deletion, sync tokens (`MessengerSyncEvent`), and read receipts. |
| **`MediaStorageService`** | [`app/Services/MediaStorageService.php`](file:///Users/mizanurrahman/claude/jugajug/app/Services/MediaStorageService.php) | Chunked resumable uploads (`media_upload_chunks`), mime-type validation, CDN URL normalization, and storage quota enforcement. |

### 3.4 Asynchronous Queue & Horizon Architecture
- **Queue Engine**: Redis queue driver managed by **Laravel Horizon** (`laravel/horizon`).
- **Critical Queue Jobs**:
  - `DispatchNotificationJob`: Handles high-priority notification fanouts without blocking HTTP threads.
  - `MediaProcessingJob`: Asynchronous image compression, aspect-ratio calculation, and video transcoding.
  - `EmailNotificationJob`: Transactional email delivery via enterprise SMTP.
  - `CacheWarmJob`: Rebuilding feed caches for active users during off-peak hours.

### 3.5 Real-Time Communication & WebSockets
- **Broadcasting Driver**: Pusher protocol compatible (configured for **Laravel Reverb**).
- **Channels Architecture ([`routes/channels.php`](file:///Users/mizanurrahman/claude/jugajug/routes/channels.php))**:
  - `user.{id}`: Private channel for user-specific alerts and background state changes.
  - `conversation.{conversationId}`: Private chat channel restricted to verified conversation participants.
  - `presence-users`: Presence channel broadcasting real-time online status and avatars.
  - `call.{conversationId}`: Real-time WebSockets signaling channel for WebRTC 1-on-1 audio/video calling.
  - `live.{channelId}` & `stream.{channelId}`: High-concurrency broadcast channels for live streaming video and interactive live comments/gifts.
  - `page.{pageId}.inbox` & `group.{groupId}`: Role-based presence channels for page managers and group members.

### 3.6 Frontend Architecture
- **Core Technologies**: Vue 3 (Composition API `<script setup lang="ts">`), Inertia.js 3, Tailwind CSS v4, TypeScript, Lucide Icons, Vite 8.
- **Design System Tokens**: Modern dark-mode compatible glassmorphism (`backdrop-blur-md`), vibrant gradients (`from-blue-600 to-indigo-600`), and fluid typography using Google Fonts (Plus Jakarta Sans).
- **Core Components ([`resources/js/Components/`](file:///Users/mizanurrahman/claude/jugajug/resources/js/Components/))**:
  - `Navbar.vue`: Global top bar and responsive bottom navigation.
  - `SidebarLeft.vue` & `SidebarRight.vue`: Desktop 3-column frame with friend requests and online contacts.
  - `PostComposer.vue`: Multi-media uploader with client previews and audience dropdown.
  - `PostCard.vue`: Animated emoji reactions, nested comments thread, and responsive image grid.
  - `PostSkeleton.vue` & `EmptyState.vue`: Loading states and user onboarding fallbacks.

---

## 4. Dependencies & Technology Stack Matrix

| Technology | Installed Version | Purpose |
| :--- | :--- | :--- |
| **PHP** | 8.5.6 | Primary backend language runtime |
| **Laravel Framework** | 13.32.0 | Core application framework |
| **Inertia.js Laravel** | 3.5.1 | Server-driven SPA adapter |
| **Laravel Sanctum** | 4.3.3 | API token & SPA cookie authentication |
| **Laravel Horizon** | 5.49.0 | Redis queue dashboard & worker management |
| **Predis** | 3.6.0 | High-performance Redis client |
| **Pusher PHP Server** | 7.3.0 | Real-time WebSocket broadcasting server driver |
| **Flysystem AWS S3** | 3.35.3 | Cloud object storage abstraction |
| **Vue** | 3.5.44 | Frontend reactive UI framework |
| **@inertiajs/vue3** | 3.9.1 | Vue 3 client-side Inertia integration |
| **Tailwind CSS** | 4.0.0 | Utility-first styling framework |
| **Vite** | 8.0.0 | High-speed frontend build tool and HMR |
| **TypeScript** | 5.x | Strict compile-time frontend type safety |
| **PHPUnit** | 12.5.35 | Automated backend unit and feature testing |
| **Laravel Pint** | 1.27.0 | Automated code formatting engine |

---

## 5. Architectural Risks & Bottleneck Analysis

| Risk Area | Severity | Description | Mitigation Strategy |
| :--- | :---: | :--- | :--- |
| **SQLite in Development vs MySQL 8 in Production** | High | SQLite does not support full row-level locking (`FOR UPDATE`) or native spatial types, which may cause subtle concurrency deviations during high-volume test loads. | Run integration and benchmark suites against a dedicated Docker MySQL 8 instance before staging deployments. |
| **WebSocket Connection Spikes** | High | High concurrent user counts on `presence-users` or viral live streams can exhaust server socket file descriptors. | Deploy Laravel Reverb behind Nginx with scaled worker processes, or offload live chat presence to Redis pub/sub. |
| **Polymorphic Table Bloat (`media`, `reactions`)** | Medium | As posts and comments scale to millions, `reactable_type` and `mediable_type` polymorphic queries can degrade query cache hit rates. | Maintain composite indexes on `(reactable_type, reactable_id, user_id)` and enforce archive partitioning for historical reactions. |
| **Media Bandwidth & Transcoding CPU Saturation** | High | Handling large 4K video uploads and thumbnail generation on application nodes will exhaust web server CPU and disk space. | Transition to Direct-to-S3 Presigned URLs and offload FFmpeg transcoding to dedicated serverless workers (AWS Lambda / Elastic Transcoder). |
| **Feed Fanout-on-Write vs Fanout-on-Read** | Medium | Current `FeedService` computes engagement scores on read (`Fanout-on-Read`). For users with thousands of friends, sorting and filtering large post sets will become slow. | Introduce a Redis-backed pre-computed timeline cache (`Fanout-on-Write`) for active users, falling back to database read for cold users. |
