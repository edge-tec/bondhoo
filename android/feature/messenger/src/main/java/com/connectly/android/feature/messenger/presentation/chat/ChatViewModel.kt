package com.connectly.android.feature.messenger.presentation.chat

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.model.CallType
import com.connectly.android.core.model.Conversation
import com.connectly.android.core.model.DeliveryStatus
import com.connectly.android.core.model.Message
import com.connectly.android.core.model.MessagePreview
import com.connectly.android.core.websocket.ReverbWebSocketManager
import com.connectly.android.feature.messenger.domain.usecase.CreateConversationUseCase
import com.connectly.android.feature.messenger.domain.usecase.DeleteMessageUseCase
import com.connectly.android.feature.messenger.domain.usecase.EditMessageUseCase
import com.connectly.android.feature.messenger.domain.usecase.ForwardMessageUseCase
import com.connectly.android.feature.messenger.domain.usecase.GetConversationDetailsUseCase
import com.connectly.android.feature.messenger.domain.usecase.GetConversationsUseCase
import com.connectly.android.feature.messenger.domain.usecase.GetFriendsForMessagingUseCase
import com.connectly.android.feature.messenger.domain.usecase.GetMessagesUseCase
import com.connectly.android.feature.messenger.domain.usecase.ManageConversationUseCase
import com.connectly.android.feature.messenger.domain.usecase.MarkReadUseCase
import com.connectly.android.feature.messenger.domain.usecase.ObserveIncomingMessagesUseCase
import com.connectly.android.feature.messenger.domain.usecase.ObservePresenceUseCase
import com.connectly.android.feature.messenger.domain.usecase.ObserveTypingUseCase
import com.connectly.android.feature.messenger.domain.usecase.PinMessageUseCase
import com.connectly.android.feature.messenger.domain.usecase.ReactToMessageUseCase
import com.connectly.android.feature.messenger.domain.usecase.ReportMessageUseCase
import com.connectly.android.feature.messenger.domain.usecase.SaveMessageUseCase
import com.connectly.android.feature.messenger.domain.usecase.SendMessageUseCase
import com.connectly.android.feature.messenger.domain.usecase.SendTypingUseCase
import com.connectly.android.feature.messenger.domain.usecase.SendVoiceMessageUseCase
import com.connectly.android.feature.messenger.domain.usecase.UploadMessageAttachmentUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.collect
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.UUID
import javax.inject.Inject

@HiltViewModel
class ChatViewModel @Inject constructor(
    private val getConversationDetailsUseCase: GetConversationDetailsUseCase,
    private val getConversationsUseCase: GetConversationsUseCase,
    private val createConversationUseCase: CreateConversationUseCase,
    private val getFriendsUseCase: GetFriendsForMessagingUseCase,
    private val getMessagesUseCase: GetMessagesUseCase,
    private val sendMessageUseCase: SendMessageUseCase,
    private val uploadAttachmentUseCase: UploadMessageAttachmentUseCase,
    private val sendVoiceMessageUseCase: SendVoiceMessageUseCase,
    private val editMessageUseCase: EditMessageUseCase,
    private val forwardMessageUseCase: ForwardMessageUseCase,
    private val pinMessageUseCase: PinMessageUseCase,
    private val saveMessageUseCase: SaveMessageUseCase,
    private val markReadUseCase: MarkReadUseCase,
    private val reactToMessageUseCase: ReactToMessageUseCase,
    private val deleteMessageUseCase: DeleteMessageUseCase,
    private val reportMessageUseCase: ReportMessageUseCase,
    private val manageConversationUseCase: ManageConversationUseCase,
    private val sendTypingUseCase: SendTypingUseCase,
    private val observeIncomingMessagesUseCase: ObserveIncomingMessagesUseCase,
    private val observeTypingUseCase: ObserveTypingUseCase,
    private val observePresenceUseCase: ObservePresenceUseCase,
    private val webSocketManager: ReverbWebSocketManager,
    private val tokenStorageManager: TokenStorageManager
) : ViewModel() {

    private val _uiState = MutableStateFlow(ChatState())
    val uiState: StateFlow<ChatState> = _uiState.asStateFlow()

    private val _effects = MutableSharedFlow<ChatEffect>(extraBufferCapacity = 16)
    val effects: SharedFlow<ChatEffect> = _effects.asSharedFlow()

    private var observersJob: Job? = null
    private var searchJob: Job? = null
    private var typingStopJob: Job? = null
    private var currentLoadedConvId: Long = 0L

    fun handleIntent(intent: ChatIntent) {
        when (intent) {
            is ChatIntent.LoadChat -> loadChat(intent.conversationId)
            ChatIntent.RefreshChat -> silentSync()
            is ChatIntent.SendTextMessage -> sendTextMessage(intent.body)
            is ChatIntent.RetryMessage -> retryMessage(intent.message)
            is ChatIntent.SendAttachmentFile -> sendAttachmentFile(intent.file, intent.mimeType, intent.caption)
            is ChatIntent.SendVoiceFile -> sendVoiceFile(intent.audioFile, intent.durationSeconds)
            is ChatIntent.TogglePlayVoice -> togglePlayVoice(intent.message)
            is ChatIntent.SetReplyTo -> _uiState.update {
                it.copy(replyingToMessage = intent.message, editingMessage = null)
            }
            is ChatIntent.StartEditMessage -> _uiState.update {
                it.copy(editingMessage = intent.message, replyingToMessage = null)
            }
            is ChatIntent.SubmitEditMessage -> submitEditMessage(intent.messageId, intent.newBody)
            is ChatIntent.OpenForwardSheet -> openForwardSheet(intent.message)
            is ChatIntent.ForwardToConversation -> forwardToConversation(intent.messageId, intent.targetConversationId)
            is ChatIntent.ForwardToFriend -> forwardToFriend(intent.messageId, intent.friendId)
            is ChatIntent.ReactMessage -> reactMessage(intent.messageId, intent.reaction)
            is ChatIntent.TogglePinMessage -> togglePinMessage(intent.message)
            is ChatIntent.ToggleSaveMessage -> toggleSaveMessage(intent.message)
            is ChatIntent.DeleteMessage -> deleteMessage(intent.messageId, intent.forEveryone)
            is ChatIntent.ReportMessage -> reportMessage(intent.messageId, intent.reason, intent.details)
            is ChatIntent.TypingChanged -> handleTypingChanged(intent.isTyping)
            is ChatIntent.ToggleSearchMode -> toggleSearchMode(intent.enabled)
            is ChatIntent.SearchQueryChanged -> searchInChat(intent.query)
            is ChatIntent.HandleMessageRequest -> handleMessageRequest(intent.action)
            is ChatIntent.ToggleChatInfoSheet -> toggleChatInfoSheet(intent.show)
            ChatIntent.ToggleMuteConversation -> toggleMuteConversation()
            ChatIntent.TogglePinConversation -> togglePinConversation()
            ChatIntent.ToggleArchiveConversation -> toggleArchiveConversation()
            ChatIntent.ClearConversationHistory -> clearConversationHistory()
            ChatIntent.DeleteConversation -> deleteConversation()
            ChatIntent.LeaveGroup -> leaveGroup()
            is ChatIntent.UpdateGroupInfo -> updateGroupInfo(intent.title, intent.description)
            ChatIntent.LoadFriendsForGroup -> loadFriendsForGroup()
            is ChatIntent.AddGroupMembers -> addGroupMembers(intent.userIds)
            is ChatIntent.RemoveGroupMember -> removeGroupMember(intent.userId)
            is ChatIntent.UpdateMemberRole -> updateMemberRole(intent.userId, intent.role)
            is ChatIntent.MessageMemberDirectly -> messageMemberDirectly(intent.userId)
            is ChatIntent.StartCallFromChat -> startCallFromChat(intent.isVideo)
            ChatIntent.ClearStatusMessages -> _uiState.update {
                it.copy(statusMessage = null, errorMessage = null)
            }
        }
    }

    private fun loadChat(conversationId: Long) {
        if (conversationId <= 0L) return
        val isNewConversation = currentLoadedConvId != conversationId
        currentLoadedConvId = conversationId

        _uiState.update {
            it.copy(
                conversationId = conversationId,
                isLoading = true,
                errorMessage = null,
                messages = if (isNewConversation) emptyList() else it.messages
            )
        }

        loadConversationMetadata(conversationId)
        loadMessages(conversationId, showLoading = true)
        loadPinnedMessages(conversationId)

        viewModelScope.launch {
            markReadUseCase(conversationId).collect()
        }

        if (isNewConversation) {
            subscribeRealtimeEvents(conversationId)
        }
    }

    fun silentSync() {
        val convId = _uiState.value.conversationId
        if (convId <= 0L) return
        loadMessages(convId, showLoading = false)
        loadConversationMetadata(convId)
    }

    fun onVoicePlaybackCompleted() {
        _uiState.update { it.copy(isAudioPlaying = false, playingMessageId = null) }
    }

    private fun loadConversationMetadata(conversationId: Long) {
        viewModelScope.launch {
            getConversationDetailsUseCase(conversationId).collect { result ->
                when (result) {
                    is Result.Success -> {
                        applyConversationMetadata(result.data)
                    }
                    is Result.Error -> {
                        // If this conversation was not found (404), conversationId may be a user/recipient ID!
                        // Automatically open or create the direct conversation with this user
                        createConversationUseCase(recipientId = conversationId).collect { createRes ->
                            if (createRes is Result.Success) {
                                val resolvedConv = createRes.data
                                currentLoadedConvId = resolvedConv.id
                                _uiState.update {
                                    it.copy(
                                        conversationId = resolvedConv.id,
                                        errorMessage = null
                                    )
                                }
                                applyConversationMetadata(resolvedConv)
                                loadMessages(resolvedConv.id, showLoading = true)
                                loadPinnedMessages(resolvedConv.id)
                                subscribeRealtimeEvents(resolvedConv.id)
                            } else if (createRes is Result.Error) {
                                _uiState.update {
                                    it.copy(
                                        isLoading = false,
                                        errorMessage = "Conversation not found."
                                    )
                                }
                            }
                        }
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun applyConversationMetadata(conv: Conversation) {
        _uiState.update {
            it.copy(
                conversation = conv,
                participantName = conv.displayTitle,
                participantAvatar = conv.displayAvatar,
                isOnline = conv.isOnline
            )
        }
    }

    private fun loadMessages(conversationId: Long, showLoading: Boolean) {
        viewModelScope.launch {
            getMessagesUseCase(conversationId).collect { result ->
                when (result) {
                    is Result.Loading -> {
                        if (showLoading) {
                            _uiState.update { it.copy(isLoading = true) }
                        }
                    }
                    is Result.Success -> {
                        _uiState.update { state ->
                            val pendingOptimistic = state.messages.filter {
                                it.deliveryStatus == DeliveryStatus.SENDING || it.deliveryStatus == DeliveryStatus.FAILED
                            }.filter { pending ->
                                result.data.none { server ->
                                    server.id == pending.id || server.clientMessageId == pending.clientMessageId
                                }
                            }
                            state.copy(
                                isLoading = false,
                                messages = (result.data + pendingOptimistic).sortedBy { m -> m.createdAt.takeIf { ts -> ts > 0 } ?: m.id }
                            )
                        }
                    }
                    is Result.Error -> {
                        if (showLoading) {
                            // If conversation metadata is already loaded, display the error.
                            // If conversation is still resolving (e.g. from userId), defer to avoid premature 404 toast.
                            if (_uiState.value.conversation != null) {
                                _uiState.update {
                                    it.copy(
                                        isLoading = false,
                                        errorMessage = result.exception.message ?: "Unable to load messages"
                                    )
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private fun loadPinnedMessages(conversationId: Long) {
        viewModelScope.launch {
            pinMessageUseCase.getPinned(conversationId).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(pinnedMessages = result.data) }
                }
            }
        }
    }

    private fun subscribeRealtimeEvents(conversationId: Long) {
        observersJob?.cancel()
        webSocketManager.connect()
        webSocketManager.subscribeConversation(conversationId)

        observersJob = viewModelScope.launch {
            launch {
                observeIncomingMessagesUseCase(conversationId).collect { incomingMsg ->
                    _uiState.update { state ->
                        val existingIdx = state.messages.indexOfFirst {
                            it.id == incomingMsg.id ||
                                (incomingMsg.clientMessageId.isNotBlank() && it.clientMessageId == incomingMsg.clientMessageId)
                        }
                        val updatedList = if (existingIdx >= 0) {
                            state.messages.toMutableList().apply { set(existingIdx, incomingMsg) }
                        } else {
                            state.messages + incomingMsg
                        }
                        state.copy(messages = updatedList)
                    }
                    markReadUseCase(conversationId).collect()
                }
            }

            launch {
                observeTypingUseCase(conversationId).collect { (userId, typing) ->
                    val myId = tokenStorageManager.getUserId() ?: 0L
                    if (userId != myId) {
                        val memberName = _uiState.value.conversation?.members
                            ?.firstOrNull { it.id == userId }?.name
                            ?: _uiState.value.conversation?.otherUser?.name
                        _uiState.update {
                            it.copy(
                                isTyping = typing,
                                typingUserName = if (typing) memberName else null
                            )
                        }
                    }
                }
            }

            launch {
                observePresenceUseCase().collect { (userId, online) ->
                    val otherId = _uiState.value.conversation?.otherUser?.id
                    if (otherId != null && otherId == userId) {
                        _uiState.update { it.copy(isOnline = online) }
                    }
                }
            }

            launch {
                webSocketManager.messageUpdatedFlow.collect { ev ->
                    if (ev.conversationId == conversationId) {
                        _uiState.update { state ->
                            state.copy(
                                messages = state.messages.map { m ->
                                    if (m.id == ev.messageId) m.copy(body = ev.body, isEdited = ev.isEdited) else m
                                }
                            )
                        }
                    }
                }
            }

            launch {
                webSocketManager.messageDeletedFlow.collect { ev ->
                    if (ev.conversationId == conversationId) {
                        _uiState.update { state ->
                            state.copy(
                                messages = if (ev.forEveryone) {
                                    state.messages.map { m ->
                                        if (m.id == ev.messageId) {
                                            m.copy(body = "This message was deleted", isDeleted = true, mediaUrls = emptyList(), attachments = emptyList())
                                        } else m
                                    }
                                } else {
                                    state.messages.filterNot { it.id == ev.messageId }
                                }
                            )
                        }
                    }
                }
            }

            launch {
                webSocketManager.messageReceiptFlow.collect { ev ->
                    if (ev.conversationId == conversationId) {
                        val receiptMsgId = ev.messageId
                        val newStatus = if (ev.status.equals("read", ignoreCase = true)) {
                            DeliveryStatus.READ
                        } else {
                            DeliveryStatus.DELIVERED
                        }
                        _uiState.update { state ->
                            state.copy(
                                messages = state.messages.map { m ->
                                    if (m.isMine && (receiptMsgId == null || m.id <= receiptMsgId)) {
                                        m.copy(deliveryStatus = newStatus)
                                    } else m
                                }
                            )
                        }
                    }
                }
            }

            launch {
                webSocketManager.conversationClearedFlow.collect { ev ->
                    if (ev.conversationId == conversationId) {
                        _uiState.update { it.copy(messages = emptyList(), pinnedMessages = emptyList()) }
                    }
                }
            }
        }
    }

    private fun sendTextMessage(rawBody: String) {
        val body = rawBody.trim()
        if (body.isBlank()) return

        val editing = _uiState.value.editingMessage
        if (editing != null) {
            submitEditMessage(editing.id, body)
            return
        }

        val convId = _uiState.value.conversationId
        if (convId <= 0L) return
        val replyTo = _uiState.value.replyingToMessage
        val clientUuid = "client_${UUID.randomUUID()}"
        val nowFormatted = SimpleDateFormat("h:mm a", Locale.getDefault()).format(Date())

        val optimisticMsg = Message(
            id = -System.currentTimeMillis(),
            conversationId = convId,
            senderId = 0L,
            senderName = "You",
            body = body,
            type = "text",
            clientMessageId = clientUuid,
            deliveryStatus = DeliveryStatus.SENDING,
            isMine = true,
            replyToMessageId = replyTo?.id,
            replyPreview = replyTo?.let {
                MessagePreview(id = it.id, senderName = it.senderName, body = it.body, type = it.type)
            },
            formattedTime = nowFormatted,
            createdAt = System.currentTimeMillis()
        )

        _uiState.update {
            it.copy(
                messages = it.messages + optimisticMsg,
                replyingToMessage = null
            )
        }

        viewModelScope.launch {
            sendMessageUseCase(
                conversationId = convId,
                body = body,
                type = "text",
                clientMessageId = clientUuid,
                replyToId = replyTo?.id
            ).collect { result ->
                when (result) {
                    is Result.Success -> {
                        val serverMsg = result.data
                        _uiState.update { state ->
                            val updated = state.messages.map { m ->
                                if (m.clientMessageId == clientUuid) {
                                    serverMsg.copy(deliveryStatus = DeliveryStatus.SENT)
                                } else {
                                    m
                                }
                            }
                            state.copy(messages = updated)
                        }
                    }
                    is Result.Error -> {
                        _uiState.update { state ->
                            state.copy(
                                messages = state.messages.map { m ->
                                    if (m.clientMessageId == clientUuid) m.copy(deliveryStatus = DeliveryStatus.FAILED) else m
                                },
                                errorMessage = result.exception.message ?: "Failed to send message"
                            )
                        }
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun retryMessage(failedMessage: Message) {
        val convId = _uiState.value.conversationId
        if (convId <= 0L) return
        _uiState.update { state ->
            state.copy(
                messages = state.messages.map { m ->
                    if (m.clientMessageId == failedMessage.clientMessageId) {
                        m.copy(deliveryStatus = DeliveryStatus.SENDING)
                    } else m
                }
            )
        }
        viewModelScope.launch {
            sendMessageUseCase(
                conversationId = convId,
                body = failedMessage.body,
                type = failedMessage.type,
                clientMessageId = failedMessage.clientMessageId,
                replyToId = failedMessage.replyToMessageId
            ).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update { state ->
                            state.copy(
                                messages = state.messages.map { m ->
                                    if (m.clientMessageId == failedMessage.clientMessageId) {
                                        result.data.copy(deliveryStatus = DeliveryStatus.SENT)
                                    } else m
                                }
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update { state ->
                            state.copy(
                                messages = state.messages.map { m ->
                                    if (m.clientMessageId == failedMessage.clientMessageId) {
                                        m.copy(deliveryStatus = DeliveryStatus.FAILED)
                                    } else m
                                },
                                errorMessage = result.exception.message ?: "Retry failed"
                            )
                        }
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun sendAttachmentFile(file: File, mimeType: String, caption: String) {
        val convId = _uiState.value.conversationId
        if (convId <= 0L) return
        val replyTo = _uiState.value.replyingToMessage

        _uiState.update { it.copy(isUploadingAttachment = true, replyingToMessage = null) }

        viewModelScope.launch {
            uploadAttachmentUseCase(file, mimeType).collect { uploadResult ->
                when (uploadResult) {
                    is Result.Loading -> _uiState.update { it.copy(isUploadingAttachment = true) }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isUploadingAttachment = false,
                                errorMessage = uploadResult.exception.message ?: "Failed to upload attachment"
                            )
                        }
                    }
                    is Result.Success -> {
                        val uploaded = uploadResult.data
                        val messageType = if (mimeType.startsWith("image/", ignoreCase = true) ||
                            mimeType.startsWith("video/", ignoreCase = true)
                        ) {
                            "media"
                        } else {
                            "file"
                        }
                        val clientUuid = "media_${UUID.randomUUID()}"
                        sendMessageUseCase(
                            conversationId = convId,
                            body = caption.trim(),
                            type = messageType,
                            clientMessageId = clientUuid,
                            replyToId = replyTo?.id,
                            mediaIds = listOf(uploaded.id)
                        ).collect { sendResult ->
                            when (sendResult) {
                                is Result.Loading -> Unit
                                is Result.Success -> {
                                    _uiState.update { state ->
                                        val msg = sendResult.data.let { m ->
                                            if (m.attachments.isEmpty()) {
                                                m.copy(
                                                    attachments = listOf(uploaded),
                                                    mediaUrls = listOfNotNull(uploaded.url.takeIf { u -> u.isNotBlank() })
                                                )
                                            } else m
                                        }
                                        state.copy(
                                            isUploadingAttachment = false,
                                            messages = state.messages + msg
                                        )
                                    }
                                    runCatching { file.delete() }
                                }
                                is Result.Error -> {
                                    _uiState.update {
                                        it.copy(
                                            isUploadingAttachment = false,
                                            errorMessage = sendResult.exception.message ?: "Failed to send attachment"
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private fun sendVoiceFile(audioFile: File, durationSeconds: Int) {
        val convId = _uiState.value.conversationId
        if (convId <= 0L) return

        _uiState.update { it.copy(isUploadingAttachment = true) }

        viewModelScope.launch {
            sendVoiceMessageUseCase(
                conversationId = convId,
                audioFile = audioFile,
                durationSeconds = durationSeconds.coerceAtLeast(1)
            ).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isUploadingAttachment = true) }
                    is Result.Success -> {
                        _uiState.update { state ->
                            state.copy(
                                isUploadingAttachment = false,
                                messages = state.messages + result.data
                            )
                        }
                        runCatching { audioFile.delete() }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isUploadingAttachment = false,
                                errorMessage = result.exception.message ?: "Failed to send voice message"
                            )
                        }
                    }
                }
            }
        }
    }

    private fun togglePlayVoice(message: Message) {
        if (message.audioUrl.isNullOrBlank()) {
            _uiState.update { it.copy(errorMessage = "Voice audio unavailable") }
            return
        }
        _uiState.update { state ->
            if (state.playingMessageId == message.id && state.isAudioPlaying) {
                state.copy(isAudioPlaying = false)
            } else {
                state.copy(playingMessageId = message.id, isAudioPlaying = true)
            }
        }
    }

    private fun submitEditMessage(messageId: Long, newBody: String) {
        val trimmed = newBody.trim()
        if (trimmed.isBlank()) return

        _uiState.update { it.copy(editingMessage = null) }

        viewModelScope.launch {
            editMessageUseCase(messageId, trimmed).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update { state ->
                            state.copy(
                                messages = state.messages.map { m ->
                                    if (m.id == messageId) result.data else m
                                },
                                statusMessage = "Message edited"
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(errorMessage = result.exception.message ?: "Failed to edit message")
                        }
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun openForwardSheet(message: Message?) {
        _uiState.update { it.copy(forwardingMessage = message) }
        if (message != null) {
            viewModelScope.launch {
                getConversationsUseCase("all").collect { res ->
                    if (res is Result.Success) {
                        _uiState.update { it.copy(forwardTargets = res.data) }
                    }
                }
            }
            viewModelScope.launch {
                getFriendsUseCase(null).collect { res ->
                    if (res is Result.Success) {
                        _uiState.update { it.copy(forwardFriends = res.data) }
                    }
                }
            }
        }
    }

    private fun forwardToConversation(messageId: Long, targetConversationId: Long) {
        _uiState.update { it.copy(forwardingMessage = null) }
        viewModelScope.launch {
            forwardMessageUseCase(messageId, listOf(targetConversationId)).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update { it.copy(statusMessage = "Message forwarded") }
                        if (targetConversationId == _uiState.value.conversationId) {
                            silentSync()
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(errorMessage = result.exception.message ?: "Failed to forward message")
                        }
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun forwardToFriend(messageId: Long, friendId: Long) {
        _uiState.update { it.copy(forwardingMessage = null) }
        viewModelScope.launch {
            createConversationUseCase(recipientId = friendId).collect { convRes ->
                if (convRes is Result.Success) {
                    forwardToConversation(messageId, convRes.data.id)
                } else if (convRes is Result.Error) {
                    _uiState.update {
                        it.copy(errorMessage = convRes.exception.message ?: "Unable to forward to friend")
                    }
                }
            }
        }
    }

    private fun reactMessage(messageId: Long, reaction: String) {
        // Optimistic toggle
        _uiState.update { state ->
            state.copy(
                messages = state.messages.map { m ->
                    if (m.id == messageId) {
                        val updatedMap = m.reactions.toMutableMap()
                        val previous = m.myReaction
                        if (previous == reaction) {
                            val next = (updatedMap[reaction] ?: 1) - 1
                            if (next <= 0) updatedMap.remove(reaction) else updatedMap[reaction] = next
                            m.copy(reactions = updatedMap, myReaction = null)
                        } else {
                            if (previous != null) {
                                val prevNext = (updatedMap[previous] ?: 1) - 1
                                if (prevNext <= 0) updatedMap.remove(previous) else updatedMap[previous] = prevNext
                            }
                            updatedMap[reaction] = (updatedMap[reaction] ?: 0) + 1
                            m.copy(reactions = updatedMap, myReaction = reaction)
                        }
                    } else m
                }
            )
        }

        viewModelScope.launch {
            reactToMessageUseCase(messageId, reaction).collect { res ->
                if (res is Result.Error) {
                    silentSync()
                }
            }
        }
    }

    private fun togglePinMessage(message: Message) {
        val convId = _uiState.value.conversationId
        if (convId <= 0L) return
        val unpinning = message.isPinned || _uiState.value.pinnedMessages.any { it.message.id == message.id }

        viewModelScope.launch {
            val flow = if (unpinning) {
                pinMessageUseCase.unpin(convId, message.id)
            } else {
                pinMessageUseCase.pin(convId, message.id)
            }
            flow.collect { res ->
                when (res) {
                    is Result.Success -> {
                        _uiState.update { state ->
                            state.copy(
                                messages = state.messages.map { m ->
                                    if (m.id == message.id) m.copy(isPinned = !unpinning) else m
                                },
                                statusMessage = if (unpinning) "Message unpinned" else "Message pinned"
                            )
                        }
                        loadPinnedMessages(convId)
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(errorMessage = res.exception.message ?: "Unable to update pin")
                        }
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun toggleSaveMessage(message: Message) {
        val unsaving = message.isSaved
        viewModelScope.launch {
            val flow = if (unsaving) saveMessageUseCase.unsave(message.id) else saveMessageUseCase.save(message.id)
            flow.collect { res ->
                when (res) {
                    is Result.Success -> {
                        _uiState.update { state ->
                            state.copy(
                                messages = state.messages.map { m ->
                                    if (m.id == message.id) m.copy(isSaved = !unsaving) else m
                                },
                                statusMessage = if (unsaving) "Removed from Saved" else "Saved message"
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(errorMessage = res.exception.message ?: "Failed to save message")
                        }
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun deleteMessage(messageId: Long, forEveryone: Boolean) {
        _uiState.update { state ->
            val updated = if (forEveryone) {
                state.messages.map { m ->
                    if (m.id == messageId) {
                        m.copy(
                            body = "This message was deleted",
                            isDeleted = true,
                            mediaUrls = emptyList(),
                            attachments = emptyList()
                        )
                    } else m
                }
            } else {
                state.messages.filterNot { it.id == messageId }
            }
            state.copy(messages = updated)
        }
        viewModelScope.launch {
            deleteMessageUseCase(messageId, forEveryone).collect { res ->
                if (res is Result.Error) {
                    _uiState.update {
                        it.copy(errorMessage = res.exception.message ?: "Unable to delete message")
                    }
                    silentSync()
                }
            }
        }
    }

    private fun reportMessage(messageId: Long, reason: String, details: String?) {
        viewModelScope.launch {
            reportMessageUseCase(messageId, reason, details).collect { res ->
                when (res) {
                    is Result.Success -> _uiState.update {
                        it.copy(statusMessage = "Message reported to Bondoo safety team")
                    }
                    is Result.Error -> _uiState.update {
                        it.copy(errorMessage = res.exception.message ?: "Failed to report message")
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun handleTypingChanged(isTyping: Boolean) {
        val convId = _uiState.value.conversationId
        if (convId <= 0L) return
        sendTypingUseCase(convId, isTyping)
        typingStopJob?.cancel()
        if (isTyping) {
            typingStopJob = viewModelScope.launch {
                delay(3500)
                sendTypingUseCase(convId, false)
            }
        }
    }

    private fun toggleSearchMode(enabled: Boolean) {
        _uiState.update {
            it.copy(
                isSearchMode = enabled,
                searchQuery = if (!enabled) "" else it.searchQuery,
                searchResults = if (!enabled) null else it.searchResults
            )
        }
    }

    private fun searchInChat(query: String) {
        val convId = _uiState.value.conversationId
        _uiState.update { it.copy(searchQuery = query) }
        searchJob?.cancel()
        if (query.trim().isEmpty()) {
            _uiState.update { it.copy(searchResults = null) }
            return
        }
        searchJob = viewModelScope.launch {
            delay(250)
            getMessagesUseCase(convId, search = query.trim()).collect { res ->
                if (res is Result.Success) {
                    _uiState.update { it.copy(searchResults = res.data) }
                }
            }
        }
    }

    private fun handleMessageRequest(action: String) {
        val convId = _uiState.value.conversationId
        if (convId <= 0L) return
        viewModelScope.launch {
            manageConversationUseCase.handleRequest(convId, action).collect { res ->
                when (res) {
                    is Result.Success -> {
                        if (action == "accept") {
                            _uiState.update { it.copy(statusMessage = "Message request accepted") }
                            loadConversationMetadata(convId)
                        } else {
                            _effects.emit(ChatEffect.NavigateBack)
                        }
                    }
                    is Result.Error -> _uiState.update {
                        it.copy(errorMessage = res.exception.message ?: "Action failed")
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun toggleChatInfoSheet(show: Boolean) {
        _uiState.update { it.copy(showChatInfoSheet = show) }
        if (show) {
            val convId = _uiState.value.conversationId
            loadConversationMetadata(convId)
            loadSharedMediaAndFiles(convId)
        }
    }

    private fun loadSharedMediaAndFiles(conversationId: Long) {
        _uiState.update { it.copy(isLoadingSharedMedia = true) }
        viewModelScope.launch {
            manageConversationUseCase.getMedia(conversationId, "media").collect { res ->
                if (res is Result.Success) {
                    _uiState.update { it.copy(sharedMedia = res.data, isLoadingSharedMedia = false) }
                } else if (res is Result.Error) {
                    _uiState.update { it.copy(isLoadingSharedMedia = false) }
                }
            }
        }
        viewModelScope.launch {
            manageConversationUseCase.getMedia(conversationId, "files").collect { res ->
                if (res is Result.Success) {
                    _uiState.update { it.copy(sharedFiles = res.data) }
                }
            }
        }
    }

    private fun toggleMuteConversation() {
        val conv = _uiState.value.conversation ?: return
        val action = if (conv.isMuted) "unmute" else "forever"
        viewModelScope.launch {
            manageConversationUseCase.mute(conv.id, action).collect { res ->
                if (res is Result.Success) {
                    loadConversationMetadata(conv.id)
                    _uiState.update {
                        it.copy(statusMessage = if (conv.isMuted) "Notifications unmuted" else "Notifications muted")
                    }
                }
            }
        }
    }

    private fun togglePinConversation() {
        val conv = _uiState.value.conversation ?: return
        viewModelScope.launch {
            manageConversationUseCase.togglePin(conv.id).collect { res ->
                if (res is Result.Success) {
                    loadConversationMetadata(conv.id)
                    _uiState.update {
                        it.copy(statusMessage = if (res.data) "Chat pinned" else "Chat unpinned")
                    }
                }
            }
        }
    }

    private fun toggleArchiveConversation() {
        val conv = _uiState.value.conversation ?: return
        viewModelScope.launch {
            manageConversationUseCase.toggleArchive(conv.id).collect { res ->
                if (res is Result.Success) {
                    loadConversationMetadata(conv.id)
                    _uiState.update {
                        it.copy(statusMessage = if (res.data) "Chat archived" else "Chat unarchived")
                    }
                }
            }
        }
    }

    private fun clearConversationHistory() {
        val convId = _uiState.value.conversationId
        if (convId <= 0L) return
        viewModelScope.launch {
            manageConversationUseCase.clear(convId).collect { res ->
                if (res is Result.Success) {
                    _uiState.update {
                        it.copy(
                            messages = emptyList(),
                            pinnedMessages = emptyList(),
                            showChatInfoSheet = false,
                            statusMessage = "Chat history cleared"
                        )
                    }
                } else if (res is Result.Error) {
                    _uiState.update {
                        it.copy(errorMessage = res.exception.message ?: "Unable to clear history")
                    }
                }
            }
        }
    }

    private fun deleteConversation() {
        val convId = _uiState.value.conversationId
        if (convId <= 0L) return
        viewModelScope.launch {
            manageConversationUseCase.deleteConversation(convId).collect { res ->
                if (res is Result.Success) {
                    _uiState.update { it.copy(showChatInfoSheet = false) }
                    _effects.emit(ChatEffect.NavigateBack)
                } else if (res is Result.Error) {
                    _uiState.update {
                        it.copy(errorMessage = res.exception.message ?: "Unable to delete chat")
                    }
                }
            }
        }
    }

    private fun leaveGroup() {
        val convId = _uiState.value.conversationId
        if (convId <= 0L) return
        viewModelScope.launch {
            manageConversationUseCase.leave(convId).collect { res ->
                if (res is Result.Success) {
                    _uiState.update { it.copy(showChatInfoSheet = false) }
                    _effects.emit(ChatEffect.NavigateBack)
                } else if (res is Result.Error) {
                    _uiState.update {
                        it.copy(errorMessage = res.exception.message ?: "Unable to leave group")
                    }
                }
            }
        }
    }

    private fun updateGroupInfo(title: String, description: String?) {
        val convId = _uiState.value.conversationId
        if (convId <= 0L || title.isBlank()) return
        viewModelScope.launch {
            manageConversationUseCase.updateGroupInfo(convId, title.trim(), description?.trim()).collect { res ->
                when (res) {
                    is Result.Success -> {
                        applyConversationMetadata(res.data)
                        _uiState.update { it.copy(statusMessage = "Group details updated") }
                    }
                    is Result.Error -> _uiState.update {
                        it.copy(errorMessage = res.exception.message ?: "Unable to update group")
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun loadFriendsForGroup() {
        viewModelScope.launch {
            getFriendsUseCase(null).collect { res ->
                if (res is Result.Success) {
                    val existingIds = _uiState.value.conversation?.members?.map { it.id }?.toSet() ?: emptySet()
                    _uiState.update {
                        it.copy(availableFriendsForGroup = res.data.filterNot { f -> existingIds.contains(f.id) })
                    }
                }
            }
        }
    }

    private fun addGroupMembers(userIds: List<Long>) {
        val convId = _uiState.value.conversationId
        if (convId <= 0L || userIds.isEmpty()) return
        viewModelScope.launch {
            manageConversationUseCase.addMembers(convId, userIds).collect { res ->
                when (res) {
                    is Result.Success -> {
                        loadConversationMetadata(convId)
                        loadMessages(convId, showLoading = false)
                        _uiState.update { it.copy(statusMessage = "Members added to group") }
                    }
                    is Result.Error -> _uiState.update {
                        it.copy(errorMessage = res.exception.message ?: "Failed to add members")
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun removeGroupMember(userId: Long) {
        val convId = _uiState.value.conversationId
        if (convId <= 0L) return
        viewModelScope.launch {
            manageConversationUseCase.removeMember(convId, userId).collect { res ->
                when (res) {
                    is Result.Success -> {
                        loadConversationMetadata(convId)
                        _uiState.update { it.copy(statusMessage = "Member removed") }
                    }
                    is Result.Error -> _uiState.update {
                        it.copy(errorMessage = res.exception.message ?: "Failed to remove member")
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun updateMemberRole(userId: Long, role: String) {
        val convId = _uiState.value.conversationId
        if (convId <= 0L) return
        viewModelScope.launch {
            manageConversationUseCase.updateMemberRole(convId, userId, role).collect { res ->
                when (res) {
                    is Result.Success -> {
                        loadConversationMetadata(convId)
                        _uiState.update { it.copy(statusMessage = "Member role updated") }
                    }
                    is Result.Error -> _uiState.update {
                        it.copy(errorMessage = res.exception.message ?: "Failed to update role")
                    }
                    is Result.Loading -> Unit
                }
            }
        }
    }

    private fun messageMemberDirectly(userId: Long) {
        val myId = tokenStorageManager.getUserId()
        if (myId != null && myId == userId) return
        viewModelScope.launch {
            createConversationUseCase(recipientId = userId).collect { res ->
                if (res is Result.Success) {
                    _uiState.update { it.copy(showChatInfoSheet = false) }
                    _effects.emit(ChatEffect.OpenOtherChat(res.data.id))
                }
            }
        }
    }

    private fun startCallFromChat(isVideo: Boolean) {
        val conv = _uiState.value.conversation
        val convId = _uiState.value.conversationId
        if (convId <= 0L || _uiState.value.isSaved) return
        val callType = when {
            conv?.isGroup == true && isVideo -> CallType.GROUP_VIDEO
            conv?.isGroup == true && !isVideo -> CallType.GROUP_AUDIO
            isVideo -> CallType.VIDEO
            else -> CallType.AUDIO
        }
        viewModelScope.launch {
            _effects.emit(
                ChatEffect.StartCall(
                    conversationId = convId,
                    receiverId = conv?.otherUser?.id,
                    receiverIds = emptyList(),
                    callType = callType
                )
            )
        }
    }

    override fun onCleared() {
        observersJob?.cancel()
        super.onCleared()
    }
}
