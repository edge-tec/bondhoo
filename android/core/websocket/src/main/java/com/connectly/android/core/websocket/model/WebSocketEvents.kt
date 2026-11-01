package com.connectly.android.core.websocket.model

import com.connectly.android.core.model.Message

sealed interface ConnectionState {
    data object Disconnected : ConnectionState
    data object Connecting : ConnectionState
    data class Connected(val socketId: String) : ConnectionState
    data class Reconnecting(val attempt: Int) : ConnectionState
}

data class MessageNewEvent(
    val conversationId: Long,
    val message: Message
)

data class MessageUpdatedEvent(
    val conversationId: Long,
    val messageId: Long,
    val body: String,
    val isEdited: Boolean,
    val editedAt: String
)

data class MessageDeletedEvent(
    val conversationId: Long,
    val messageId: Long,
    val forEveryone: Boolean
)

data class MessageReceiptEvent(
    val conversationId: Long,
    val messageId: Long?,
    val status: String, // "delivered", "read"
    val timestamp: String
)

data class MessageReactionEvent(
    val conversationId: Long,
    val messageId: Long,
    val userId: Long,
    val userName: String,
    val reaction: String,
    val action: String // "added", "removed"
)

data class UserTypingEvent(
    val conversationId: Long,
    val userId: Long,
    val userName: String,
    val isTyping: Boolean
)

data class UserPresenceEvent(
    val userId: Long,
    val isOnline: Boolean,
    val lastSeen: String? = null
)

data class ConversationClearedEvent(
    val conversationId: Long
)
