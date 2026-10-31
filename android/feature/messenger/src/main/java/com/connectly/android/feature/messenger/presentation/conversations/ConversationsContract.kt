package com.connectly.android.feature.messenger.presentation.conversations

import com.connectly.android.core.model.Conversation

data class ConversationsState(
    val selectedFilter: String = "all", // "all", "unread", "groups"
    val conversations: List<Conversation> = emptyList(),
    val searchQuery: String = "",
    val isLoading: Boolean = false,
    val errorMessage: String? = null
)

sealed interface ConversationsIntent {
    data class SelectFilter(val filter: String) : ConversationsIntent
    data class SearchQueryChanged(val query: String) : ConversationsIntent
    object Refresh : ConversationsIntent
}

