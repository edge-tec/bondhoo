package com.connectly.android.core.model

import kotlinx.serialization.Serializable

@Serializable
data class Conversation(
    val id: Long,
    val type: String = "direct", // "direct", "group"
    val title: String? = null,
    val avatar: String? = null,
    val otherUser: User? = null,
    val lastMessage: Message? = null,
    val unreadCount: Int = 0,
    val isPinned: Boolean = false,
    val isMuted: Boolean = false,
    val isOnline: Boolean = false
)

@Serializable
data class Message(
    val id: Long,
    val conversationId: Long,
    val senderId: Long,
    val senderName: String,
    val body: String,
    val type: String = "text", // "text", "media", "voice", "call"
    val mediaUrls: List<String> = emptyList(),
    val clientMessageId: String,
    val deliveryStatus: DeliveryStatus = DeliveryStatus.SENDING,
    val isMine: Boolean = true,
    val isEdited: Boolean = false,
    val isPinned: Boolean = false,
    val replyToMessageId: Long? = null,
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
