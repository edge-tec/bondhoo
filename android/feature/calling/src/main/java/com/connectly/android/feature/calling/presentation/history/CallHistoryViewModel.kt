package com.connectly.android.feature.calling.presentation.history

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.calling.domain.usecase.GetCallHistoryUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class CallHistoryViewModel @Inject constructor(
    private val getCallHistoryUseCase: GetCallHistoryUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(CallHistoryState())
    val uiState: StateFlow<CallHistoryState> = _uiState.asStateFlow()

    init {
        loadHistory()
    }

    fun handleIntent(intent: CallHistoryIntent) {
        when (intent) {
            CallHistoryIntent.LoadHistory -> loadHistory()
        }
    }

    private fun loadHistory() {
        viewModelScope.launch {
            getCallHistoryUseCase().collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, callLogs = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }
    }
}

