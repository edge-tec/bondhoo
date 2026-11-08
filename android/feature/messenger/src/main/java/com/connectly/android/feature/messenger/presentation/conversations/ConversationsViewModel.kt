package com.connectly.android.feature.messenger.presentation.conversations

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.Conversation
import com.connectly.android.feature.messenger.domain.repository.MessengerRepository
import com.connectly.android.feature.messenger.domain.usecase.CreateConversationUseCase
import com.connectly.android.feature.messenger.domain.usecase.GetConversationsUseCase
import com.connectly.android.feature.messenger.domain.usecase.GetFriendsForMessagingUseCase
import com.connectly.android.feature.messenger.domain.usecase.GetMessengerCallHistoryUseCase
import com.connectly.android.feature.messenger.domain.usecase.ManageConversationUseCase
import com.connectly.android.feature.messenger.domain.usecase.MarkReadUseCase
import com.connectly.android.feature.messenger.domain.usecase.ObservePresenceUseCase
import com.connectly.android.feature.messenger.domain.usecase.SearchMessagesUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ConversationsViewModel @Inject constructor(
    private val getConversationsUseCase: GetConversationsUseCase,
    private val createConversationUseCase: CreateConversationUseCase,
    private val getFriendsUseCase: GetFriendsForMessagingUseCase,
    private val getCallHistoryUseCase: GetMessengerCallHistoryUseCase,
    private val manageConversationUseCase: ManageConversationUseCase,
    private val markReadUseCase: MarkReadUseCase,
    private val searchMessagesUseCase: SearchMessagesUseCase,
    private val observePresenceUseCase: ObservePresenceUseCase,
    private val repository: MessengerRepository,
    private val reverbWebSocketManager: com.connectly.android.core.websocket.ReverbWebSocketManager
) : ViewModel() {

    private val _uiState = MutableStateFlow(ConversationsState())
    val uiState: StateFlow<ConversationsState> = _uiState.asStateFlow()

    private val _effects = MutableSharedFlow<ConversationsEffect>(extraBufferCapacity = 16)
    val effects: SharedFlow<ConversationsEffect> = _effects.asSharedFlow()

    private var searchJob: Job? = null
    private var composerSearchJob: Job? = null
    private var conversationsJob: Job? = null

    init {
        loadConversations("all", showLoading = true)
        loadActiveFriends()
        observeRealtimePresence()
        observeRealtimeConversations()
        viewModelScope.launch { repository.sendPresenceHeartbeat() }
    }

    fun handleIntent(intent: ConversationsIntent) {
        when (intent) {
            is ConversationsIntent.SelectFilter -> {
                _uiState.update { it.copy(selectedFilter = intent.filter, errorMessage = null) }
                if (intent.filter == "calls") {
                    loadCallHistory()
                } else {
                    loadConversations(intent.filter, showLoading = true)
                }
            }

            is ConversationsIntent.SearchQueryChanged -> {
                _uiState.update { it.copy(searchQuery = intent.query) }
                triggerGlobalMessengerSearch(intent.query)
            }

            ConversationsIntent.ToggleSearch -> {
                val closing = _uiState.value.isSearchVisible
                _uiState.update {
                    it.copy(
                        isSearchVisible = !closing,
                        searchQuery = if (closing) "" else it.searchQuery,
                        messageSearchResults = if (closing) emptyList() else it.messageSearchResults
                    )
                }
                if (closing) {
                    loadConversations(_uiState.value.selectedFilter, showLoading = false)
                }
            }

            is ConversationsIntent.ShowNewChatDialog -> {
                _uiState.update {
                    it.copy(
                        showNewChatDialog = intent.show,
                        composerMode = intent.initialMode,
                        composerSearchQuery = if (!intent.show) "" else it.composerSearchQuery,
                        selectedGroupMemberIds = if (!intent.show) emptySet() else it.selectedGroupMemberIds,
                        newGroupTitle = if (!intent.show) "" else it.newGroupTitle,
                        newGroupDescription = if (!intent.show) "" else it.newGroupDescription
                    )
                }
                if (intent.show) {
                    loadComposerFriends(null)
                }
            }

            is ConversationsIntent.SetComposerMode -> {
                _uiState.update { it.copy(composerMode = intent.mode) }
            }

            is ConversationsIntent.ComposerSearchChanged -> {
                _uiState.update { it.copy(composerSearchQuery = intent.query) }
                composerSearchJob?.cancel()
                composerSearchJob = viewModelScope.launch {
                    delay(250)
                    loadComposerFriends(intent.query)
                }
            }

            is ConversationsIntent.ToggleGroupMember -> {
                _uiState.update { current ->
                    val updated = if (current.selectedGroupMemberIds.contains(intent.userId)) {
                        current.selectedGroupMemberIds - intent.userId
                    } else {
                        current.selectedGroupMemberIds + intent.userId
                    }
                    current.copy(selectedGroupMemberIds = updated)
                }
            }

            is ConversationsIntent.UpdateNewGroupTitle -> {
                _uiState.update { it.copy(newGroupTitle = intent.title) }
            }

            is ConversationsIntent.UpdateNewGroupDescription -> {
                _uiState.update { it.copy(newGroupDescription = intent.description) }
            }

            is ConversationsIntent.OpenOrCreateDirectChat -> {
                openOrCreateDirectConversation(intent.friendId, intent.existingConversationId)
            }

            ConversationsIntent.OpenSavedMessages -> {
                openSavedMessages()
            }

            ConversationsIntent.CreateGroupConversation -> {
                createGroupChat()
            }

            is ConversationsIntent.StartGroupCallFromSelection -> {
                val selectedIds = _uiState.value.selectedGroupMemberIds.toList()
                if (selectedIds.isEmpty()) {
                    _uiState.update { it.copy(errorMessage = "Please select at least one friend to call.") }
                } else {
                    val callType = when {
                        selectedIds.size > 1 && intent.isVideo -> CallType.GROUP_VIDEO
                        selectedIds.size > 1 && !intent.isVideo -> CallType.GROUP_AUDIO
                        intent.isVideo -> CallType.VIDEO
                        else -> CallType.AUDIO
                    }
                    _uiState.update {
                        it.copy(
                            showNewChatDialog = false,
                            selectedGroupMemberIds = emptySet(),
                            newGroupTitle = "",
                            newGroupDescription = ""
                        )
                    }
                    viewModelScope.launch {
                        _effects.emit(
                            ConversationsEffect.StartCall(
                                conversationId = null,
                                receiverId = selectedIds.firstOrNull(),
                                receiverIds = selectedIds,
                                callType = callType
                            )
                        )
                    }
                }
            }

            is ConversationsIntent.ShowConversationMenu -> {
                _uiState.update { it.copy(selectedConversationForMenu = intent.conversation) }
            }

            is ConversationsIntent.TogglePin -> {
                performTogglePin(intent.conversationId)
            }

            is ConversationsIntent.ToggleArchive -> {
                performToggleArchive(intent.conversationId)
            }

            is ConversationsIntent.MuteConversation -> {
                performMuteConversation(intent.conversationId, intent.duration)
            }

            is ConversationsIntent.MarkUnread -> {
                performMarkUnread(intent.conversationId)
            }

            is ConversationsIntent.MarkRead -> {
                performMarkRead(intent.conversationId)
            }

            is ConversationsIntent.ClearConversation -> {
                performClearConversation(intent.conversationId)
            }

            is ConversationsIntent.DeleteOrLeaveConversation -> {
                performDeleteOrLeave(intent.conversation)
            }

            is ConversationsIntent.HandleMessageRequest -> {
                performHandleRequest(intent.conversationId, intent.action)
            }

            ConversationsIntent.ClearMessages -> {
                _uiState.update { it.copy(errorMessage = null, statusMessage = null) }
            }

            ConversationsIntent.Refresh -> {
                loadActiveFriends()
                if (_uiState.value.selectedFilter == "calls") {
                    loadCallHistory()
                } else {
                    loadConversations(_uiState.value.selectedFilter, showLoading = true)
                }
            }
        }
    }

    private fun loadConversations(filter: String, showLoading: Boolean) {
        if (filter == "calls") {
            loadCallHistory()
            return
        }
        conversationsJob?.cancel()
        conversationsJob = viewModelScope.launch {
            val search = _uiState.value.searchQuery.trim().takeIf { it.isNotEmpty() }
            getConversationsUseCase(filter = filter, search = search).collect { result ->
                when (result) {
                    is Result.Loading -> {
                        if (showLoading) {
                            _uiState.update { it.copy(isLoading = true) }
                        }
                    }
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                conversations = result.data,
                                errorMessage = null
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = result.exception.message ?: "Unable to load conversations."
                            )
                        }
                    }
                }
            }
        }
    }

    private fun loadActiveFriends() {
        viewModelScope.launch {
            getFriendsUseCase(null).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { state ->
                        state.copy(
                            activeFriends = result.data,
                            composerFriends = if (state.composerSearchQuery.isBlank()) result.data else state.composerFriends
                        )
                    }
                }
            }
        }
    }

    private fun loadComposerFriends(query: String?) {
        viewModelScope.launch {
            getFriendsUseCase(query).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(composerFriends = result.data) }
                }
            }
        }
    }

    private fun loadCallHistory() {
        viewModelScope.launch {
            getCallHistoryUseCase().collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, callHistory = result.data) }
                    is Result.Error -> _uiState.update {
                        it.copy(isLoading = false, errorMessage = result.exception.message)
                    }
                }
            }
        }
    }

    private fun triggerGlobalMessengerSearch(query: String) {
        searchJob?.cancel()
        val trimmed = query.trim()
        if (trimmed.isEmpty()) {
            _uiState.update { it.copy(messageSearchResults = emptyList()) }
            loadConversations(_uiState.value.selectedFilter, showLoading = false)
            return
        }
        searchJob = viewModelScope.launch {
            delay(280)
            launch {
                loadConversations(_uiState.value.selectedFilter, showLoading = false)
            }
            launch {
                loadComposerFriends(trimmed)
            }
            if (trimmed.length >= 2) {
                launch {
                    searchMessagesUseCase(trimmed).collect { result ->
                        if (result is Result.Success) {
                            _uiState.update { it.copy(messageSearchResults = result.data) }
                        }
                    }
                }
            }
        }
    }

    private fun openOrCreateDirectConversation(friendId: Long, existingConversationId: Long?) {
        if (existingConversationId != null && existingConversationId > 0L) {
            _uiState.update { it.copy(showNewChatDialog = false) }
            viewModelScope.launch {
                _effects.emit(ConversationsEffect.OpenChat(existingConversationId))
            }
            return
        }

        // Check if already in loaded conversations
        val existingInList = _uiState.value.conversations.firstOrNull {
            it.type == "direct" && it.otherUser?.id == friendId
        }
        if (existingInList != null) {
            _uiState.update { it.copy(showNewChatDialog = false) }
            viewModelScope.launch {
                _effects.emit(ConversationsEffect.OpenChat(existingInList.id))
            }
            return
        }

        viewModelScope.launch {
            createConversationUseCase(recipientId = friendId).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isCreatingChat = true) }
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isCreatingChat = false,
                                showNewChatDialog = false,
                                conversations = listOf(result.data) + it.conversations.filterNot { c -> c.id == result.data.id }
                            )
                        }
                        _effects.emit(ConversationsEffect.OpenChat(result.data.id))
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isCreatingChat = false,
                                errorMessage = result.exception.message ?: "Could not start conversation."
                            )
                        }
                    }
                }
            }
        }
    }

    private fun openSavedMessages() {
        val existingSaved = _uiState.value.conversations.firstOrNull { it.isSaved || it.type == "saved" }
        if (existingSaved != null) {
            viewModelScope.launch { _effects.emit(ConversationsEffect.OpenChat(existingSaved.id)) }
            return
        }
        viewModelScope.launch {
            createConversationUseCase.openSaved().collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isCreatingChat = true) }
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isCreatingChat = false,
                                showNewChatDialog = false,
                                conversations = listOf(result.data) + it.conversations.filterNot { c -> c.id == result.data.id }
                            )
                        }
                        _effects.emit(ConversationsEffect.OpenChat(result.data.id))
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(isCreatingChat = false, errorMessage = result.exception.message)
                        }
                    }
                }
            }
        }
    }

    private fun createGroupChat() {
        val state = _uiState.value
        val title = state.newGroupTitle.trim()
        val participantIds = state.selectedGroupMemberIds.toList()
        if (title.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter a group name.") }
            return
        }
        if (participantIds.isEmpty()) {
            _uiState.update { it.copy(errorMessage = "Please select at least one friend for the group.") }
            return
        }

        viewModelScope.launch {
            createConversationUseCase(
                recipientId = null,
                title = title,
                participantIds = participantIds,
                type = "group",
                description = state.newGroupDescription.trim().takeIf { it.isNotEmpty() }
            ).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isCreatingChat = true) }
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isCreatingChat = false,
                                showNewChatDialog = false,
                                selectedGroupMemberIds = emptySet(),
                                newGroupTitle = "",
                                newGroupDescription = "",
                                conversations = listOf(result.data) + it.conversations.filterNot { c -> c.id == result.data.id }
                            )
                        }
                        _effects.emit(ConversationsEffect.OpenChat(result.data.id))
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isCreatingChat = false,
                                errorMessage = result.exception.message ?: "Failed to create group."
                            )
                        }
                    }
                }
            }
        }
    }

    private fun performTogglePin(conversationId: Long) {
        _uiState.update { it.copy(selectedConversationForMenu = null) }
        viewModelScope.launch {
            manageConversationUseCase.togglePin(conversationId).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { state ->
                        state.copy(
                            conversations = state.conversations.map {
                                if (it.id == conversationId) it.copy(isPinned = result.data) else it
                            },
                            statusMessage = if (result.data) "Conversation pinned" else "Conversation unpinned"
                        )
                    }
                    loadConversations(_uiState.value.selectedFilter, showLoading = false)
                }
            }
        }
    }

    private fun performToggleArchive(conversationId: Long) {
        _uiState.update { it.copy(selectedConversationForMenu = null) }
        viewModelScope.launch {
            manageConversationUseCase.toggleArchive(conversationId).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { state ->
                        state.copy(
                            statusMessage = if (result.data) "Moved to Archived" else "Unarchived"
                        )
                    }
                    loadConversations(_uiState.value.selectedFilter, showLoading = false)
                }
            }
        }
    }

    private fun performMuteConversation(conversationId: Long, duration: String) {
        _uiState.update { it.copy(selectedConversationForMenu = null) }
        viewModelScope.launch {
            manageConversationUseCase.mute(conversationId, duration).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { state ->
                        state.copy(
                            conversations = state.conversations.map {
                                if (it.id == conversationId) it.copy(isMuted = result.data) else it
                            },
                            statusMessage = if (result.data) "Notifications muted" else "Notifications unmuted"
                        )
                    }
                }
            }
        }
    }

    private fun performMarkUnread(conversationId: Long) {
        _uiState.update { it.copy(selectedConversationForMenu = null) }
        viewModelScope.launch {
            manageConversationUseCase.markUnread(conversationId).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { state ->
                        state.copy(
                            conversations = state.conversations.map {
                                if (it.id == conversationId) it.copy(unreadCount = it.unreadCount.coerceAtLeast(1)) else it
                            },
                            statusMessage = "Marked as unread"
                        )
                    }
                }
            }
        }
    }

    private fun performMarkRead(conversationId: Long) {
        _uiState.update { it.copy(selectedConversationForMenu = null) }
        viewModelScope.launch {
            markReadUseCase(conversationId).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { state ->
                        state.copy(
                            conversations = state.conversations.map {
                                if (it.id == conversationId) it.copy(unreadCount = 0) else it
                            }
                        )
                    }
                }
            }
        }
    }

    private fun performClearConversation(conversationId: Long) {
        _uiState.update { it.copy(selectedConversationForMenu = null) }
        viewModelScope.launch {
            manageConversationUseCase.clear(conversationId).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { state ->
                        state.copy(
                            conversations = state.conversations.map {
                                if (it.id == conversationId) it.copy(lastMessage = null, unreadCount = 0) else it
                            },
                            statusMessage = "Chat history cleared"
                        )
                    }
                }
            }
        }
    }

    private fun performDeleteOrLeave(conversation: Conversation) {
        _uiState.update { it.copy(selectedConversationForMenu = null) }
        viewModelScope.launch {
            manageConversationUseCase.deleteConversation(conversation.id).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { state ->
                        state.copy(
                            conversations = state.conversations.filterNot { it.id == conversation.id },
                            statusMessage = if (conversation.isGroup) "Left group" else "Conversation deleted"
                        )
                    }
                }
            }
        }
    }

    private fun performHandleRequest(conversationId: Long, action: String) {
        viewModelScope.launch {
            manageConversationUseCase.handleRequest(conversationId, action).collect { result ->
                if (result is Result.Success) {
                    _uiState.update {
                        it.copy(statusMessage = "Message request ${action}ed")
                    }
                    loadConversations(_uiState.value.selectedFilter, showLoading = false)
                }
            }
        }
    }

    private fun observeRealtimePresence() {
        viewModelScope.launch {
            observePresenceUseCase().collect { (userId, isOnline) ->
                _uiState.update { state ->
                    state.copy(
                        conversations = state.conversations.map { conv ->
                            if (conv.otherUser?.id == userId) conv.copy(isOnline = isOnline) else conv
                        },
                        activeFriends = state.activeFriends.map { friend ->
                            if (friend.id == userId) friend.copy(online = isOnline) else friend
                        }
                    )
                }
            }
        }
    }

    private fun observeRealtimeConversations() {
        viewModelScope.launch {
            reverbWebSocketManager.conversationUpdatedFlow.collect {
                loadConversations(_uiState.value.selectedFilter, showLoading = false)
            }
        }
        viewModelScope.launch {
            reverbWebSocketManager.messageNewFlow.collect {
                loadConversations(_uiState.value.selectedFilter, showLoading = false)
            }
        }
        viewModelScope.launch {
            reverbWebSocketManager.conversationClearedFlow.collect { event ->
                _uiState.update { state ->
                    state.copy(
                        conversations = state.conversations.map {
                            if (it.id == event.conversationId) it.copy(lastMessage = null, unreadCount = 0) else it
                        }
                    )
                }
            }
        }
    }

    fun silentPeriodicSync() {
        viewModelScope.launch { repository.sendPresenceHeartbeat() }
        if (_uiState.value.selectedFilter != "calls" && _uiState.value.searchQuery.isBlank()) {
            loadConversations(_uiState.value.selectedFilter, showLoading = false)
        }
    }
}
