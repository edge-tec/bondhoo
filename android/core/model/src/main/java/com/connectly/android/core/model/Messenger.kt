package com.connectly.android.core.model

import kotlinx.serialization.Serializable

@Serializable
data class Conversation(
    val id: Long,
    val type: String = "direct", // "direct", "group", "saved"
    val title: String? = null,
    val avatar: String? = null,
    val otherUser: User? = null,
    val lastMessage: Message? = null,
    val unreadCount: Int = 0,
    val isPinned: Boolean = false,
    val isMuted: Boolean = false,
    val isArchived: Boolean = false,
    val isOnline: Boolean = false,
    val draftMessage: String? = null,
    val myRole: String = "member",
    val members: List<User> = emptyList()
)

@Serializable
data class MessagePreview(
    val id: Long,
    val senderName: String,
    val body: String,
    val type: String = "text"
)

@Serializable
data class Message(
    val id: Long,
    val conversationId: Long,
    val senderId: Long,
    val senderName: String,
    val body: String,
    val type: String = "text", // "text", "media", "voice", "file", "call"
    val mediaUrls: List<String> = emptyList(),
    val clientMessageId: String,
    val deliveryStatus: DeliveryStatus = DeliveryStatus.SENDING,
    val isMine: Boolean = true,
    val isEdited: Boolean = false,
    val isPinned: Boolean = false,
    val isSaved: Boolean = false,
    val isDeleted: Boolean = false,
    val isForwarded: Boolean = false,
    val replyToMessageId: Long? = null,
    val replyPreview: MessagePreview? = null,
    val reactions: Map<String, Int> = emptyMap(),
    val myReaction: String? = null,
    val audioUrl: String? = null,
    val audioDurationSeconds: Int? = null,
    val createdAt: Long = System.currentTimeMillis(),
    val sentAt: Long? = null,
    val deliveredAt: Long? = null,
    val readAt: Long? = null
)

/**
 * Enterprise 5-State Delivery Lifecycle
 */
enum class DeliveryStatus(val rawValue: String) {
    SENDING("sending"),       // Optimistic pending (⌛)
    SENT("sent"),             // Server acknowledged (✓)
    DELIVERED("delivered"),   // Device received (✓✓ gray)
    READ("read"),             // Viewed by recipient (✓✓ blue)
    FAILED("failed")          // Network drop / transmission error (⚠️ retry)
}

@Serializable
data class ActivePresenceFriend(
    val id: Long,
    val name: String,
    val username: String,
    val avatarUrl: String? = null,
    val online: Boolean = false,
    val lastSeenDisplay: String? = null,
    val conversationId: Long? = null,
    val canCall: Boolean = true
)
