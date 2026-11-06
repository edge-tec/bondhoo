package com.connectly.android.feature.groups.presentation.pages.inbox

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.PageInboxMessageDto
import com.connectly.android.core.network.api.SendPageMessageRequest
import com.connectly.android.feature.groups.domain.usecase.GetPageInboxUseCase
import com.connectly.android.feature.groups.domain.usecase.SendPageMessageUseCase
import com.connectly.android.feature.groups.presentation.util.getLongOrNull
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class PageInboxUiState(
    val pageId: Long = 0L,
    val isLoading: Boolean = false,
    val messages: List<PageInboxMessageDto> = emptyList(),
    val errorMessage: String? = null,
    val isSending: Boolean = false
)

@HiltViewModel
class PageInboxViewModel @Inject constructor(
    private val getPageInboxUseCase: GetPageInboxUseCase,
    private val sendPageMessageUseCase: SendPageMessageUseCase,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val _uiState = MutableStateFlow(PageInboxUiState())
    val uiState: StateFlow<PageInboxUiState> = _uiState.asStateFlow()

    init {
        val pageId = savedStateHandle.getLongOrNull("pageId") ?: 0L
        if (pageId > 0L) {
            _uiState.update { it.copy(pageId = pageId) }
            loadInbox(pageId)
        }
    }

    fun loadInbox(pageId: Long) {
        _uiState.update { it.copy(pageId = pageId, isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            getPageInboxUseCase(pageId).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, messages = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }
    }

    fun sendMessage(conversationId: Long, text: String) {
        val pageId = _uiState.value.pageId
        if (pageId <= 0L || conversationId <= 0L || text.isBlank()) return

        _uiState.update { it.copy(isSending = true) }
        viewModelScope.launch {
            val request = SendPageMessageRequest(conversation_id = conversationId, text = text)
            sendPageMessageUseCase(pageId, request).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isSending = true) }
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isSending = false,
                                messages = listOf(result.data) + it.messages.filter { m -> m.id != result.data.id }
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update { it.copy(isSending = false, errorMessage = result.exception.message) }
                    }
                }
            }
        }
    }
}

