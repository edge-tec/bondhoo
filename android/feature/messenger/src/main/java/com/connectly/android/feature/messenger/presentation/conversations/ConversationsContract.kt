package com.connectly.android.feature.messenger.presentation.conversations

import com.connectly.android.core.model.Conversation

data class ConversationsState(
    val selectedFilter: String = "all", // "all", "unread", "groups"
    val conversations: List<Conversation> = emptyList(),
    val searchQuery: String = "",
    val isSearchVisible: Boolean = false,
    val showNewChatDialog: Boolean = false,
    val isLoading: Boolean = false,
    val errorMessage: String? = null
)

sealed interface ConversationsIntent {
    data class SelectFilter(val filter: String) : ConversationsIntent
    data class SearchQueryChanged(val query: String) : ConversationsIntent
    data object ToggleSearch : ConversationsIntent
    data class ShowNewChatDialog(val show: Boolean) : ConversationsIntent
    data class StartNewChat(val name: String) : ConversationsIntent
    data object Refresh : ConversationsIntent
}
