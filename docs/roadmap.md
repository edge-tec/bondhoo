# Technical Roadmap & Architectural Evolution Plan

## 1. Roadmap Vision & Guiding Principles

The engineering roadmap for **Bondhoo (Jugajug)** outlines a structured, risk-mitigated progression from our current modular monolith foundation to an elastic, high-concurrency enterprise social networking ecosystem.

### Core Principles:
- **Zero-Downtime Migration**: Database schema evolutions and data backfills must be backward compatible and non-blocking.
- **Strict Verification Gate**: No feature phase moves to production without passing comprehensive PHPUnit feature tests and Laravel Pint code standards.
- **Explicit Approval for Major Architectural Changes**: Any change impacting core authentication guards, database engines, or infrastructure dependencies requires formal approval before execution.

---

## 2. Phased Evolution Roadmap

```
+---------------------------------------------------------------------------------------------------------+
| [COMPLETED] Phase 1: Core Foundation & Multi-Client Readiness                                           |
| - Authentication, Profiles, Posts, Media, Comments, Reactions, Social Graph, Newsfeed, Inertia Vue 3 UI  |
+----------------------------------------------------+----------------------------------------------------+
                                                     |
                                                     v
+---------------------------------------------------------------------------------------------------------+
| [PLANNED] Phase 2: High-Performance Caching & Database Hardening                                        |
| - Redis Cache-Aside for Feeds & Profiles, Query optimization, Database read-replicas, Asset CDN caching |
+----------------------------------------------------+----------------------------------------------------+
                                                     |
                                                     v
+---------------------------------------------------------------------------------------------------------+
| [PLANNED] Phase 3: Scaled Real-Time Communications & Messaging                                          |
| - WebSockets scale testing (Laravel Reverb cluster), SFU-backed group video calling, Messenger E2EE     |
+----------------------------------------------------+----------------------------------------------------+
                                                     |
                                                     v
+---------------------------------------------------------------------------------------------------------+
| [PLANNED] Phase 4: Automated Media Pipeline & AI Intelligence                                           |
| - Direct-to-S3 presigned multipart uploads, Asynchronous FFmpeg worker farm, Semantic Vector Search      |
+----------------------------------------------------+----------------------------------------------------+
                                                     |
                                                     v
+---------------------------------------------------------------------------------------------------------+
| [PLANNED] Phase 5: Mobile Ecosystem, PWA & ActivityPub Federation                                        |
| - Flutter Mobile API parity, Offline-first local sync protocol, ActivityPub federated actor support     |
+----------------------------------------------------+----------------------------------------------------+
                                                     |
                                                     v
+---------------------------------------------------------------------------------------------------------+
| [PLANNED] Phase 6: Enterprise Governance, Ads & Monetization Engine                                     |
| - Self-serve Ad auction engine, Creator payout wallet automation, Automated legal compliance audits    |
+---------------------------------------------------------------------------------------------------------+
```

---

## 3. Detailed Phase Breakdown

### Phase 1: Core Foundation & Multi-Client Readiness (Status: COMPLETED)
- **Delivered Milestones**:
  - Robust multi-guard authentication (`web`, `admin`, `sanctum`) with 2FA TOTP support.
  - Granular profile privacy policies and user profile customization.
  - UGC engine: Posts, polymorphic media attachments, reactions, and threaded comments.
  - Bidirectional social graph: Friend requests, mutual friend calculation, block isolation.
  - Chronological & engagement-weighted algorithmic feed (`FeedService`) with cursor pagination.
  - Modern, responsive Vue 3 / Inertia.js platform (`/app`, `/app/profile/{user}`, `/app/friends`, `/app/notifications`) with 3-column desktop layout and mobile bottom navigation.
  - Automated test suite: Over 1,280 tests verified and passing green.

---

### Phase 2: High-Performance Caching & Database Hardening (Target: Q1)
- **Objectives**:
  - Implement Redis Cache-Aside layer for hot social profiles and friendship status queries.
  - Transition feed fanout from on-read calculation to a hybrid model: Redis timeline caches for active users (`Fanout-on-Write`).
  - Configure MySQL 8 Read/Write replica splitting in `config/database.php` (`read` vs `write` hosts).
  - Enable Cloudflare / CloudFront CDN edge caching for static assets, avatars, and media thumbnails.
- **Key Deliverables**:
  - `TimelineCacheService`: Manages Redis sorted sets (`ZADD`, `ZREVRANGEBYSCORE`) for instant feed rendering (< 50ms).
  - Database index audit using `EXPLAIN ANALYZE` on queries with > 100k rows.
- **Verification**: Load testing with K6 simulating 10,000 concurrent feed readers.

---

### Phase 3: Scaled Real-Time Communications & Messaging (Target: Q2)
- **Objectives**:
  - Production benchmark and hardening of **Laravel Reverb** WebSocket server under sustained socket connections.
  - Upgrade WebRTC calling architecture from 1-on-1 peer mesh to an SFU (Selective Forwarding Unit) media bridge (e.g., LiveKit or Janus) for multi-party group audio/video calls.
  - Introduce optional Signal-protocol-based End-to-End Encryption (E2EE) for 1-on-1 private messaging.
- **Key Deliverables**:
  - `SfuSignalService`: Manages room tokens and SDP renegotiation for multi-participant rooms.
  - Messenger client-side key storage and message encryption/decryption hooks.
- **Verification**: Concurrency tests with 5,000 active WebSocket subscriptions and automated call disconnect/reconnect tests.

---

### Phase 4: Automated Media Pipeline & AI Intelligence (Target: Q3)
- **Objectives**:
  - Eliminate server memory exhaustion by implementing Direct-to-S3 Presigned URLs for file uploads.
  - Offload heavy video transcoding (H.264 / H.265 / DASH / HLS) to an isolated worker pool or AWS MediaConvert.
  - Integrate OpenAI / Gemini APIs via `AiPrompt` and `ai_usage_logs` for automated toxicity detection, spam moderation, and vector embeddings.
- **Key Deliverables**:
  - `PresignedUploadController`: Issues temporary S3 multipart upload signatures.
  - Worker daemon for FFmpeg adaptive bitrate stream generation.
  - Vector search integration via pgvector / MySQL 8 vector functions.

---

### Phase 5: Mobile Ecosystem, PWA & ActivityPub Federation (Target: Q4)
- **Objectives**:
  - Complete synchronization protocol for cross-platform Flutter clients (Android & iOS).
  - Progressive Web App (PWA) offline support using service workers and IndexedDB cache for messages.
  - Implement ActivityPub W3C standard (`federated_actors`, `federated_activities`) enabling users to follow and interact with users on Mastodon and Bluesky.
- **Key Deliverables**:
  - PWA manifest, service worker lifecycle, and background sync.
  - WebFinger and ActivityPub outbox/inbox endpoints.

---

### Phase 6: Enterprise Governance, Ads & Monetization Engine (Target: Q4+)
- **Objectives**:
  - Self-serve targeted advertising auction platform (`ad_campaigns`) with budget capping and real-time impression tracking.
  - Creator tipping and digital subscriptions with automated wallet payouts (`wallets`, `creator_earnings`).
  - Comprehensive administrative compliance reports, GDPR/privacy data export, and account deletion workflows.

---

## 4. Architectural Risk Matrix & Decisions Requiring Approval

The following architectural transitions carry significant system-wide impact and **strictly require explicit stakeholder approval** prior to initiation:

| Decision / Proposal | Potential Impact & Risks | Recommended Mitigation | Approval Status |
| :--- | :--- | :--- | :---: |
| **1. Database Engine Migration (SQLite to MySQL 8 / PostgreSQL)** | Migration syntax differences, locking semantics, and index tuning differences between development SQLite and production RDBMS. | Run dual-database automated CI pipelines. All migration scripts must be strictly dialect-agnostic. | **Requires Approval** |
| **2. Feed Fanout-on-Write (Redis Timeline Pre-computation)** | Redis memory consumption will increase linearly with active user count and post frequency. High-profile "celebrity" accounts with > 100k followers will cause fanout write spikes. | Implement a hybrid approach: Fanout-on-write only for accounts with < 5,000 followers; Fanout-on-read for high-follower accounts. | **Requires Approval** |
| **3. Direct-to-S3 Upload Transition** | Bypasses backend server validation until S3 webhook fires; requires CORS configuration on storage buckets. | Issue short-lived presigned URLs (15 mins) with strict `content-length` and `content-type` headers enforced in the signature. | **Requires Approval** |
| **4. Group WebRTC SFU Server Deployment** | Introduces an external stateful media server (e.g., LiveKit) into infrastructure deployment, requiring dedicated UDP port management. | Deploy LiveKit as an independent Docker container cluster with TURN/STUN fallback. | **Requires Approval** |
| **5. Deprecating Legacy Blade Views in Favor of Inertia** | Potential temporary UX disruption for administrative users accustomed to legacy Blade dashboards. | Maintain Blade admin routes (`/admin/*`) intact while incrementally rolling out Inertia modules for user-facing features. | **Requires Approval** |
