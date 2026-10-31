package com.connectly.android.core.database.entity

import androidx.room.Entity
import androidx.room.ForeignKey
import androidx.room.Index
import androidx.room.PrimaryKey

@Entity(
    tableName = "users",
    indices = [Index(value = ["username"], unique = true)]
)
data class UserEntity(
    @PrimaryKey val id: Long,
    val name: String,
    val username: String,
    val email: String? = null,
    val avatar: String? = null,
    val coverPhoto: String? = null,
    val bio: String? = null,
    val isVerified: Boolean = false,
    val friendsCount: Int = 0,
    val followersCount: Int = 0,
    val followingCount: Int = 0,
    val isOnline: Boolean = false
)

@Entity(
    tableName = "posts",
    indices = [
        Index(value = ["feedTab", "createdAt"]),
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
    val mediaUrls: List<String> = emptyList(),
    val reactionsCount: Int = 0,
    val commentsCount: Int = 0,
    val sharesCount: Int = 0,
    val userReaction: String? = null,
    val feedTab: String = "for_you",
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(
    tableName = "stories",
    indices = [Index(value = ["userId", "expiresAt"])]
)
data class StoryEntity(
    @PrimaryKey val id: Long,
    val userId: Long,
    val userName: String,
    val userAvatar: String?,
    val mediaUrl: String,
    val mediaType: String = "image",
    val durationSeconds: Int = 5,
    val isSeen: Boolean = false,
    val expiresAt: Long,
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(
    tableName = "reels",
    indices = [Index(value = ["creatorId"]), Index(value = ["createdAt"])]
)
data class ReelEntity(
    @PrimaryKey val id: Long,
    val creatorId: Long,
    val creatorName: String,
    val creatorAvatar: String?,
    val caption: String,
    val videoUrl: String,
    val thumbnailUrl: String?,
    val likesCount: Int = 0,
    val commentsCount: Int = 0,
    val sharesCount: Int = 0,
    val isLiked: Boolean = false,
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(
    tableName = "conversations",
    indices = [Index(value = ["lastMessageAt"])]
)
data class ConversationEntity(
    @PrimaryKey val id: Long,
    val type: String = "direct",
    val otherUserId: Long?,
    val otherUserName: String?,
    val otherUserAvatar: String?,
    val isOnline: Boolean = false,
    val lastMessageBody: String?,
    val lastMessageAt: Long?,
    val unreadCount: Int = 0
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
    val type: String = "text",
    val clientMessageId: String,
    val deliveryStatus: String = "sent", // sending, sent, delivered, read, failed
    val isMine: Boolean = true,
    val createdAt: Long = System.currentTimeMillis()
)

@Entity(tableName = "groups")
data class GroupEntity(
    @PrimaryKey val id: Long,
    val name: String,
    val slug: String,
    val coverImage: String?,
    val membersCount: Int = 0,
    val isMember: Boolean = false
)

@Entity(tableName = "events")
data class EventEntity(
    @PrimaryKey val id: Long,
    val title: String,
    val banner: String?,
    val location: String,
    val startTime: Long,
    val interestedCount: Int = 0,
    val goingCount: Int = 0,
    val myRsvp: String? = null
)

@Entity(
    tableName = "sync_pending_actions",
    indices = [Index(value = ["status", "createdAt"])]
)
data class SyncPendingActionEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val actionType: String,
    val payloadJson: String,
    val status: String = "PENDING", // PENDING, IN_FLIGHT, FAILED
    val retryCount: Int = 0,
    val createdAt: Long = System.currentTimeMillis()
)
