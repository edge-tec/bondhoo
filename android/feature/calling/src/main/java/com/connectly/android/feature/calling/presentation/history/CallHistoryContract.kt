package com.connectly.android.feature.calling.presentation.history

import com.connectly.android.core.model.CallHistoryItem

data class CallHistoryState(
    val isLoading: Boolean = false,
    val callLogs: List<CallHistoryItem> = emptyList(),
    val errorMessage: String? = null
)

sealed interface CallHistoryIntent {
    data object LoadHistory : CallHistoryIntent
}
