package com.connectly.android.core.model

import kotlinx.serialization.Serializable

@Serializable
data class Comment(
    val id: Long,
    val postId: Long,
    val parentId: Long? = null,
    val author: User,
    val body: String,
    val reactionsCount: Int = 0,
    val userReaction: String? = null,
    val repliesCount: Int = 0,
    val replies: List<Comment> = emptyList(),
    val createdAt: String = "Just now"
)
