package com.connectly.android.feature.groups.presentation.pages

data class PageItem(
    val id: Long,
    val name: String,
    val category: String,
    val avatarUrl: String,
    val coverUrl: String,
    val followersCount: Int,
    val rating: Double = 4.8,
    val isVerified: Boolean = true,
    val isFollowed: Boolean = false,
    val about: String = ""
)

data class PagesUiState(
    val selectedTab: String = "discover", // "discover", "following", "your_pages"
    val featuredPage: PageItem? = null,
    val pages: List<PageItem> = emptyList(),
    val searchQuery: String = "",
    val isLoading: Boolean = false
)

sealed interface PagesIntent {
    data class SelectTab(val tab: String) : PagesIntent
    data class SearchQueryChanged(val query: String) : PagesIntent
    data class ToggleFollowPage(val pageId: Long) : PagesIntent
    object Refresh : PagesIntent
}

