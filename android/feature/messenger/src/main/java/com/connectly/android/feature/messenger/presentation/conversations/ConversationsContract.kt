package com.connectly.android.feature.messenger.presentation.conversations

import com.connectly.android.core.model.ActivePresenceFriend
import com.connectly.android.core.model.CallHistoryItem
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.Conversation
import com.connectly.android.core.model.Message

enum class ComposerSheetMode {
    DIRECT,
    GROUP
}

data class ConversationsState(
    val selectedFilter: String = "all", // "all", "unread", "groups", "pinned", "requests", "archived", "calls"
    val conversations: List<Conversation> = emptyList(),
    val activeFriends: List<ActivePresenceFriend> = emptyList(),
    val composerFriends: List<ActivePresenceFriend> = emptyList(),
    val callHistory: List<CallHistoryItem> = emptyList(),
    val messageSearchResults: List<Message> = emptyList(),
    val searchQuery: String = "",
    val composerSearchQuery: String = "",
    val isSearchVisible: Boolean = false,
    val showNewChatDialog: Boolean = false,
    val composerMode: ComposerSheetMode = ComposerSheetMode.DIRECT,
    val selectedGroupMemberIds: Set<Long> = emptySet(),
    val newGroupTitle: String = "",
    val newGroupDescription: String = "",
    val selectedConversationForMenu: Conversation? = null,
    val isLoading: Boolean = false,
    val isCreatingChat: Boolean = false,
    val errorMessage: String? = null,
    val statusMessage: String? = null
)

sealed interface ConversationsEffect {
    data class OpenChat(val conversationId: Long) : ConversationsEffect
    data class StartCall(
        val conversationId: Long? = null,
        val receiverId: Long? = null,
        val receiverIds: List<Long> = emptyList(),
        val callType: CallType = CallType.AUDIO
    ) : ConversationsEffect
}

sealed interface ConversationsIntent {
    data class SelectFilter(val filter: String) : ConversationsIntent
    data class SearchQueryChanged(val query: String) : ConversationsIntent
    data object ToggleSearch : ConversationsIntent
    data class ShowNewChatDialog(
        val show: Boolean,
        val initialMode: ComposerSheetMode = ComposerSheetMode.DIRECT
    ) : ConversationsIntent
    data class SetComposerMode(val mode: ComposerSheetMode) : ConversationsIntent
    data class ComposerSearchChanged(val query: String) : ConversationsIntent
    data class ToggleGroupMember(val userId: Long) : ConversationsIntent
    data class UpdateNewGroupTitle(val title: String) : ConversationsIntent
    data class UpdateNewGroupDescription(val description: String) : ConversationsIntent
    data class OpenOrCreateDirectChat(val friendId: Long, val existingConversationId: Long? = null) : ConversationsIntent
    data object OpenSavedMessages : ConversationsIntent
    data object CreateGroupConversation : ConversationsIntent
    data class StartGroupCallFromSelection(val isVideo: Boolean) : ConversationsIntent
    data class ShowConversationMenu(val conversation: Conversation?) : ConversationsIntent
    data class TogglePin(val conversationId: Long) : ConversationsIntent
    data class ToggleArchive(val conversationId: Long) : ConversationsIntent
    data class MuteConversation(val conversationId: Long, val duration: String) : ConversationsIntent
    data class MarkUnread(val conversationId: Long) : ConversationsIntent
    data class MarkRead(val conversationId: Long) : ConversationsIntent
    data class ClearConversation(val conversationId: Long) : ConversationsIntent
    data class DeleteOrLeaveConversation(val conversation: Conversation) : ConversationsIntent
    data class HandleMessageRequest(val conversationId: Long, val action: String) : ConversationsIntent
    data object ClearMessages : ConversationsIntent
    data object Refresh : ConversationsIntent
}
