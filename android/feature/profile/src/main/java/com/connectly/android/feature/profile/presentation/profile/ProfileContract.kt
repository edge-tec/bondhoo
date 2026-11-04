package com.connectly.android.feature.profile.presentation.profile

import com.connectly.android.core.model.Post
import com.connectly.android.core.model.User

data class ProfileState(
    val user: User? = null,
    val isLoading: Boolean = false,
    val selectedTab: Int = 0, // 0: Posts, 1: Photos, 2: Videos, 3: Reels
    val isFollowing: Boolean = false,
    val isFriend: Boolean = false,
    val posts: List<Post> = emptyList(),
    val photos: List<String> = emptyList(),
    val videos: List<String> = emptyList(),
    val reels: List<String> = emptyList(),
    val userMessage: String? = null,
    val errorMessage: String? = null
)

sealed interface ProfileIntent {
    data class LoadProfile(val username: String = "me") : ProfileIntent
    data class SelectTab(val tabIndex: Int) : ProfileIntent
    object ToggleFollow : ProfileIntent
    object SendFriendRequest : ProfileIntent
    object ShareProfile : ProfileIntent
    object CopyProfileLink : ProfileIntent
    object BlockUser : ProfileIntent
    object ReportProfile : ProfileIntent
    object DismissMessage : ProfileIntent
}

