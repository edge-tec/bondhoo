# CONNECTLY & JUGAJUG — UNIFIED ENTERPRISE REAL-TIME ARCHITECTURE

> **Document Version:** 1.0.0  
> **Status:** Production Ready  
> **Author:** Antigravity Senior Distributed Systems & Mobile Engineering Team  
> **Target Platforms:** Connectly Android (Kotlin, Jetpack Compose, Coroutines/Flow) & JUGAJUG Website (Laravel 13, Reverb, MySQL 8, Redis)

---

## 1. Executive Summary

Connectly Real-Time Infrastructure unites **Laravel Reverb**, **WebRTC PeerConnection**, **Firebase Cloud Messaging (FCM)**, and **Redis Pub/Sub** into a cohesive, high-performance, and resilient synchronization engine. 

The architecture guarantees:
1. **Single Connection Topology:** Android client establishes and manages exactly **one** persistent WebSocket connection to Laravel Reverb across all features (Messaging, Calling, Feed, Notifications, Social Graph).
2. **Authoritative Backend Security:** Laravel remains the single source of truth for authorization, persistence, and state transitions. WebSocket events are treated as non-blocking delivery accelerators rather than sole data conduits.
3. **Graceful Degraded Mode & Fallback:** Any WebSocket disconnect or network transition seamlessly falls back to idempotent REST polling and offline Room cache synchronization without user-visible error cascades.
4. **Cross-Platform Parity:** Bidirectional real-time interoperability between **Android to Android**, **Android to Web**, and **Web to Android**.

```
+-----------------------------------------------------------------------------------+
|                            CONNECTLY REAL-TIME TOPOLOGY                           |
+-----------------------------------------------------------------------------------+
|                                                                                   |
|  [ Connectly Android Client ]                              [ JUGAJUG Web Client ] |
|  - Jetpack Compose / MVVM                                  - Blade / Inertia / JS |
|  - Room Local Cache (Offline)                              - Pusher-JS / Echo     |
|         |                                                           |             |
|         +-----------------------------+-----------------------------+             |
|                                       |                                           |
|                           WSS (Pusher Protocol v7)                                |
|                                       v                                           |
|                        +-----------------------------+                            |
|                        |    Laravel Reverb Cluster   |                            |
|                        |    (Port 8080 / SSL 443)    |                            |
|                        +-----------------------------+                            |
|                                       |                                           |
|                        Redis Pub/Sub & Event Drivers                              |
|                                       v                                           |
|                        +-----------------------------+                            |
|                        |   JUGAJUG Laravel Backend   |                            |
|                        |   (Auth, Sanctum, Policies) |                            |
|                        +-----------------------------+                            |
|                               |               |                                   |
|                +--------------+               +---------------+                   |
|                v                                              v                   |
|       +-----------------+                            +-----------------+          |
|       |  MySQL Database |                            |   Firebase FCM  |          |
|       |  (Persistence)  |                            |   (VoIP & Data) |          |
|       +-----------------+                            +-----------------+          |
+-----------------------------------------------------------------------------------+
```

---

## 2. Core Architecture Modules

The Android client architecture is divided into decoupled, modular components located under `com.connectly.android.core.websocket.unified`:

| Component | Responsibility | Key Collaborators |
| :--- | :--- | :--- |
| **`ConnectionManager`** | Manages WebSocket lifecycle, ping/pong keepalive, exponential backoff (1s to 30s), and Pusher v7 handshake. | `OkHttpClient`, `TokenStorageManager` |
| **`ChannelSubscriptionManager`** | Manages channel registration, token authorization via `/api/v1/broadcasting/auth`, and automatic re-subscription on reconnect. | `ConnectlyApi`, `ReverbWebSocketManager` |
| **`RealtimeEventDispatcher`** | Parses raw Pusher JSON frames, deduplicates via LRU cache, and dispatches to strongly-typed Kotlin `SharedFlow` streams. | `ReverbWebSocketManager` |
| **`MessageSyncManager`** | Manages local Room persistence, gap detection (`/api/v1/messenger/sync?since_id=`), and delivery/read receipts. | `MessageDao`, `ConversationDao`, `ConnectlyApi` |
| **`CallSessionManager`** | Coordinates WebRTC SDP signaling, ICE candidate exchange, ICE restart, and participant state updates. | `WebRtcEngine`, `WebRtcSignalingCoordinator` |
| **`PushNotificationManager`** | Integrates device registration (`/api/v1/devices/register`), token rotation, high-priority VoIP incoming call alerts, and deep links. | `DeviceTokenManager`, `NotificationChannelManager` |
| **`PresenceManager`** | Aggregates online/offline statuses and last seen timestamps of contacts and conversation participants. | `ReverbWebSocketManager` |
| **`NetworkConnectivityMonitor`** | Tracks Android network availability, cellular/WiFi handovers, and triggers reconnects or ICE restarts. | `ConnectivityManager.NetworkCallback` |

---

## 3. WebSocket Connection Lifecycle & Pusher Protocol v7

### 3.1 Handshake Sequence
1. **URL Endpoint:** `wss://bondhoo.edge-tec.com/app/connectly_reverb_key?protocol=7&client=android&version=1.0.0`
2. **Initial Event Received:**
   ```json
   {
     "event": "pusher:connection_established",
     "data": "{\"socket_id\":\"123456.789012\",\"activity_timeout\":30}"
   }
   ```
3. **Heartbeat Maintenance:** Server emits `pusher:ping`. Client immediately replies with `{"event":"pusher:pong","data":{}}`. If no ping is received within 60s, connection is terminated and re-established.
4. **Exponential Backoff:** Reconnection uses backoff formula:  
   $$\text{delay} = \min(1000 \times 2^{\min(\text{attempt}, 5)}, 30000)\text{ ms}$$
   With jitter protection to prevent thundering herd spikes against the Reverb cluster.

---

## 4. Channel Topology & Security Matrix

All private and presence channels require authentication via Sanctum API bearer token at `POST /api/v1/broadcasting/auth`.

| Channel Pattern | Type | Authorization Logic | Broadcast Events |
| :--- | :--- | :--- | :--- |
| `private-user.{userId}` | Private | Authenticated user ID matches `{userId}`. | `notification.new`, `notification.read`, `friend.*`, `call.incoming`, `call.accepted`, `call.rejected`, `call.ended`, `call.signal` |
| `private-conversation.{convId}` | Private | User is active participant in `conversation_participants`. | `message.new`, `message.updated`, `message.deleted`, `message.delivered`, `message.read`, `message.reaction`, `user.typing`, `conversation.cleared`, `call.*` |
| `presence-users` | Presence | Authenticated user in Sanctum. Shared presence list with `{id, name, avatar}`. | `user.presence` (online, offline, last seen) |
| `post.{postId}` | Public | Anyone (public post) or authenticated user. | `post.reaction.updated`, `post.comment.updated` |
| `page.{pageId}.feed` | Public | Anyone with page read permissions. | `page.post.published` |
| `stories` | Public | Active followers or public platform. | `story.created`, `story.deleted`, `story.expired` |
| `reels` & `reel.{reelId}` | Public | Public reels feed / individual reel stream. | `reel.created`, `reel.reaction.updated`, `reel.comment.created`, `reel.deleted` |
| `live.{channelId}` | Public | Viewers of active live broadcast. | `live.started`, `live.ended`, `live.comment.received`, `gift.received` |
| `group.{groupId}` | Private | User is member of group in `group_members`. | `group.post.created`, `group.announcement.published`, `group.poll.voted`, `group.member.joined` |

---

## 5. Deduplication & Idempotency Engine

To eliminate duplicated UI cards or multiple sound alerts when receiving events via both WebSocket and push notifications, the client maintains a synchronized **Least Recently Used (LRU)** deduplication memory set (1,000 entries):

$$\text{EventKey} = \begin{cases} 
\text{"new\_"} + \text{message.id} & \text{if server message ID} \\ 
\text{client\_message\_id} & \text{if client generated UUID} \\
\text{"notif\_"} + \text{notification.id} & \text{if notification} \\
\text{callId} + ":" + \text{signalType} + ":" + \text{senderId} & \text{if WebRTC signal}
\end{cases}$$

If `processedEventKeys.contains(EventKey)` returns true, the frame is silently dropped before dispatching to ViewModel StateFlows.

---

## 6. Offline Queuing & Message Recovery

1. **Optimistic Insertion:** Messages composed offline are immediately committed to Room with `deliveryStatus = SENDING`.
2. **Network Recovery:** `NetworkConnectivityMonitor` detects internet restoration and triggers `MessageSyncManager.syncMissedEvents()`.
3. **Gap Resolution:** Client calls `POST /api/v1/messenger/sync?since_id={lastKnownEventId}`. Any events that occurred while offline are ingested, applied in strict timestamp order, and persisted.
4. **Idempotent Dispatch:** Pending local messages with client UUIDs are submitted via `POST /api/v1/conversations/{id}/messages`. The backend uses MySQL unique constraint on `client_message_id` to guarantee zero duplication.

