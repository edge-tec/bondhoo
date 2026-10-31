package com.connectly.android.feature.messenger.presentation.conversations

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.messenger.domain.usecase.GetConversationsUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ConversationsViewModel @Inject constructor(
    private val getConversationsUseCase: GetConversationsUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(ConversationsState())
    val uiState: StateFlow<ConversationsState> = _uiState.asStateFlow()

    init {
        loadConversations("all")
    }

    fun handleIntent(intent: ConversationsIntent) {
        when (intent) {
            is ConversationsIntent.SelectFilter -> {
                _uiState.update { it.copy(selectedFilter = intent.filter) }
                loadConversations(intent.filter)
            }
            is ConversationsIntent.SearchQueryChanged -> {
                _uiState.update { it.copy(searchQuery = intent.query) }
            }
            ConversationsIntent.Refresh -> loadConversations(_uiState.value.selectedFilter)
        }
    }

    private fun loadConversations(filter: String) {
        viewModelScope.launch {
            getConversationsUseCase(filter).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, conversations = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }
    }
}
