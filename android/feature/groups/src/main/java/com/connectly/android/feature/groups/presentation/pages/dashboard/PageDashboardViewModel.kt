package com.connectly.android.feature.groups.presentation.pages.dashboard

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.groups.domain.usecase.GetPageAnalyticsUseCase
import com.connectly.android.feature.groups.domain.usecase.GetPageDetailsUseCase
import com.connectly.android.feature.groups.presentation.util.getLongOrNull
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
        val pageId = savedStateHandle.getLongOrNull("pageId") ?: 0L
        if (pageId > 0L) {
            loadDashboard(pageId)
        } else {
            _uiState.update { it.copy(isLoading = false, errorMessage = "Please select a page to view its dashboard.") }
        }
    }

    fun handleIntent(intent: PageDashboardIntent) {
        when (intent) {
            is PageDashboardIntent.LoadDashboard -> loadDashboard(intent.pageId)
            is PageDashboardIntent.SelectRange -> selectRange(intent.range)
            PageDashboardIntent.Refresh -> {
                val currentId = _uiState.value.pageId
                if (currentId > 0L) {
                    loadDashboard(currentId)
                }
            }
        }
    }

    fun loadDashboard(pageId: Long) {
        if (pageId <= 0L) {
            _uiState.update { it.copy(isLoading = false, errorMessage = "Invalid page ID") }
            return
        }
        _uiState.update { it.copy(pageId = pageId, isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            try {
                getPageDetailsUseCase(pageId.toString()).collect { result ->
                    when (result) {
                        is Result.Success -> _uiState.update { it.copy(page = result.data) }
                        is Result.Error -> {
                            // Page details failed, record error if analytics also fails
                            if (_uiState.value.analytics == null) {
                                _uiState.update { it.copy(errorMessage = result.exception.message) }
                            }
                        }
                        else -> Unit
                    }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false, errorMessage = e.message) }
            }
        }
        fetchAnalytics(pageId, _uiState.value.selectedRange)
    }

    private fun selectRange(range: String) {
        _uiState.update { it.copy(selectedRange = range) }
        fetchAnalytics(_uiState.value.pageId, range)
    }

    private fun fetchAnalytics(pageId: Long, range: String) {
        if (pageId <= 0L) return
        viewModelScope.launch {
            try {
                getPageAnalyticsUseCase(pageId, range).collect { result ->
                    when (result) {
                        is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                        is Result.Success -> {
                            _uiState.update { it.copy(isLoading = false, analytics = result.data, errorMessage = null) }
                        }
                        is Result.Error -> {
                            _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message ?: "Failed to load analytics") }
                        }
                    }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false, errorMessage = e.message ?: "Network error") }
            }
        }
    }
}
