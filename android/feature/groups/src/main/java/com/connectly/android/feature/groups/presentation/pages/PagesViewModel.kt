package com.connectly.android.feature.groups.presentation.pages

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.CreatePageRequest
import com.connectly.android.feature.groups.domain.usecase.CreatePageDetailedUseCase
import com.connectly.android.feature.groups.domain.usecase.CreatePageUseCase
import com.connectly.android.feature.groups.domain.usecase.FollowPageUseCase
import com.connectly.android.feature.groups.domain.usecase.GetPagesUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.collect
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class PagesViewModel @Inject constructor(
    private val getPagesUseCase: GetPagesUseCase,
    private val followPageUseCase: FollowPageUseCase,
    private val createPageUseCase: CreatePageUseCase,
    private val createPageDetailedUseCase: CreatePageDetailedUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(PagesUiState())
    val uiState: StateFlow<PagesUiState> = _uiState.asStateFlow()

    init {
        loadPages()
    }

    fun handleIntent(intent: PagesIntent) {
        when (intent) {
            is PagesIntent.SelectTab -> {
                _uiState.update { it.copy(selectedTab = intent.tab) }
                loadPages(filter = intent.tab)
            }
            is PagesIntent.SelectCategoryFilter -> {
                _uiState.update { it.copy(selectedCategoryFilter = intent.category) }
            }
            is PagesIntent.SearchQueryChanged -> {
                _uiState.update { it.copy(searchQuery = intent.query) }
            }
            PagesIntent.ToggleSearch -> {
                _uiState.update { it.copy(isSearchVisible = !it.isSearchVisible, searchQuery = if (it.isSearchVisible) "" else it.searchQuery) }
            }
            is PagesIntent.ToggleFollowPage -> {
                toggleFollowPage(intent.pageId)
            }
            is PagesIntent.ShowCreateDialog -> {
                _uiState.update { it.copy(showCreateDialog = intent.show) }
            }
            is PagesIntent.CreatePage -> {
                createNewPage(intent.name, intent.category, intent.bio)
            }
            is PagesIntent.CreatePageDetailed -> {
                createNewPageDetailed(intent.request)
            }
            PagesIntent.Refresh -> {
                loadPages()
            }
        }
    }

    private fun loadPages(filter: String = _uiState.value.selectedTab) {
        viewModelScope.launch {
            getPagesUseCase(filter = filter).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true, errorMessage = null) }
                    is Result.Success -> {
                        val items = result.data.map { dto ->
                            PageItem(
                                id = dto.id,
                                name = dto.name,
                                username = dto.resolvedUsername,
                                category = dto.category,
                                businessCategory = dto.business_category ?: dto.category,
                                avatarUrl = dto.resolvedAvatar ?: "",
                                coverUrl = dto.resolvedCover ?: "",
                                followersCount = dto.followers_count,
                                rating = dto.rating,
                                isVerified = dto.is_verified,
                                isFollowed = dto.is_following,
                                isOwner = dto.is_owner || dto.is_admin,
                                about = dto.bio ?: dto.description ?: ""
                            )
                        }
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                pages = items,
                                featuredPage = items.firstOrNull(),
                                errorMessage = null
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(isLoading = false, errorMessage = result.exception.message)
                        }
                    }
                }
            }
        }
    }

    private fun toggleFollowPage(pageId: Long) {
        val page = _uiState.value.pages.find { it.id == pageId } ?: return
        val nextFollow = !page.isFollowed
        val nextCount = if (nextFollow) page.followersCount + 1 else maxOf(0, page.followersCount - 1)

        _uiState.update { state ->
            val updatedPages = state.pages.map { p ->
                if (p.id == pageId) p.copy(isFollowed = nextFollow, followersCount = nextCount) else p
            }
            val updatedFeatured = if (state.featuredPage?.id == pageId) {
                state.featuredPage.copy(isFollowed = nextFollow, followersCount = nextCount)
            } else state.featuredPage

            state.copy(pages = updatedPages, featuredPage = updatedFeatured)
        }

        viewModelScope.launch {
            followPageUseCase(pageId).collect { result ->
                if (result is Result.Error) {
                    _uiState.update { state ->
                        val revertPages = state.pages.map { p ->
                            if (p.id == pageId) p.copy(isFollowed = page.isFollowed, followersCount = page.followersCount) else p
                        }
                        val revertFeatured = if (state.featuredPage?.id == pageId) {
                            state.featuredPage.copy(isFollowed = page.isFollowed, followersCount = page.followersCount)
                        } else state.featuredPage

                        state.copy(pages = revertPages, featuredPage = revertFeatured)
                    }
                }
            }
        }
    }

    private fun createNewPage(name: String, category: String, bio: String) {
        viewModelScope.launch {
            createPageUseCase(name, category, bio).collect { result ->
                if (result is Result.Success) {
                    val dto = result.data
                    val newPage = PageItem(
                        id = dto.id,
                        name = dto.name,
                        category = dto.category,
                        avatarUrl = dto.avatar ?: "",
                        coverUrl = dto.cover ?: "",
                        followersCount = 1,
                        rating = 5.0,
                        isVerified = false,
                        isFollowed = true,
                        about = dto.bio ?: bio
                    )
                    _uiState.update {
                        it.copy(
                            pages = listOf(newPage) + it.pages,
                            showCreateDialog = false
                        )
                    }
                } else if (result is Result.Error) {
                    _uiState.update {
                        it.copy(
                            showCreateDialog = false,
                            errorMessage = result.exception.message ?: "Failed to create page"
                        )
                    }
                }
            }
        }
    }

    private fun createNewPageDetailed(request: CreatePageRequest) {
        viewModelScope.launch {
            createPageDetailedUseCase(request).collect { result ->
                if (result is Result.Success) {
                    val dto = result.data
                    val newPage = PageItem(
                        id = dto.id,
                        name = dto.name,
                        username = dto.resolvedUsername,
                        category = dto.category,
                        businessCategory = dto.business_category ?: "",
                        avatarUrl = dto.resolvedAvatar ?: "",
                        coverUrl = dto.resolvedCover ?: "",
                        followersCount = 1,
                        rating = 5.0,
                        isVerified = false,
                        isFollowed = true,
                        isOwner = true,
                        about = dto.description ?: dto.bio ?: ""
                    )
                    _uiState.update {
                        it.copy(
                            pages = listOf(newPage) + it.pages,
                            showCreateDialog = false
                        )
                    }
                } else if (result is Result.Error) {
                    _uiState.update {
                        it.copy(
                            showCreateDialog = false,
                            errorMessage = result.exception.message ?: "Failed to create page"
                        )
                    }
                }
            }
        }
    }
}

