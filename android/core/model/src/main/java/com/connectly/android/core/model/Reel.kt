package com.connectly.android.core.model

import kotlinx.serialization.Serializable

@Serializable
data class Reel(
    val id: Long,
    val creator: User,
    val caption: String,
    val videoUrl: String,
    val thumbnailUrl: String? = null,
    val durationMs: Long = 0L,
    val music: MusicTrack? = null,
    val likesCount: Int = 0,
    val commentsCount: Int = 0,
    val sharesCount: Int = 0,
    val isLiked: Boolean = false,
    val isSaved: Boolean = false,
    val isFollowingCreator: Boolean = false,
    val createdAt: Long = System.currentTimeMillis()
)

@Serializable
data class MusicTrack(
    val id: Long,
    val title: String,
    val artist: String? = null,
    val audioUrl: String? = null,
    val coverUrl: String? = null
)
