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
    val privacy: String = "public",
    val feedTab: String = "for_you",
    val createdAt: Long = System.currentTimeMillis()
)

enum class ReactionType(val value: String, val emoji: String) {
    LIKE("like", "👍"),
    LOVE("love", "❤️"),
    HAHA("haha", "😂"),
    WOW("wow", "😮"),
    SAD("sad", "😢"),
    ANGRY("angry", "😡")
}

