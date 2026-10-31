package com.connectly.android.core.model

import kotlinx.serialization.Serializable

@Serializable
data class Story(
    val id: Long,
    val userId: Long,
    val author: User,
    val mediaUrl: String,
    val mediaType: String = "image", // "image", "video"
    val durationSeconds: Int = 5,
    val isSeen: Boolean = false,
    val userReaction: String? = null,
    val viewsCount: Int = 0,
    val viewers: List<User> = emptyList(),
    val privacy: String = "public",
    val expiresAt: Long = System.currentTimeMillis() + 86400000L,
    val createdAt: Long = System.currentTimeMillis()
)

@Serializable
data class StoryUserTray(
    val user: User,
    val hasUnseenStories: Boolean,
    val stories: List<Story>
)
