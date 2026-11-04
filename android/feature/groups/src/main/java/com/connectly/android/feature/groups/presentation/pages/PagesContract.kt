package com.connectly.android.feature.groups.presentation.pages

import com.connectly.android.core.network.api.CreatePageRequest

data class PageItem(
    val id: Long,
    val name: String,
    val username: String = "",
    val category: String,
    val businessCategory: String = "",
    val avatarUrl: String,
    val coverUrl: String,
    val followersCount: Int,
    val rating: Double = 4.8,
    val isVerified: Boolean = true,
    val isFollowed: Boolean = false,
    val isOwner: Boolean = false,
    val about: String = ""
)

data class PagesUiState(
    val selectedTab: String = "discover", // "discover", "following", "your_pages"
    val selectedCategoryFilter: String? = null,
    val featuredPage: PageItem? = null,
    val pages: List<PageItem> = emptyList(),
    val searchQuery: String = "",
    val isSearchVisible: Boolean = false,
    val isLoading: Boolean = false,
    val showCreateDialog: Boolean = false,
    val errorMessage: String? = null
)

sealed interface PagesIntent {
    data class SelectTab(val tab: String) : PagesIntent
    data class SelectCategoryFilter(val category: String?) : PagesIntent
    data class SearchQueryChanged(val query: String) : PagesIntent
    data object ToggleSearch : PagesIntent
    data class ToggleFollowPage(val pageId: Long) : PagesIntent
    data class CreatePage(val name: String, val category: String, val bio: String) : PagesIntent
    data class CreatePageDetailed(val request: CreatePageRequest) : PagesIntent
    data class ShowCreateDialog(val show: Boolean) : PagesIntent
    data object Refresh : PagesIntent
}
