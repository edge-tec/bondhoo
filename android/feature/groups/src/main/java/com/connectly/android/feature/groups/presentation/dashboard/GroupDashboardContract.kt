package com.connectly.android.feature.groups.presentation.dashboard

import com.connectly.android.core.network.api.GroupDashboardResponseDto

data class GroupDashboardState(
    val groupId: Long = 0,
    val selectedPeriod: String = "30d", // 7d, 30d, 90d, 12m, custom
    val customStartDate: String? = null,
    val customEndDate: String? = null,
    val dashboardData: GroupDashboardResponseDto? = null,
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
    val showCustomDateDialog: Boolean = false
)

sealed interface GroupDashboardIntent {
    data class LoadDashboard(val groupId: Long, val period: String = "30d") : GroupDashboardIntent
    data class ChangePeriod(val period: String) : GroupDashboardIntent
    data class ApplyCustomDateRange(val startDate: String, val endDate: String) : GroupDashboardIntent
    data class ShowCustomDateDialog(val show: Boolean) : GroupDashboardIntent
    data object Refresh : GroupDashboardIntent
}

