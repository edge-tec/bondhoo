# System Architecture: Professional Social Network Platform

## 1. Architectural Philosophy: The Modular Monolith

The platform is engineered as a high-throughput, maintainable **Modular Monolith** built on the Laravel framework. This pattern is chosen over microservices to eliminate unnecessary network hop overhead, distributed transaction failures, and deployment complexity, while enforcing strict boundaries between domain responsibilities.

```
+-----------------------------------------------------------------------------------+
|                                  CLIENT LAYER                                     |
|  +---------------------------------------------+  +----------------------------+  |
|  |     Web Application (Vue 3 / Inertia)       |  |   Mobile Apps (Flutter)    |  |
|  |       TypeScript + Tailwind CSS             |  |      Android & iOS         |  |
|  +---------------------------------------------+  +----------------------------+  |
+---------------------------------------+-------------------------------------------+
                                        | HTTP / JSON & WebSockets
                                        v
+-----------------------------------------------------------------------------------+
|                                 APPLICATION GATEWAY                               |
|  - Rate Limiting (Throttle Middleware)            - CORS & Security Headers       |
|  - Laravel Sanctum Guard (Stateful Cookies / Bearer Tokens)                       |
+---------------------------------------+-------------------------------------------+
                                        |
                                        v
+-----------------------------------------------------------------------------------+
|                         MODULAR MONOLITH CORE (Laravel)                           |
|                                                                                   |
|  [Presentation Layer]                                                             |
|   -> Form Requests (Strict Server-Side Validation & Sanitization)                 |
|   -> Controllers (Thin HTTP boundary, returns API Resources)                      |
|                                                                                   |
|  [Security & Governance Layer]                                                    |
|   -> Authorization Policies (Fine-grained ability checks, IDOR prevention)        |
|                                                                                   |
|  [Domain / Business Logic Layer]                                                  |
|   -> Services & Actions (Atomic DB transactions, business invariants)             |
|   -> Domain Models (Normalized Eloquent models, casts, scopes, observers)         |
|                                                                                   |
|  [Asynchronous & Real-Time Pipeline]                                              |
|   -> Events & Listeners (Domain event dispatch)                                   |
|   -> Queue Jobs (Redis Horizon background tasks: media, emails, feed rebuilds)    |
|   -> Laravel Reverb (WebSocket server for Echo real-time broadcasts)              |
+---------------------------------------+-------------------------------------------+
                                        |
                     +------------------+------------------+
                     |                                     |
                     v                                     v
+--------------------------------------+  +-----------------------------------------+
|           DATA STORAGE               |  |          CACHE & REAL-TIME              |
|  - Relational Database (MySQL 8.0+)  |  |  - Redis (Sessions, Cache, Horizon)    |
|  - Object Storage (S3 / MinIO)       |  |  - Laravel Reverb (WebSockets)          |
+--------------------------------------+  +-----------------------------------------+
```

---

## 2. Layered Responsibilities & Code Organization

The application enforces a strict separation of concerns across well-defined architectural layers:

### 2.1 Presentation Layer: Controllers & Form Requests
- **Controllers** (`app/Http/Controllers/Api/v1/`):
  - Act strictly as HTTP coordinators.
  - Never contain raw SQL or business logic.
  - Rely on Form Requests for parameter validation and Services for domain execution.
  - Always return standardized JSON envelopes via Eloquent API Resources.
- **Form Requests** (`app/Http/Requests/`):
  - Encapsulate all incoming validation rules, regex constraints, and data type coercion.
  - Run before controller code is reached, preventing malformed or malicious inputs.

### 2.2 Security & Governance Layer: Policies
- **Authorization Policies** (`app/Policies/`):
  - Guard every mutative operation (create, view, update, delete) on domain models.
  - Validate ownership, privacy visibility, friend relationships, and block statuses.
  - Mitigate Insecure Direct Object References (IDOR).

### 2.3 Domain Layer: Services & Actions
- **Services** (`app/Services/`):
  - House all business rules, orchestration, and database transactions (`DB::transaction`).
  - Coordinate side-effects: firing domain events, dispatching background jobs, and invoking cache invalidators.
  - Reusable across web controllers, API endpoints, console commands, and future integrations.

### 2.4 Data Layer: Eloquent Models & Migrations
- **Models** (`app/Models/`):
  - Fully normalized database representations with explicit type-casting (PHP 8.5 typed properties).
  - Define explicit relationships (e.g. `BelongsTo`, `HasMany`, `MorphMany`).
  - Encapsulate local query scopes (e.g., `scopePublished()`, `scopeVisibleTo()`).

### 2.5 Transformation Layer: API Resources
- **API Resources** (`app/Http/Resources/`):
  - Transform internal database records into strict, immutable JSON contracts.
  - Prevent accidental leakage of sensitive attributes (password hashes, two-factor secrets, internal keys).
  - Guarantee contract stability for mobile (Flutter) and web (Vue 3 / Inertia) clients.

---

## 3. Shared Dual-Client Architecture

A central requirement is that both the **Vue 3 Web App** and the **Flutter Mobile App** consume the identical backend business logic.

```
                                  Unified Backend (/api/v1)
                                             |
                   +-------------------------+-------------------------+
                   |                                                   |
                   v                                                   v
         Web Frontend (Inertia.js)                           Mobile Client (Flutter)
- Protocol: SPA with HTTPS Session Cookies          - Protocol: REST with Sanctum Bearer Token
- State: Vue 3 Reactive Stores (Pinia/Composition)  - State: Bloc or Riverpod
- Styling: Tailwind CSS                             - UI: Material 3 / Cupertino Native Widgets
- Real-Time: Laravel Echo via Pusher-JS             - Real-Time: Pusher-Channels Flutter SDK
```

### 3.1 Web Application Architecture
- **Inertia.js + Vue 3 + TypeScript**:
  - Delivers a single-page application (SPA) experience with classic server-side routing advantages.
  - Leverages Laravel Sanctum's stateful cookie authentication for seamless CSRF protection and zero token management on web.
  - Tailwind CSS provides modern styling with responsive design and dark-mode tokens.

### 3.2 Mobile Application Architecture (Flutter)
- **Cross-Platform Android & iOS**:
  - Consumes `/api/v1` REST endpoints using an HTTP client (e.g., Dio).
  - Stores Sanctum plain-text Bearer tokens securely in iOS Keychain and Android Keystore (`flutter_secure_storage`).
  - Consumes WebSockets using the `pusher_channels_flutter` package configured for Laravel Reverb.

---

## 4. Security Architecture

Security is built into every architectural tier:

### 4.1 Authentication & Credential Safety
- **Password Storage**: Hashed using Bcrypt with cost factor 12 (`BCRYPT_ROUNDS=12`).
- **Token Security**: Tokens are generated via Laravel Sanctum, SHA-256 hashed in the database, and scoped with device names.
- **Two-Factor Authentication**: RFC 6238 TOTP with encrypted secrets and hashed recovery codes.

### 4.2 Rate Limiting & Brute Force Defense
- Multi-tier rate limiting using Laravel's Redis-backed `RateLimiter`:
  - `throttle:auth`: 5 attempts per minute per IP for login/registration.
  - `throttle:api`: 60 requests per minute per authenticated user.
  - Exponential backoff and account lockouts after consecutive failed attempts.

### 4.3 Injection & Tampering Defense
- **SQL Injection**: Prevented exclusively via PDO parameter binding in Eloquent and query builders.
- **Cross-Site Scripting (XSS)**: Strict escaping in Inertia/Vue; HTML tags stripped on user content inputs.
- **Cross-Site Request Forgery (CSRF)**: Enforced on all web state-modifying requests via Sanctum cookie handshakes.
- **Insecure Direct Object References (IDOR)**: Enforced via Model Policies on every route parameter (`$this->authorize('update', $post)`).

### 4.4 File Upload Security
- Multi-stage upload validation:
  1. Size limit enforcement (Max 10MB per image).
  2. MIME type whitelist: `image/jpeg`, `image/png`, `image/webp`.
  3. Magic byte (file signature) validation via PHP `finfo` or GD/Imagick inspection.
  4. Non-executable storage directory isolation on S3/MinIO.

---

## 5. Media Pipeline & Object Storage

Media files are handled asynchronously to preserve server responsiveness:

```
[Client] ---> 1. Direct/Chunked Upload ---> [MediaController]
                                                    |
                                      2. Save Original to S3/MinIO
                                                    |
                                      3. Dispatch ProcessMediaJob
                                                    |
                                                    v
                                            [Redis Horizon Queue]
                                                    |
                                     4. Generate Thumbnails (150x150)
                                     5. Generate Medium (600x600)
                                     6. Generate Large (1200x1200)
                                     7. Strip EXIF GPS metadata
                                     8. Update Media Record with URLs
```

- **Polymorphic Storage**: Media attaches to posts, comments, stories, profiles, and messages via `mediable_type` and `mediable_id`.
- **Private Media**: Presigned URLs with configurable TTL are issued for private attachments.

---

## 6. Real-Time Subsystem: Laravel Reverb

Real-time capabilities are provided by **Laravel Reverb**, a high-performance WebSocket server natively integrated with Laravel:

- **Broadcasting Driver**: `reverb`
- **Client Library**: Laravel Echo
- **Channel Taxonomy**:
  - `private-user.{userId}`: Personal alerts, friend requests, badge updates.
  - `private-post.{postId}`: Live comments, reaction counters.
  - `presence-chat.{conversationId}`: Real-time chat, typing indicators, active participant list.

---

## 7. Caching & Performance Architecture

High concurrency is supported by strategic Redis caching:

- **Query Optimization**:
  - Heavy relationship eager-loading (`with(['user.profile', 'media', 'reactions'])`).
  - Cursor pagination (`cursorPaginate()`) utilizing `id` and `created_at` indexes for O(1) page traversal regardless of depth.
- **Cache Tags & Invalidation**:
  - Feed queries and user profile summaries utilize Redis cache tags (e.g. `user:{id}:feed`, `user:{id}:profile`).
  - Model observers trigger atomic cache invalidations when posts are created, edited, deleted, or friendships change.
