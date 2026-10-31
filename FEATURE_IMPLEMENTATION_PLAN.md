# Connectly Android — Feature Implementation Plan & Roadmap

**Milestone Target:** Production-Ready Enterprise Social Network for Android  
**Architecture Baseline:** Clean Architecture + MVI + Kotlin Coroutines & Flow + Jetpack Compose  
**Backend Compatibility:** Verified against Laravel 12 Enterprise Backend

---

## 1. Phased Execution Roadmap Overview

```
[Phase 1: Foundation & Design System]
           │
           ▼
[Phase 2: Authentication & Security]
           │
           ▼
[Phase 3: Home Feed & Stories Tray] ◄──► [Phase 4: Reels Video Player]
           │                                       │
           ▼                                       ▼
[Phase 5: Messenger & 5-State Chat]  ◄──► [Phase 6: WebRTC Audio/Video Calling]
           │                                       │
           ▼                                       ▼
[Phase 7: Groups & Communities]      ◄──► [Phase 8: Profile & Social Graph]
           │                                       │
           ▼                                       ▼
[Phase 9: Live Streaming Viewer]     ◄──► [Phase 10: Events, Settings & Hardening]
```

---

## 2. Phase-by-Phase Deliverables & Technical Scope

### Phase 1: Core Foundation, Design System & Infrastructure
- **Objective:** Establish the multi-module project scaffold, dependency injection, networking client, Room database, and the Connectly design system.
- **Key Deliverables:**
  1. Multi-module Gradle configuration with version catalogs (`libs.versions.toml`).
  2. Dagger Hilt dependency injection setup across all modules.
  3. `core:network`: OkHttp 4 client with `AuthInterceptor`, `TokenAuthenticator` (auto-refresh on 401), and Retrofit 2 with Kotlinx Serialization.
  4. `core:database`: Room database v1 schema with DAOs and TypeConverters.
  5. `core:designsystem`:
     - Connectly Brand Theme: Primary (`#1877F2`), Secondary (`#7C3AED`), Backgrounds (Light `#F0F2F5` / Dark `#0F172A`), Surface cards (`#FFFFFF` / `#1E293B`).
     - Typography (Instrument Sans font family tokens).
     - Component library: `ConnectlyTopBar`, `ConnectlyBottomBar`, `ConnectlyAvatar`, `ConnectlyButton`, `ConnectlyBadge`.
- **Validation Criteria:** Successful compilation, zero lint warnings, unit test coverage on interceptors and DAOs.

---

### Phase 2: Authentication, Security & Biometrics
- **Objective:** Secure, seamless user onboarding and session lifecycle.
- **Key Deliverables:**
  1. Login Screen: Email/Username + Password, "Remember Me", Forgot Password link.
  2. Registration Screen: Name, Email, Password, Terms acceptance.
  3. Two-Factor Authentication (2FA) Challenge Screen: 6-digit TOTP input with auto-advance.
  4. Phone OTP Login: Mobile number entry + SMS code verification.
  5. `core:datastore`: AES-256 encrypted token persistence using Android KeyStore.
  6. BiometricPrompt integration for fast, secure app unlocking.
- **Validation Criteria:** Integration test for complete auth flow, token refresh cycle, and session revocation.

---

### Phase 3: Home Feed & Stories Tray (Screen 1 & Screen 2)
- **Objective:** High-performance, offline-first news feed with horizontal stories tray and post interactions.
- **Key Deliverables:**
  1. Top App Bar: Brand logo, Search icon, Notification counter badge, user avatar.
  2. Filter Tabs: `For You`, `Following`, `Groups`, `Pages` with smooth indicator animation.
  3. Horizontal Stories Tray:
     - `Add Story` card with camera/gallery picker.
     - User story cards with unread gradient ring and name caption.
  4. Story Viewer (Screen 2):
     - Full-screen Media3 image/video pager.
     - Segmented progress bar timer (5s per image, video duration for videos).
     - Author header with timestamp and close button.
     - Reaction overlay (`❤️`, `👍`, `😂`) and direct message reply bar.
  5. Post Composer: "What's on your mind?", Photo, Video, Feeling buttons.
  6. Post Feed Card:
     - Header: Author avatar, name, verified badge, relative timestamp, privacy icon, overflow menu.
     - Body: Text with auto-link detection, single/multi-image mosaic grid, or embedded video.
     - Engagement Bar: Reactions summary (`2.4K`), Comments count (`248`), Share count.
     - Interactive Buttons: Animated Like button with long-press reaction picker, Comment button, Share button.
  7. Infinite Scrolling via `Paging 3` and `PostRemoteMediator` for seamless offline caching.
- **Validation Criteria:** 60fps scrolling with zero jank on mid-range devices; offline feed reading passes.

---

### Phase 4: Full-Screen Reels & Media Player (Screen 3)
- **Objective:** TikTok/Instagram-style vertical short video experience with low-latency playback.
- **Key Deliverables:**
  1. Vertical `VerticalPager` with Google Media3 `ExoPlayer`.
  2. Pre-caching & Pre-warming engine: Pre-buffers next 2 reels while current reel plays.
  3. Right Action Rail:
     - Like button with real-time counter (`12.5K`) and heart burst animation.
     - Comments button with counter (`567`) opening bottom sheet drawer.
     - Share button (`1.2K`) opening native share and internal forward sheet.
     - Spinning Vinyl Record audio disc animation.
  4. Creator Attribution: Creator avatar, name ("Music Vibes"), Follow/Following button, expandable caption.
  5. Audio Track Pill: "Original Audio" with marquee scroll.
  6. Playback Scrubber: Thin progress bar with drag-to-seek functionality.
- **Validation Criteria:** Reel playback starts within 250ms on swipe; memory leak test passes on 100 consecutive swipes.

---

### Phase 5: Messenger & Real-Time Chat (Screen 5)
- **Objective:** Next-generation enterprise messaging with 5-state delivery lifecycle.
- **Key Deliverables:**
  1. Messenger Home:
     - Search conversations input.
     - Filter pills: `All`, `Unread (3)`, `Groups`.
     - Active Contacts horizontal tray with real-time green online presence dots.
     - Conversation List: Avatar with online badge, recipient name, last message preview, timestamp, unread pill.
  2. Chat Thread:
     - Message bubble layout with bubble tail and timestamps.
     - **Full 5-State Delivery Lifecycle:**
       - `sending` (⌛ optimistic spinner).
       - `sent` (✓ single gray check).
       - `delivered` (✓✓ double gray check).
       - `read` (✓✓ double blue check).
       - `failed` (⚠️ red alert + tap to retry action).
     - Typing indicators ("Sadia Islam is typing...").
     - Audio message recorder with waveform preview.
     - Media attachments viewer and camera quick-send.
  3. Offline Outbox & Sync Catchup:
     - Outbox queue persists unsent messages in Room.
     - Automatic catchup via `/api/v1/messenger/sync` upon reconnection.
- **Validation Criteria:** End-to-end messaging test over WebSocket; offline message queue delivers on network restore.

---

### Phase 6: WebRTC Audio/Video Calling
- **Objective:** Crystal-clear 1-on-1 and group calling with low-latency media and background alerts.
- **Key Deliverables:**
  1. Dynamic ICE Server configuration fetching from `/api/v1/calls/ice-servers`.
  2. WebRTC Native Engine initialization with hardware hardware-accelerated VP8/H.264 codecs.
  3. Outgoing Call Screen: Callee avatar, pulsating ring wave, call status ("Ringing..."), End Call button.
  4. Incoming Call Screen: Full-screen intent with sound/vibration, Accept (Green) and Decline (Red) swipe actions.
  5. Active In-Call Controls: Mute Microphone, Toggle Camera, Flip Camera, Speakerphone, Screen Share, Floating PIP.
  6. Background Wake: FCM high-priority data messages wake the device even when app is killed.
- **Validation Criteria:** PeerConnection establishes within 1.5 seconds; 100% incoming call ring success rate.

---

### Phase 7: Groups & Communities Hub (Screen 4)
- **Objective:** Community building and discovery platform.
- **Key Deliverables:**
  1. Navigation Tabs: `Discover`, `Your Groups`, `Manage`.
  2. Hero Featured Group Card: Full-width banner, Group title ("Travel Lovers BD"), Member count ("248K members"), Join CTA.
  3. 2x2 Popular Groups Grid: Cards with cover, avatar, title, member count, and instant Join button.
  4. Group Details & Feed: Announcements banner, Group Rules, Member directory, Moderation queue (for admins).
  5. Group Post Creation & Interactive Polls voting.
- **Validation Criteria:** Join/leave updates update Room cache optimistically; group feed pagination verified.

---

### Phase 8: Profile & Social Graph (Screen 6)
- **Objective:** Comprehensive user identity and social connection management.
- **Key Deliverables:**
  1. Header: Full-bleed Cover photo, Profile photo with Blue Verified Badge.
  2. Name & Bio: User display name, bio string ("Dream Big • Work Hard • Be Happy").
  3. Social Metrics: `2.5K Friends`, `865 Followers`, `128 Following` with interactive dialogs.
  4. Action Buttons: `Edit Profile` (for owner) or `Add Friend` / `Message` (for other users), Overflow `...` menu.
  5. Content Tabs: `Posts`, `Photos`, `Videos`, `Reels` with sticky tab row.
  6. Tab View Pager: Embedded posts feed, photo album grid, reels grid.
- **Validation Criteria:** Profile updates persist to backend; verified badge renders correctly based on API flag.

---

### Phase 9: Live Streaming Viewer & Broadcaster (Screen 8)
- **Objective:** Interactive live video broadcasting and audience engagement.
- **Key Deliverables:**
  1. Broadcaster Camera Engine: CameraX + RTMP/WebRTC publisher.
  2. Viewer Screen:
     - ExoPlayer HLS/WebRTC live player with sub-second latency.
     - Top Bar: `LIVE` red badge, Viewer counter (`4.8K`), Host info, Close button.
     - Real-Time Chat Overlay: Semi-transparent scrolling list of user comments.
     - Floating Reaction Bursts: Multi-colored hearts and emojis floating upward from bottom right.
     - Bottom Interaction Bar: "Add a comment..." text field, Gift button, Heart tap button.
  3. Virtual Gifts Modal: Selection sheet displaying available gifts and coin balances.
- **Validation Criteria:** 1,000 simulated comments/min without frame drops or UI thread freezing.

---

### Phase 10: Events, Notifications, Settings & Final Hardening (Screens 7, 9, 10)
- **Objective:** Complete remaining utility features, notifications, settings, and release certification.
- **Key Deliverables:**
  1. Events Hub (Screen 7): `Discover`, `My Events`, `Past` tabs; Event cards with Date badge, Title, Venue, `Interested` & `Going` buttons.
  2. Notifications (Screen 9): `All`, `Likes`, `Comments`, `Follows` tabs; Friend request accept/reject actions.
  3. Settings (Screen 10): Profile card, Account Settings, Privacy & Security, Notification switches, Theme toggle (Light/Dark), Language picker, Logout.
  4. App Security & Release Build: ProGuard/R8 obfuscation, certificate pinning, APK/AAB bundle signing.
- **Validation Criteria:** Macrobenchmark startup under 800ms; zero crash rate across Android 8 through Android 15.
