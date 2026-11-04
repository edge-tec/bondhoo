package com.connectly.android.feature.groups.presentation.dashboard

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.groups.domain.usecase.GetGroupDashboardUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class GroupDashboardViewModel @Inject constructor(
    private val getGroupDashboardUseCase: GetGroupDashboardUseCase,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val _uiState = MutableStateFlow(GroupDashboardState())
    val uiState: StateFlow<GroupDashboardState> = _uiState.asStateFlow()

    init {
        val initialId: String? = savedStateHandle["groupId"] ?: savedStateHandle["id"]
        val parsedId = initialId?.toLongOrNull() ?: 0L
        if (parsedId > 0) {
            _uiState.update { it.copy(groupId = parsedId) }
            loadDashboard(parsedId, "30d")
        }
    }

    fun handleIntent(intent: GroupDashboardIntent) {
        when (intent) {
            is GroupDashboardIntent.LoadDashboard -> {
                _uiState.update { it.copy(groupId = intent.groupId, selectedPeriod = intent.period) }
                loadDashboard(intent.groupId, intent.period)
            }
            is GroupDashboardIntent.ChangePeriod -> {
                _uiState.update { it.copy(selectedPeriod = intent.period, customStartDate = null, customEndDate = null) }
                loadDashboard(_uiState.value.groupId, intent.period)
            }
            is GroupDashboardIntent.ApplyCustomDateRange -> {
                _uiState.update {
                    it.copy(
                        selectedPeriod = "custom",
                        customStartDate = intent.startDate,
                        customEndDate = intent.endDate,
                        showCustomDateDialog = false
                    )
                }
                loadDashboard(_uiState.value.groupId, "custom", intent.startDate, intent.endDate)
            }
            is GroupDashboardIntent.ShowCustomDateDialog -> {
                _uiState.update { it.copy(showCustomDateDialog = intent.show) }
            }
            GroupDashboardIntent.Refresh -> {
                val state = _uiState.value
                loadDashboard(state.groupId, state.selectedPeriod, state.customStartDate, state.customEndDate)
            }
        }
    }

    private fun loadDashboard(
        groupId: Long,
        period: String,
        startDate: String? = null,
        endDate: String? = null
    ) {
        if (groupId <= 0) return
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            getGroupDashboardUseCase(groupId, period, startDate, endDate).collect { result ->
                when (result) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                dashboardData = result.data,
                                errorMessage = null
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = result.exception.message ?: "Failed to load dashboard analytics"
                            )
                        }
                    }
                    Result.Loading -> {
                        _uiState.update { it.copy(isLoading = true) }
                    }
                }
            }
        }
    }
}

