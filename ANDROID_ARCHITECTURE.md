# Connectly Android — System Architecture & Engineering Blueprint

**Document Version:** 1.0.0  
**Target Platform:** Android 8.0 (API 26) to Android 15 (API 35+)  
**Primary Language:** Kotlin 2.1+  
**UI Framework:** Jetpack Compose (100% declarative UI) + Material 3  
**Design Reference:** Connectly Mobile Suite (10 Core Screen Experience)

---

## 1. Architectural Principles & Paradigm

Connectly Android follows **Clean Architecture** combined with **MVI (Model-View-Intent) / Unidirectional Data Flow (UDF)** and a **Modularized Domain-Driven Design (DDD)**.

```
┌────────────────────────────────────────────────────────┐
│                   UI Layer (Presentation)              │
│  Jetpack Compose Screens + Components + ViewModels     │
│  StateFlow<UiState> ◄──────► UI Events (Intent)        │
└───────────────────────────┬────────────────────────────┘
                            │ (Dispatches UseCases)
┌───────────────────────────▼────────────────────────────┐
│                   Domain Layer                         │
│  Pure Kotlin Use Cases, Business Logic, Domain Models │
│  Repositories Interfaces (Contracts)                   │
└───────────────────────────┬────────────────────────────┘
                            │ (Implements Repositories)
┌───────────────────────────▼────────────────────────────┐
│                    Data Layer                          │
│  Repository Implementations, Offline Sync Engines      │
│  Remote Data Sources (Retrofit / WebSocket / WebRTC)   │
│  Local Data Sources (Room DB / EncryptedDataStore)     │
└────────────────────────────────────────────────────────┘
```

### Core Architecture Tenets
1. **Single Source of Truth (SSOT):** Local Room database acts as the single source of truth for all feed items, conversations, user profiles, and notifications. Network requests update the database, and the UI observes Room reactive streams (`Flow<T>`).
2. **Offline-First & Optimistic Updates:** User actions (liking a post, posting a comment, sending a message, reacting to stories) instantly update local state with an optimistic status. Background work queues or coroutines synchronize with the backend.
3. **Type-Safe Navigation:** Leveraging Jetpack Navigation Compose with Kotlin Serialization for compile-time safe navigation arguments.
4. **Resilient Real-Time Engine:** Dual real-time communication stack consisting of:
   - WebSocket / Pusher protocol for chat, live comments, typing indicators, and presence.
   - Native WebRTC for low-latency peer-to-peer 1-on-1 audio/video calls and group calls via LiveKit SFU.
5. **High-Performance Media Ingestion & Playback:** Google Media3 (ExoPlayer) with pre-caching, pre-warming, and buffer management for continuous vertical video (Reels) and horizontal Stories.

---

## 2. Multi-Module Project Structure

To ensure compile-time isolation, fast incremental builds, parallel CI builds, and feature independence, the project is structured as a multi-module Gradle project:

```
connectly-android/
├── app/                               # Main application entry point, App class, Navigation Host
├── core/
│   ├── common/                        # Dispatchers, Result wrapper, Extensions, DateTime utilities
│   ├── model/                         # Pure domain models (User, Post, Story, Reel, Message, etc.)
│   ├── network/                       # Retrofit, OkHttp, Authenticator, Interceptors, DTOs
│   ├── database/                      # Room Database, DAOs, Entities, TypeConverters
│   ├── datastore/                     # EncryptedDataStore for tokens, auth preferences, settings
│   ├── designsystem/                  # Connectly theme, Color tokens, Typography, Common Compose UI
│   ├── media/                         # Media3 ExoPlayer wrappers, Pre-cache manager, Image loaders
│   ├── webrtc/                        # WebRTC peer connection factory, ICE handling, LiveKit client
│   └── websocket/                     # Pusher / WebSocket client, channel subscriptions, event bus
└── feature/
    ├── auth/                          # Login, Register, 2FA, OTP, Forgot Password
    ├── feed/                          # Home feed (For You, Following, Groups, Pages), Post composer
    ├── stories/                       # Story tray, Full-screen Story viewer, Story camera creator
    ├── reels/                         # Full-screen vertical Reels viewer, interactions, music player
    ├── groups/                        # Groups hub (Discover, Your Groups, Manage), Group feed, Polls
    ├── messenger/                     # Conversations list, 1-on-1 chat, group chat, 5-state messages
    ├── calling/                       # Incoming/Outgoing WebRTC audio/video call screens, PIP mode
    ├── profile/                       # User profile, verified badges, edit profile, user tabs
    ├── events/                        # Event discovery, My Events, Past, RSVP handling
    ├── live/                          # Live streaming viewer & broadcaster, real-time comment overlay
    ├── notifications/                 # Social alerts, friend request approvals, push notification handling
    └── settings/                      # Account, Privacy, Appearance (Dark/Light), Language, Block list
```

---

## 3. Technology Stack & Dependencies

| Layer / Concern | Technology | Justification |
| :--- | :--- | :--- |
| **Language** | Kotlin 2.1+ | Coroutines, Flow, Strict Null Safety, Context Receivers |
| **UI Toolkit** | Jetpack Compose 1.7+ | Modern declarative UI, Material 3, dynamic theme support |
| **Dependency Injection** | Dagger Hilt 2.52+ | Standard Android DI, compile-time validation, ViewModel integration |
| **Networking** | Retrofit 2.11 + OkHttp 4.12 | Industry standard HTTP client, connection pooling, interceptors |
| **Serialization** | Kotlinx Serialization | Fast, reflection-free JSON parsing directly targeting Kotlin models |
| **Local Database** | Room 2.6+ (SQLite) | Reactive queries via Kotlin `Flow`, migration support, SQLite cipher |
| **Preferences** | AndroidX DataStore | Asynchronous, transactional key-value & proto storage (replaces SharedPreferences) |
| **Image Loading** | Coil 3.0+ | Lightweight, Compose-first image loading with memory & disk cache |
| **Video Playback** | Google Media3 ExoPlayer 1.4+ | Low-latency HLS/MP4 playback, cache DataSource for Reels & Stories |
| **Real-Time WebSockets** | Pusher Java Client / OkHttp WS | Compatible with Laravel Reverb / Pusher broadcaster protocol |
| **WebRTC & Calling** | WebRTC Android Native + LiveKit SDK | STUN/TURN traversal, low-latency audio/video, echo cancellation |
| **Paging** | Paging 3 (Compose Integration) | Native infinite scrolling with RemoteMediator for offline cache |
| **Push Notifications** | Firebase Cloud Messaging (FCM) | Background notification alerts, high-priority call signaling wakes |
| **Security** | AndroidX Security Crypto | AES-256 GCM encrypted token storage via Android KeyStore |

---

## 4. UI Layer Architecture (MVI Pattern)

Every feature follows a strict Contract-driven MVI implementation:

```kotlin
// Example: FeedContract.kt
sealed interface FeedUiState {
    data object Loading : FeedUiState
    data class Success(
        val posts: List<PostItem>,
        val stories: List<StoryUserTray>,
        val currentTab: FeedTab,
        val isRefreshing: Boolean = false
    ) : FeedUiState
    data class Error(val message: String) : FeedUiState
}

sealed interface FeedUiIntent {
    data class ChangeTab(val tab: FeedTab) : FeedUiIntent
    data class LikePost(val postId: Long) : FeedUiIntent
    data class SharePost(val postId: Long) : FeedUiIntent
    data class DeletePost(val postId: Long) : FeedUiIntent
    data object Refresh : FeedUiIntent
}

sealed interface FeedUiEffect {
    data class ShowToast(val message: String) : FeedUiEffect
    data class NavigateToComments(val postId: Long) : FeedUiEffect
    data class NavigateToStory(val userId: Long) : FeedUiEffect
}
```

### ViewModel Implementation Pattern
```kotlin
@HiltViewModel
class FeedViewModel @Inject constructor(
    private val getFeedUseCase: GetFeedUseCase,
    private val togglePostReactionUseCase: TogglePostReactionUseCase,
    private val getStoriesTrayUseCase: GetStoriesTrayUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow<FeedUiState>(FeedUiState.Loading)
    val uiState: StateFlow<FeedUiState> = _uiState.asStateFlow()

    private val _effect = Channel<FeedUiEffect>(Channel.BUFFERED)
    val effect: Flow<FeedUiEffect> = _effect.receiveAsFlow()

    fun handleIntent(intent: FeedUiIntent) {
        when (intent) {
            is FeedUiIntent.ChangeTab -> selectTab(intent.tab)
            is FeedUiIntent.LikePost -> reactToPost(intent.postId)
            is FeedUiIntent.Refresh -> reloadFeed()
            // ...
        }
    }
}
```

---

## 5. Screen Breakdown & Navigation Graph

Based on the verified Connectly 10-screen design suite:

```
                             [NavHost]
                                │
   ┌──────────────┬─────────────┼──────────────┬──────────────┐
   ▼              ▼             ▼              ▼              ▼
[FeedTab]    [FriendsTab]  [CreatePost]   [ReelsTab]    [MessengerTab] ────► [SettingsMenu]
   │                                          │               │
   ├─► StoryViewer                            │               ├─► ChatThread (1-on-1)
   ├─► GroupDiscovery                         │               ├─► GroupChat
   ├─► EventsDiscovery                        │               └─► WebRTCCallScreen (Audio/Video)
   └─► UserProfileScreen                      │
              │                               │
              └─► Photo/Video/Reel Tabs       └─► LiveStreamingViewerScreen
```

### Top-Level Destinations (Bottom Bar)
1. **`HomeRoute` (Screen 1):** Top header with Connectly logo, Search, Notifications, Profile avatar. Sub-tabs: `For You`, `Following`, `Groups`, `Pages`. Horizontal Stories tray with `Add Story` + friends stories. Post creation prompt. Vertical post feed.
2. **`FriendsRoute` (Connections):** Friend requests, suggestions, all friends list.
3. **`CreateRoute` (+ Button):** Floating action bottom sheet: Create Post, Add Story, Create Reel, Go Live.
4. **`ReelsRoute` (Screen 3):** Full-screen vertical swipe Reels player with like, comment drawer, share sheet, audio disc.
5. **`MessengerRoute` (Screen 5):** Search, Active friends tray, filter pills (`All`, `Unread`, `Groups`), conversations list.
6. **`MenuRoute` (Screen 10):** User profile card, shortcuts to Groups, Pages, Events, Saved items, Settings list.

---

## 6. Offline-First Synchronization & Paging Flow

```
   [Remote API]
         ▲
         │ (Sync via RemoteMediator)
         ▼
 ┌───────────────┐
 │ Room Database │ ◄──── (Listens for updates) ──── [SyncWorker / WebSocket]
 └───────┬───────┘
         │
         ▼
   Flow<PagingData<PostEntity>>
         │
         ▼
 [FeedViewModel] ────► [Compose LazyColumn]
```

1. **Paging 3 RemoteMediator:** When the user scrolls near the end of the feed, `PostRemoteMediator` fetches the next page from `/api/v1/feed?page={page}`.
2. **Database Insertion:** New records are inserted into Room with transaction safety. The UI updates reactively through Room's `PagingSource`.
3. **Offline Reading:** When the user launches the app in offline mode (airplane mode / poor connectivity), Room immediately serves cached posts, stories, and conversations without blocking on a network spinner.
4. **Outbox Action Queue:** Liking a post or sending a message while offline writes an entry into `SyncPendingActionEntity`. A background `WorkManager` worker flushes pending actions as soon as network connectivity is restored.
