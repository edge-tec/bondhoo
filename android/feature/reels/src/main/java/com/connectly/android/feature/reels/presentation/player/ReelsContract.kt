package com.connectly.android.feature.reels.presentation.player

import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Reel

data class ReelsState(
    val reels: List<Reel> = emptyList(),
    val selectedTab: String = "for_you", // "for_you", "following", "friends", "popular", "latest"
    val currentIndex: Int = 0,
    val isLoading: Boolean = false,
    val activeCommentsReelId: Long? = null,
    val activeComments: List<Comment> = emptyList(),
    val isCommentsLoading: Boolean = false,
    val errorMessage: String? = null
)

sealed interface ReelsIntent {
    data class LoadFeed(val tab: String = "for_you") : ReelsIntent
    data class PageChanged(val index: Int) : ReelsIntent
    data class ToggleLike(val reelId: Long) : ReelsIntent
    data class ToggleSave(val reelId: Long) : ReelsIntent
    data class ShareReel(val reelId: Long) : ReelsIntent
    data class FollowCreator(val creatorId: Long) : ReelsIntent
    data class OpenComments(val reelId: Long) : ReelsIntent
    object CloseComments : ReelsIntent
    data class AddComment(val reelId: Long, val body: String) : ReelsIntent
}
