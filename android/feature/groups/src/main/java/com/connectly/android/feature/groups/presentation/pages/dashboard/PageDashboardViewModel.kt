package com.connectly.android.feature.groups.presentation.pages.dashboard

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.groups.domain.usecase.GetPageAnalyticsUseCase
import com.connectly.android.feature.groups.domain.usecase.GetPageDetailsUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class PageDashboardViewModel @Inject constructor(
    private val getPageDetailsUseCase: GetPageDetailsUseCase,
    private val getPageAnalyticsUseCase: GetPageAnalyticsUseCase,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val _uiState = MutableStateFlow(PageDashboardUiState())
    val uiState: StateFlow<PageDashboardUiState> = _uiState.asStateFlow()

    init {
        val pageIdStr: String? = savedStateHandle["pageId"]
        val pageId = pageIdStr?.toLongOrNull() ?: 1L
        if (pageId > 0L) {
            loadDashboard(pageId)
        }
    }

    fun handleIntent(intent: PageDashboardIntent) {
        when (intent) {
            is PageDashboardIntent.LoadDashboard -> loadDashboard(intent.pageId)
            is PageDashboardIntent.SelectRange -> selectRange(intent.range)
            PageDashboardIntent.Refresh -> loadDashboard(_uiState.value.pageId)
        }
    }

    fun loadDashboard(pageId: Long) {
        _uiState.update { it.copy(pageId = pageId, isLoading = true) }
        viewModelScope.launch {
            getPageDetailsUseCase(pageId.toString()).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(page = result.data) }
                }
            }
        }
        fetchAnalytics(pageId, _uiState.value.selectedRange)
    }

    private fun selectRange(range: String) {
        _uiState.update { it.copy(selectedRange = range) }
        fetchAnalytics(_uiState.value.pageId, range)
    }

    private fun fetchAnalytics(pageId: Long, range: String) {
        viewModelScope.launch {
            getPageAnalyticsUseCase(pageId, range).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> {
                        _uiState.update { it.copy(isLoading = false, analytics = result.data) }
                    }
                    is Result.Error -> {
                        _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                    }
                }
            }
        }
    }
}
