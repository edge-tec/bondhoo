# ENGINE 1 — REAL-TIME MESSAGING INTEGRATION GUIDE

> **Engine:** Real-Time Messaging & Chat Synchronization  
> **Technology:** Native Android Kotlin + Jetpack Compose + Room + Laravel Reverb (Pusher v7) + MySQL 8  
> **Status:** Fully Integrated & Verified

---

## 1. Overview & Capabilities

Engine 1 provides enterprise-grade, low-latency messaging between Connectly Android and JUGAJUG Web users.

### Key Capabilities:
- **One-to-One & Group Messaging:** Real-time dispatch and delivery over private channels.
- **Message Lifecycle Tracking:** Comprehensive status states (`SENDING`, `SENT`, `DELIVERED`, `READ`, `FAILED`).
- **Read Receipts & Delivery Acknowledgment:** Automatically sends delivery receipts when a message arrives on device; emits read receipts when the chat screen is opened.
- **Typing Indicators:** Real-time ephemeral typing indicators with 3-second debounce.
- **Online Presence & Last Seen:** Live tracking of contact availability.
- **Reactions & Replies:** Emoji reactions and nested quoted replies.
- **Message Editing & Deletion:** Real-time propagation of edited content or deleted tombstones (for me vs. for everyone).
- **Rich Media & Voice Notes:** Support for photos, video clips, documents, and voice audio with waveform visualization.
- **Offline Persistence & Room Sync:** Messages are cached locally in SQLite via Room, with automatic recovery upon reconnect.

---

## 2. Event Specifications & Payloads

### 2.1 `message.new`
Dispatched when a new direct or group chat message is created.
- **Channel:** `private-conversation.{conversationId}`
- **Payload Schema:**
```json
{
  "conversation_id": 104,
  "message": {
    "id": 8421,
    "conversation_id": 104,
    "sender_id": 42,
    "sender_name": "Tanzil Rahman",
    "body": "Hello, are we ready for the production deployment?",
    "type": "text",
    "client_message_id": "c88f1234-abcd-4a3e-b876-0123456789ab",
    "delivery_status": "sent",
    "created_at": "2026-11-01T10:15:30Z"
  }
}
```

### 2.2 `message.delivered`
Dispatched when the recipient's device receives the message.
- **Channel:** `private-conversation.{conversationId}`
- **Payload Schema:**
```json
{
  "conversation_id": 104,
  "message_id": 8421,
  "delivered_at": "2026-11-01T10:15:31Z"
}
```

### 2.3 `message.read`
Dispatched when the recipient views the conversation.
- **Channel:** `private-conversation.{conversationId}`
- **Payload Schema:**
```json
{
  "conversation_id": 104,
  "reader_id": 43,
  "last_read_message_id": 8421,
  "read_at": "2026-11-01T10:15:35Z"
}
```

### 2.4 `message.reaction`
Dispatched when an emoji reaction is added or removed.
- **Channel:** `private-conversation.{conversationId}`
- **Payload Schema:**
```json
{
  "conversation_id": 104,
  "message_id": 8421,
  "user_id": 43,
  "user_name": "Farhana Ahmed",
  "reaction": "❤️",
  "action": "added"
}
```

### 2.5 `user.typing`
Dispatched during text input in the chat composer.
- **Channel:** `private-conversation.{conversationId}`
- **Payload Schema:**
```json
{
  "conversation_id": 104,
  "user_id": 42,
  "user_name": "Tanzil Rahman",
  "is_typing": true
}
```

---

## 3. Client Implementation Architecture

### 3.1 Room Local Cache Schema
Messages and conversations are stored in SQLite using Room:
```kotlin
@Entity(
    tableName = "messages",
    indices = [
        Index(value = ["conversationId", "createdAt"]),
        Index(value = ["clientMessageId"], unique = true)
    ]
)
data class MessageEntity(
    @PrimaryKey val id: Long,
    val conversationId: Long,
    val senderId: Long,
    val senderName: String,
    val body: String,
    val type: String = "text",
    val clientMessageId: String,
    val deliveryStatus: String = "sent",
    val isMine: Boolean = true,
    val createdAt: Long = System.currentTimeMillis()
)
```

### 3.2 Offline Queuing & Message Recovery Loop
```
[ User Types Message ]
          |
          v
[ Room MessageDao: Insert optimistic message (SENDING) ]
          |
   (Has Internet?)
    /            \
  (Yes)          (No)
   /                \
  v                  v
[ API: POST message ]   [ Saved in Room ]
  |                         |
  +-- (Success) ----------> [ Network Restored ]
  |                            |
  v                            v
[ Update Room: SENT ]   [ WorkManager / SyncManager ]
                               |
                               v
                        [ Re-send pending messages ]
```

---

## 4. Verification & Cross-Platform Parity

- **Android to Android:** Instant display, delivery checkmark toggles from 1 check (sent) to 2 checks (delivered), turns green on read receipt.
- **Android to Web:** Sending from Android immediately updates the web chat window via Laravel Reverb Echo listener without requiring page reload.
- **Web to Android:** Sending from the web browser immediately fires `message.new` over Reverb, triggering local notification if in background or updating ChatScreen if active.

