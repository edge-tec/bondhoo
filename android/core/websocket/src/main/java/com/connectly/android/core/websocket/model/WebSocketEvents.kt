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

data class CallIncomingEvent(
    val callId: Long,
    val conversationId: Long,
    val callType: String,
    val callerId: Long,
    val callerName: String,
    val callerAvatar: String? = null
)

data class CallAcceptedEvent(
    val callId: Long,
    val userId: Long,
    val status: String
)

data class CallRejectedEvent(
    val callId: Long,
    val userId: Long,
    val status: String
)

data class CallEndedEvent(
    val callId: Long,
    val status: String,
    val duration: Int = 0
)

data class CallSignalEvent(
    val callId: Long,
    val signalType: String,
    val senderId: Long,
    val senderName: String? = null,
    val targetUserId: Long? = null,
    val payload: Map<String, String>
)

data class CallParticipantStateEvent(
    val callId: Long,
    val userId: Long,
    val isMuted: Boolean? = null,
    val isCameraOff: Boolean? = null,
    val isScreenSharing: Boolean? = null
)

