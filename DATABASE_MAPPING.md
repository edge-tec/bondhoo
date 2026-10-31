# Connectly Android — Local Database Mapping & Schema Blueprint

**Local Storage Engine:** Room Database 2.6+ (SQLite with SQLCipher option)  
**Backend Source:** Laravel 12 MySQL 8 / PostgreSQL Database  
**Synchronization Paradigm:** Offline-First with Room as Single Source of Truth (SSOT)

---

## 1. Database Entity Architecture

The Android local database is designed to mirror the backend domain entities while optimizing for mobile query performance, infinite scrolling (Paging 3), and offline outbox queuing.

```
┌────────────────────────────────────────────────────────┐
│                   ConnectlyDatabase                    │
├────────────────────┬──────────────────┬────────────────┤
│    UserEntity      │    PostEntity    │   ReelEntity   │
├────────────────────┼──────────────────┼────────────────┤
│    StoryEntity     │   GroupEntity    │  EventEntity   │
├────────────────────┼──────────────────┼────────────────┤
│ ConversationEntity │  MessageEntity   │ LiveStreamEnt. │
├────────────────────┴──────────────────┴────────────────┤
│       RemoteKeyEntity (Paging 3 Cursor Cache)          │
├────────────────────────────────────────────────────────┤
│   SyncPendingActionEntity (Offline Outbox Queue)       │
└────────────────────────────────────────────────────────┘
```

---

## 2. Model-to-Entity Mapping Matrix

| Laravel Backend Model | Android Room Entity | Primary Key | Key Relationships / Indices | Purpose |
| :--- | :--- | :---: | :--- | :--- |
| `User` + `UserProfile` | `UserEntity` | `id: Long` | Index on `username` | Stores authenticated user and cached friend/contact profiles |
| `Post` | `PostEntity` | `id: Long` | Index on `feed_tab`, `created_at`, `author_id` | Cached posts for For You, Following, Groups, and Pages tabs |
| `Story` + `StoryMedia` | `StoryEntity` | `id: Long` | Index on `user_id`, `expires_at` | Stores active 24-hour stories for the horizontal tray |
| `Reel` + `ReelMedia` | `ReelEntity` | `id: Long` | Index on `creator_id`, `created_at` | Cached vertical videos with audio track and engagement stats |
| `Group` | `GroupEntity` | `id: Long` | Index on `slug`, `is_member` | Discoverable, popular, and joined community groups |
| `Conversation` | `ConversationEntity`| `id: Long` | Index on `last_message_at` | 1-on-1 and group conversation threads in Messenger |
| `Message` | `MessageEntity` | `id: Long` | Index on `conversation_id`, `client_message_id` | Chat messages with 5-state delivery lifecycle |
| `Event` | `EventEntity` | `id: Long` | Index on `start_time`, `my_rsvp` | Community events and RSVP attendance status |
| `LiveStream` | `LiveStreamEntity` | `id: Long` | Index on `status`, `host_id` | Active live streams with viewer counters |
| `notifications` table | `NotificationEntity`| `id: String` | Index on `type`, `created_at` | Social alerts and friend request interactions |
| *Paging 3 Infrastructure* | `RemoteKeyEntity` | `item_id: Long` | Compound PK `(item_id, query_type)` | Remembers next/prev cursor for infinite scrolling |
| *Offline Sync Engine* | `SyncPendingActionEntity` | `id: Long` (auto) | Index on `status`, `created_at` | Stores optimistic mutations to flush when online |

---

## 3. Entity Definitions & Room Kotlin Code

### 3.1. UserEntity
```kotlin
@Entity(
    tableName = "users",
    indices = [
        Index(value = ["username"], unique = true)
    ]
)
data class UserEntity(
    @PrimaryKey val id: Long,
    val name: String,
    val username: String,
    val email: String?,
    val avatar: String?,
    val coverPhoto: String?,
    val bio: String?,
    val occupation: String?,
    val location: String?,
    val isVerified: Boolean = false,
    val friendsCount: Int = 0,
    val followersCount: Int = 0,
    val followingCount: Int = 0,
    val isOnline: Boolean = false,
    val lastSeenAt: Long? = null,
    val updatedAt: Long = System.currentTimeMillis()
)
```

### 3.2. PostEntity
```kotlin
@Entity(
    tableName = "posts",
    indices = [
        Index(value = ["feed_tab", "createdAt"]),
        Index(value = ["authorId"])
    ]
)
data class PostEntity(
    @PrimaryKey val id: Long,
    val authorId: Long,
    val authorName: String,
    val authorUsername: String,
    val authorAvatar: String?,
    val authorVerified: Boolean,
    val content: String,
    val mediaUrls: List<String>, // Serialized JSON list
    val mediaTypes: List<String>,
    val reactionsCount: Int,
    val commentsCount: Int,
    val sharesCount: Int,
    val userReaction: String?, // "like", "love", null
    val privacy: String, // "public", "friends", "private"
    val feedTab: String, // "for_you", "following", "groups", "pages"
    val createdAt: Long,
    val isOptimistic: Boolean = false
)
```

### 3.3. StoryEntity
```kotlin
@Entity(
    tableName = "stories",
    indices = [
        Index(value = ["userId", "expiresAt"])
    ]
)
data class StoryEntity(
    @PrimaryKey val id: Long,
    val userId: Long,
    val userName: String,
    val userAvatar: String?,
    val mediaUrl: String,
    val mediaType: String, // "image", "video"
    val durationSeconds: Int,
    val isSeen: Boolean = false,
    val expiresAt: Long,
    val createdAt: Long
)
```

### 3.4. ReelEntity
```kotlin
@Entity(
    tableName = "reels",
    indices = [
        Index(value = ["creatorId"]),
        Index(value = ["createdAt"])
    ]
)
data class ReelEntity(
    @PrimaryKey val id: Long,
    val creatorId: Long,
    val creatorName: String,
    val creatorAvatar: String?,
    val isFollowingCreator: Boolean,
    val caption: String,
    val videoUrl: String,
    val thumbnailUrl: String?,
    val durationMs: Long,
    val musicId: Long?,
    val musicTitle: String?,
    val musicArtist: String?,
    val likesCount: Int,
    val commentsCount: Int,
    val sharesCount: Int,
    val isLiked: Boolean,
    val isSaved: Boolean,
    val createdAt: Long
)
```

### 3.5. ConversationEntity & MessageEntity
```kotlin
@Entity(
    tableName = "conversations",
    indices = [
        Index(value = ["lastMessageAt"])
    ]
)
data class ConversationEntity(
    @PrimaryKey val id: Long,
    val type: String, // "direct", "group"
    val title: String?,
    val avatar: String?,
    val otherUserId: Long?,
    val otherUserName: String?,
    val otherUserAvatar: String?,
    val isOnline: Boolean,
    val lastMessageBody: String?,
    val lastMessageAt: Long?,
    val lastMessageSenderId: Long?,
    val unreadCount: Int = 0,
    val isPinned: Boolean = false,
    val isMuted: Boolean = false
)

@Entity(
    tableName = "messages",
    indices = [
        Index(value = ["conversationId", "createdAt"]),
        Index(value = ["clientMessageId"], unique = true)
    ],
    foreignKeys = [
        ForeignKey(
            entity = ConversationEntity::class,
            parentColumns = ["id"],
            childColumns = ["conversationId"],
            onDelete = ForeignKey.CASCADE
        )
    ]
)
data class MessageEntity(
    @PrimaryKey val id: Long,
    val conversationId: Long,
    val senderId: Long,
    val senderName: String,
    val body: String,
    val type: String, // "text", "media", "voice", "call"
    val mediaUrls: List<String> = emptyList(),
    val clientMessageId: String,
    val deliveryStatus: String, // "sending", "sent", "delivered", "read", "failed"
    val isMine: Boolean,
    val isEdited: Boolean = false,
    val isPinned: Boolean = false,
    val replyToMessageId: Long? = null,
    val createdAt: Long,
    val sentAt: Long?,
    val deliveredAt: Long?,
    val readAt: Long?
)
```

### 3.6. SyncPendingActionEntity (Offline Outbox Queue)
```kotlin
@Entity(
    tableName = "sync_pending_actions",
    indices = [
        Index(value = ["status", "createdAt"])
    ]
)
data class SyncPendingActionEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val actionType: String, // "SEND_MESSAGE", "REACT_POST", "LIKE_REEL", "RSVP_EVENT"
    val payloadJson: String,
    val status: String, // "PENDING", "IN_FLIGHT", "FAILED"
    val retryCount: Int = 0,
    val errorReason: String? = null,
    val createdAt: Long = System.currentTimeMillis()
)
```

---

## 4. Room Type Converters

To handle complex types without unnecessary SQLite joins:

```kotlin
class ConnectlyTypeConverters {
    private val json = Json { ignoreUnknownKeys = true }

    @TypeConverter
    fun fromStringList(value: List<String>?): String {
        return json.encodeToString(value ?: emptyList())
    }

    @TypeConverter
    fun toStringList(value: String?): List<String> {
        return if (value.isNullOrEmpty()) emptyList() else json.decodeFromString(value)
    }

    @TypeConverter
    fun fromTimestamp(value: Long?): Date? {
        return value?.let { Date(it) }
    }

    @TypeConverter
    fun dateToTimestamp(date: Date?): Long? {
        return date?.time
    }
}
```

---

## 5. Cache Invalidation & Eviction Policies

1. **Stories Cache:** Cleaned every 6 hours via WorkManager. Any story where `expiresAt < currentTime` is deleted from Room.
2. **Reels & Feed Cache:** Paging 3 retains a maximum of 200 items in memory and Room; older items beyond page 20 are pruned during sync to prevent unbounded mobile disk growth.
3. **Media Cache:** Disk cache managed by Coil and Media3 ExoPlayer with a fixed quota of 500MB, evicting using Least Recently Used (LRU) policy.
4. **Offline Outbox Eviction:** Completed pending actions (`status = "COMPLETED"`) are purged immediately; failed actions exceeding 5 retries are marked `"PERMANENT_FAILURE"` and surfaced to user.
