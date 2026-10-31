package com.connectly.android.feature.messenger.presentation.chat

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.DeliveryStatus
import com.connectly.android.core.model.Message
import com.connectly.android.feature.messenger.domain.usecase.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.util.UUID
import javax.inject.Inject

@HiltViewModel
class ChatViewModel @Inject constructor(
    private val getMessagesUseCase: GetMessagesUseCase,
    private val sendMessageUseCase: SendMessageUseCase,
    private val markReadUseCase: MarkReadUseCase,
    private val reactToMessageUseCase: ReactToMessageUseCase,
    private val deleteMessageUseCase: DeleteMessageUseCase,
    private val observeIncomingMessagesUseCase: ObserveIncomingMessagesUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(ChatState())
    val uiState: StateFlow<ChatState> = _uiState.asStateFlow()

    fun handleIntent(intent: ChatIntent) {
        when (intent) {
            is ChatIntent.LoadChat -> loadChat(intent.conversationId)
            is ChatIntent.SendTextMessage -> sendTextMessage(intent.body)
            is ChatIntent.SendVoiceMessage -> sendVoiceMessage(intent.durationSeconds)
            is ChatIntent.ReactMessage -> reactMessage(intent.messageId, intent.reaction)
            is ChatIntent.DeleteMessage -> deleteMessage(intent.messageId, intent.forEveryone)
            is ChatIntent.TypingChanged -> { /* Typing signal */ }
        }
    }

    private fun loadChat(conversationId: Long) {
        _uiState.update { it.copy(conversationId = conversationId, isLoading = true) }

        viewModelScope.launch {
            getMessagesUseCase(conversationId).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, messages = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }

        // Mark conversation as read
        viewModelScope.launch {
            markReadUseCase(conversationId).collect()
        }

        // Subscribe to real-time incoming messages via WebSocket
        viewModelScope.launch {
            observeIncomingMessagesUseCase(conversationId).collect { incomingMsg ->
                _uiState.update { state ->
                    if (state.messages.none { it.id == incomingMsg.id || it.clientMessageId == incomingMsg.clientMessageId }) {
                        state.copy(messages = state.messages + incomingMsg)
                    } else state
                }
            }
        }
    }

    private fun sendTextMessage(body: String) {
        val convId = _uiState.value.conversationId
        val clientUuid = "client_${UUID.randomUUID()}"

        val optimisticMsg = Message(
            id = System.currentTimeMillis(),
            conversationId = convId,
            senderId = 1L,
            senderName = "You",
            body = body,
            type = "text",
            clientMessageId = clientUuid,
            deliveryStatus = DeliveryStatus.SENDING,
            isMine = true
        )

        _uiState.update { it.copy(messages = it.messages + optimisticMsg) }

        viewModelScope.launch {
            sendMessageUseCase(
                conversationId = convId,
                body = body,
                type = "text",
                clientMessageId = clientUuid
            ).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { state ->
                        val updated = state.messages.map { m ->
                            if (m.clientMessageId == clientUuid) m.copy(deliveryStatus = DeliveryStatus.SENT) else m
                        }
                        state.copy(messages = updated)
                    }
                }
            }
        }
    }

    private fun sendVoiceMessage(durationSeconds: Int) {
        val convId = _uiState.value.conversationId
        val clientUuid = "voice_${UUID.randomUUID()}"

        val voiceMsg = Message(
            id = System.currentTimeMillis(),
            conversationId = convId,
            senderId = 1L,
            senderName = "You",
            body = "Voice message (${durationSeconds}s)",
            type = "voice",
            clientMessageId = clientUuid,
            deliveryStatus = DeliveryStatus.SENT,
            isMine = true
        )

        _uiState.update { it.copy(messages = it.messages + voiceMsg) }

        viewModelScope.launch {
            sendMessageUseCase(
                conversationId = convId,
                body = "Voice message",
                type = "voice",
                clientMessageId = clientUuid
            ).collect()
        }
    }

    private fun reactMessage(messageId: Long, reaction: String) {
        viewModelScope.launch {
            reactToMessageUseCase(messageId, reaction).collect()
        }
    }

    private fun deleteMessage(messageId: Long, forEveryone: Boolean) {
        _uiState.update { state ->
            state.copy(messages = state.messages.filter { it.id != messageId })
        }
        viewModelScope.launch {
            deleteMessageUseCase(messageId, forEveryone).collect()
        }
    }
}

