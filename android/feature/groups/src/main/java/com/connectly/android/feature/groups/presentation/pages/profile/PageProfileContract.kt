package com.connectly.android.feature.groups.presentation.pages.profile

import com.connectly.android.core.network.api.PageDto
import com.connectly.android.core.network.api.PageProductDto
import com.connectly.android.core.network.api.PageReviewDto
import com.connectly.android.core.network.api.PageServiceDto
import com.connectly.android.core.network.api.PostDto

data class PageProfileUiState(
    val pageIdOrSlug: String = "",
    val isLoading: Boolean = true,
    val page: PageDto? = null,
    val selectedTab: Int = 0,
    val posts: List<PostDto> = emptyList(),
    val reviews: List<PageReviewDto> = emptyList(),
    val products: List<PageProductDto> = emptyList(),
    val services: List<PageServiceDto> = emptyList(),
    val isFollowing: Boolean = false,
    val isLiked: Boolean = false,
    val errorMessage: String? = null
)

sealed interface PageProfileIntent {
    data class LoadProfile(val pageIdOrSlug: String) : PageProfileIntent
    data class SelectTab(val tabIndex: Int) : PageProfileIntent
    data object ToggleFollow : PageProfileIntent
    data object ToggleLike : PageProfileIntent
    data class SubmitReview(val rating: Int, val reviewText: String) : PageProfileIntent
}
