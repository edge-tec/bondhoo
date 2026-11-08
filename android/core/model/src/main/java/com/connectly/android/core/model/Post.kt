package com.connectly.android.core.model

import kotlinx.serialization.Serializable

@Serializable
data class Post(
    val id: Long,
    val author: User,
    val content: String,
    val mediaUrls: List<String> = emptyList(),
    val mediaTypes: List<String> = emptyList(),
    val reactionsCount: Int = 0,
    val commentsCount: Int = 0,
    val sharesCount: Int = 0,
    val userReaction: String? = null,
    val isSaved: Boolean = false,
    val privacy: String = "public",
    val feedTab: String = "for_you",
    val status: String = "published",
    val moderationStatus: String? = null,
    val createdAt: Long = System.currentTimeMillis()
)

enum class ReactionType(val value: String, val emoji: String, val label: String) {
    LIKE("like", "👍", "Like"),
    LOVE("love", "❤️", "Love"),
    CARE("care", "🥰", "Care"),
    HAHA("haha", "😂", "Haha"),
    WOW("wow", "😮", "Wow"),
    SAD("sad", "😢", "Sad"),
    ANGRY("angry", "😡", "Angry")
}
