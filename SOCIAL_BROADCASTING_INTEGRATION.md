# ENGINE 4 — SOCIAL REAL-TIME SYNCHRONIZATION GUIDE

> **Engine:** Social Graph, Feed, Stories, Reels & Community Real-Time Sync  
> **Protocol:** Laravel Reverb (Pusher v7) + Reactive Kotlin Coroutines & StateFlow  
> **Status:** Fully Integrated & Verified

---

## 1. Overview & Capabilities

Engine 4 powers dynamic, live-updating social interactions across Connectly Android and JUGAJUG Web. Users see real-time updates to reactions, comments, stories, reels, follower counts, and community activity without refreshing the feed.

### Core Features:
- **Live Reaction Counters:** Increments and decrements reaction counts and breakdowns across all active viewers.
- **Real-Time Comments:** New comments and replies appear instantly in post comment bottom sheets.
- **Ephemeral Stories & Reels:** Live updates when contacts upload new stories or reels, plus instant expiration cleanup.
- **Friendship & Follow Graph:** Instant notifications for friend requests, acceptances, and follow counts.
- **Live Video Streaming:** Live comment streams, viewer counts, and gift animations.
- **Group & Page Real-Time Feeds:** Group announcements, polls, member admissions, and page inbox updates.

---

## 2. Event Specifications & Channel Routing

### 2.1 Post Reactions (`post.reaction.updated`)
- **Channel:** `post.{postId}` (Public Channel)
- **Event Name:** `post.reaction.updated`
- **Payload Schema:**
```json
{
  "event_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
  "post_id": 512,
  "total_reactions": 87,
  "breakdown": {
    "like": 62,
    "love": 18,
    "celebrate": 7
  },
  "timestamp": "2026-11-01T10:20:00Z"
}
```

### 2.2 Post Comments (`post.comment.updated`)
- **Channel:** `post.{postId}` (Public Channel)
- **Event Name:** `post.comment.updated`
- **Payload Schema:**
```json
{
  "event_id": "3a7f8b9c-1234-5678-abcd-ef0123456789",
  "post_id": 512,
  "page_id": null,
  "comment_id": 2048,
  "action": "created",
  "comment": {
    "id": 2048,
    "post_id": 512,
    "user": {
      "id": 43,
      "name": "Farhana Ahmed",
      "avatar": "https://bondhoo.edge-tec.com/storage/avatars/43.jpg"
    },
    "body": "Congratulations on the launch! Looks fantastic.",
    "created_at": "2026-11-01T10:20:05Z"
  },
  "comments_count": 14,
  "timestamp": "2026-11-01T10:20:05Z"
}
```

### 2.3 Friendship Graph Lifecycle (`friend.*`)
- **Channel:** `private-user.{userId}` (Private Channel)
- **Events:**
  - `friend.request.sent`
  - `friend.request.received`
  - `friend.request.accepted`
  - `friend.request.rejected`
  - `friend.removed`
  - `friend.followed`
  - `friend.unfollowed`
  - `friend.counters.updated`

---

## 3. Android Reactive UI State Integration

### 3.1 Jetpack Compose StateFlow Plumbing
In `FeedViewModel.kt` and `FeedScreen.kt`, incoming social events directly update the reactive state without resetting scroll position:

```kotlin
// In FeedViewModel.kt:
viewModelScope.launch {
    reverbWebSocketManager.postReactionFlow.collect { event ->
        _uiState.update { state ->
            val updatedPosts = state.posts.map { post ->
                if (post.id == event.postId) {
                    post.copy(reactionsCount = event.totalReactions)
                } else post
            }
            state.copy(posts = updatedPosts)
        }
    }
}
```

### 3.2 Battery & Network Optimization
To conserve battery and cellular data:
- `post.{postId}` channels are subscribed **lazily** only when a post is active in view or when the user opens the comment sheet.
- When the user scrolls past the post or closes the comment sheet, `unsubscribePostChannel(postId)` is invoked.
- Global channels like `stories` and `reels` remain active only while the app is in foreground.

---

## 4. Privacy & Authorization Rules

1. **No Sensitive Data on Public Channels:** Public channels (`post.{id}`, `stories`, `reels`) broadcast only non-sensitive metadata (counts, public usernames, timestamps).
2. **Strict Private Channel Guard:** All private interactions (friend requests, direct notifications, page admin actions) broadcast strictly on `private-user.{id}`, `page.{id}.inbox`, or `group.{id}` where membership is validated on the backend.

