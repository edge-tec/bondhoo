package com.connectly.android.feature.groups.presentation.pages.dashboard

import com.connectly.android.core.network.api.PageAnalyticsDetailDto
import com.connectly.android.core.network.api.PageDto

data class PageDashboardUiState(
    val pageId: Long = 0L,
    val isLoading: Boolean = true,
    val selectedRange: String = "7d", // "1d", "7d", "28d", "30d", "90d"
    val page: PageDto? = null,
    val analytics: PageAnalyticsDetailDto? = null,
    val errorMessage: String? = null
)

sealed interface PageDashboardIntent {
    data class LoadDashboard(val pageId: Long) : PageDashboardIntent
    data class SelectRange(val range: String) : PageDashboardIntent
    data object Refresh : PageDashboardIntent
}
