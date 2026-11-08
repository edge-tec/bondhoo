package com.connectly.android.core.websocket.model

import com.connectly.android.core.model.Message

sealed interface ConnectionState {
    data object Disconnected : ConnectionState
    data object Connecting : ConnectionState
    data class Connected(val socketId: String) : ConnectionState
    data class Reconnecting(val attempt: Int) : ConnectionState
}

// -----------------------------------------------------------------------------
// ENGINE 1 — MESSAGING EVENTS
// -----------------------------------------------------------------------------

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

data class ConversationUpdatedEvent(
    val conversationId: Long,
    val action: String,
    val payload: Map<String, String> = emptyMap()
)

// -----------------------------------------------------------------------------
// ENGINE 2 — CALLING EVENTS
// -----------------------------------------------------------------------------

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

// -----------------------------------------------------------------------------
// ENGINE 3 — PUSH & IN-APP NOTIFICATIONS
// -----------------------------------------------------------------------------

data class SocialNotificationEvent(
    val userId: Long,
    val notificationId: String,
    val title: String,
    val message: String,
    val type: String,
    val actionUrl: String? = null,
    val createdAt: String? = null
)

data class NotificationReadEvent(
    val userId: Long,
    val notificationId: String?,
    val unreadCount: Int = 0
)

data class NotificationReadAllEvent(
    val userId: Long
)

data class NotificationDeletedEvent(
    val userId: Long,
    val notificationId: String,
    val unreadCount: Int = 0
)

// -----------------------------------------------------------------------------
// ENGINE 4 — SOCIAL REAL-TIME SYNCHRONIZATION
// -----------------------------------------------------------------------------

data class PostReactionUpdatedEvent(
    val postId: Long,
    val totalReactions: Int,
    val breakdown: Map<String, Int> = emptyMap()
)

data class PostCommentUpdatedEvent(
    val postId: Long,
    val pageId: Long? = null,
    val commentId: Long,
    val action: String, // "created", "updated", "deleted"
    val commentsCount: Int = 0,
    val commentBody: String? = null,
    val authorName: String? = null
)

data class PagePostPublishedEvent(
    val pageId: Long,
    val postId: Long,
    val content: String? = null,
    val authorName: String? = null
)

data class FriendshipEvent(
    val userId: Long,
    val targetId: Long,
    val action: String, // "request.sent", "request.received", "request.accepted", "request.rejected", "removed", "followed", "unfollowed"
    val counters: Map<String, Int> = emptyMap()
)

data class StoryEvent(
    val storyId: Long,
    val action: String // "created", "deleted", "expired"
)

data class ReelEvent(
    val reelId: Long,
    val action: String, // "created", "reaction.updated", "comment.created", "deleted", "expired"
    val likesCount: Int = 0,
    val commentsCount: Int = 0
)

data class LiveStreamEvent(
    val channelId: String,
    val action: String, // "started", "ended", "comment.received", "reaction.created", "gift.received"
    val senderName: String? = null,
    val message: String? = null
)

data class GroupEvent(
    val groupId: Long,
    val action: String, // "post.created", "announcement.published", "poll.voted", "member.joined"
    val title: String? = null
)

data class ProfileUpdatedEvent(
    val userId: Long,
    val username: String,
    val avatarUrl: String? = null,
    val coverUrl: String? = null,
    val name: String? = null
)

