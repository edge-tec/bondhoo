package com.connectly.android.core.model

import kotlinx.serialization.Serializable

@Serializable
data class ConversationMember(
    val user: User,
    val role: String = "member"
)

@Serializable
data class Conversation(
    val id: Long,
    val type: String = "direct", // "direct", "group", "saved"
    val title: String? = null,
    val avatar: String? = null,
    val description: String? = null,
    val creatorId: Long? = null,
    val isSaved: Boolean = false,
    val otherUser: User? = null,
    val lastMessage: Message? = null,
    val lastMessageTimeFormatted: String = "",
    val lastMessageAtIso: String? = null,
    val unreadCount: Int = 0,
    val isPinned: Boolean = false,
    val isMuted: Boolean = false,
    val mutedUntil: String? = null,
    val isArchived: Boolean = false,
    val isRequest: Boolean = false,
    val requestStatus: String = "accepted",
    val isOnline: Boolean = false,
    val draftMessage: String? = null,
    val myRole: String = "member",
    val participantsCount: Int = 0,
    val members: List<User> = emptyList(),
    val memberRoles: Map<Long, String> = emptyMap(),
    val canSendMessages: String = "all",
    val canAddMembers: String = "all",
    val canEditInfo: String = "admins",
    val theme: ChatTheme? = null,
    val disappearingDuration: Int = 0,
    val isChannel: Boolean = false
) {
    val isGroup: Boolean get() = type.equals("group", ignoreCase = true)
    val displayTitle: String
        get() = when {
            isSaved || type.equals("saved", ignoreCase = true) -> "Saved Messages"
            !title.isNullOrBlank() -> title
            otherUser != null && otherUser.name.isNotBlank() -> otherUser.name
            members.isNotEmpty() -> members.joinToString(", ") { it.name }.take(40)
            else -> "Chat #$id"
        }
    val displayAvatar: String?
        get() = avatar?.takeIf { it.isNotBlank() } ?: otherUser?.avatar?.takeIf { it.isNotBlank() }
}

@Serializable
data class MessagePreview(
    val id: Long,
    val senderName: String,
    val body: String,
    val type: String = "text"
)

@Serializable
data class MessageAttachment(
    val id: Long,
    val url: String,
    val previewUrl: String? = null,
    val downloadUrl: String? = null,
    val mimeType: String = "",
    val fileName: String = "",
    val fileSize: Long = 0L,
    val width: Int? = null,
    val height: Int? = null
) {
    val isImage: Boolean get() = mimeType.startsWith("image/", ignoreCase = true)
    val isVideo: Boolean get() = mimeType.startsWith("video/", ignoreCase = true)
    val isAudio: Boolean get() = mimeType.startsWith("audio/", ignoreCase = true)
}

@Serializable
data class PinnedMessageInfo(
    val pinId: Long = 0L,
    val message: Message,
    val pinnedByName: String? = null,
    val pinnedAtFormatted: String = ""
)

@Serializable
data class Message(
    val id: Long,
    val conversationId: Long,
    val senderId: Long,
    val senderName: String,
    val senderAvatar: String? = null,
    val body: String,
    val type: String = "text", // "text", "media", "voice", "file", "call", "system"
    val mediaUrls: List<String> = emptyList(),
    val attachments: List<MessageAttachment> = emptyList(),
    val clientMessageId: String,
    val deliveryStatus: DeliveryStatus = DeliveryStatus.SENDING,
    val isMine: Boolean = true,
    val isEdited: Boolean = false,
    val isPinned: Boolean = false,
    val isSaved: Boolean = false,
    val isDeleted: Boolean = false,
    val isForwarded: Boolean = false,
    val version: Int = 1,
    val replyToMessageId: Long? = null,
    val replyPreview: MessagePreview? = null,
    val reactions: Map<String, Int> = emptyMap(),
    val myReaction: String? = null,
    val audioUrl: String? = null,
    val audioDurationSeconds: Int? = null,
    val callType: String? = null,
    val callOutcome: String? = null,
    val callDurationSeconds: Int? = null,
    val formattedTime: String = "",
    val createdAt: Long = System.currentTimeMillis(),
    val sentAt: Long? = null,
    val deliveredAt: Long? = null,
    val readAt: Long? = null,
    val expiresAt: String? = null,
    val poll: ChatPoll? = null,
    val location: LocationData? = null,
    val transcription: String? = null,
    val translatedText: String? = null
)

@Serializable
data class ChatPollOption(
    val id: Long,
    val pollId: Long,
    val optionText: String,
    val votesCount: Int = 0,
    val percentage: Double = 0.0,
    val isVotedByMe: Boolean = false
)

@Serializable
data class ChatPoll(
    val id: Long,
    val question: String,
    val isMultipleChoice: Boolean = false,
    val isAnonymous: Boolean = false,
    val canChangeVote: Boolean = true,
    val isClosed: Boolean = false,
    val endsAt: String? = null,
    val totalVotesCount: Int = 0,
    val hasVoted: Boolean = false,
    val myVotedOptionIds: List<Long> = emptyList(),
    val options: List<ChatPollOption> = emptyList()
)

@Serializable
data class LocationData(
    val latitude: Double,
    val longitude: Double,
    val address: String? = null,
    val isLive: Boolean = false,
    val liveUntil: String? = null,
    val mapUrl: String? = null
)

@Serializable
data class ChatTheme(
    val themeColor: String = "#0084FF",
    val bubbleColor: String = "#0084FF",
    val wallpaperUrl: String? = null,
    val gradient: String? = null,
    val soundPreset: String = "default"
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
