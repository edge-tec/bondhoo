package com.connectly.android.feature.profile.presentation.friends

import com.connectly.android.core.model.User

data class FriendRequestItem(
    val user: User,
    val mutualFriendsCount: Int,
    val timeAgo: String
)

data class SuggestedFriendItem(
    val user: User,
    val mutualFriendsCount: Int,
    val reason: String = "Suggested for you"
)

data class FriendsUiState(
    val selectedTab: String = "requests", // "requests", "friends", "suggestions"
    val friendRequests: List<FriendRequestItem> = emptyList(),
    val friendsList: List<User> = emptyList(),
    val suggestions: List<SuggestedFriendItem> = emptyList(),
    val searchQuery: String = "",
    val isLoading: Boolean = false,
    val errorMessage: String? = null
)

sealed interface FriendsIntent {
    data class SelectTab(val tab: String) : FriendsIntent
    data class SearchQueryChanged(val query: String) : FriendsIntent
    data class AcceptRequest(val userId: Long) : FriendsIntent
    data class DeleteRequest(val userId: Long) : FriendsIntent
    data class AddFriend(val userId: Long) : FriendsIntent
    data class RemoveSuggestion(val userId: Long) : FriendsIntent
    data class Unfriend(val userId: Long) : FriendsIntent
    object Refresh : FriendsIntent
}

