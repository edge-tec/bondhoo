package com.connectly.android.feature.groups.presentation.pages.profile

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.CreatePageReviewRequest
import com.connectly.android.feature.groups.domain.usecase.CreatePageReviewUseCase
import com.connectly.android.feature.groups.domain.usecase.FollowPageUseCase
import com.connectly.android.feature.groups.domain.usecase.GetPageDetailsUseCase
import com.connectly.android.feature.groups.domain.usecase.GetPageProductsServicesUseCase
import com.connectly.android.feature.groups.domain.usecase.GetPageReviewsUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class PageProfileViewModel @Inject constructor(
    private val getPageDetailsUseCase: GetPageDetailsUseCase,
    private val followPageUseCase: FollowPageUseCase,
    private val getPageReviewsUseCase: GetPageReviewsUseCase,
    private val createPageReviewUseCase: CreatePageReviewUseCase,
    private val getPageProductsServicesUseCase: GetPageProductsServicesUseCase,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val _uiState = MutableStateFlow(PageProfileUiState())
    val uiState: StateFlow<PageProfileUiState> = _uiState.asStateFlow()

    init {
        val pageIdOrSlug: String? = savedStateHandle["pageId"] ?: savedStateHandle["slugOrId"]
        if (!pageIdOrSlug.isNullOrBlank()) {
            loadProfile(pageIdOrSlug)
        }
    }

    fun handleIntent(intent: PageProfileIntent) {
        when (intent) {
            is PageProfileIntent.LoadProfile -> loadProfile(intent.pageIdOrSlug)
            is PageProfileIntent.SelectTab -> _uiState.update { it.copy(selectedTab = intent.tabIndex) }
            PageProfileIntent.ToggleFollow -> toggleFollow()
            PageProfileIntent.ToggleLike -> toggleLike()
            is PageProfileIntent.SubmitReview -> submitReview(intent.rating, intent.reviewText)
        }
    }

    fun loadProfile(pageIdOrSlug: String) {
        _uiState.update { it.copy(pageIdOrSlug = pageIdOrSlug, isLoading = true) }
        viewModelScope.launch {
            getPageDetailsUseCase(pageIdOrSlug).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> {
                        val page = result.data
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                page = page,
                                isFollowing = page.is_following,
                                isLiked = page.is_liked
                            )
                        }
                        loadSubData(page.id)
                    }
                    is Result.Error -> {
                        _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                    }
                }
            }
        }
    }

    private fun loadSubData(pageId: Long) {
        viewModelScope.launch {
            getPageReviewsUseCase(pageId).collect { res ->
                if (res is Result.Success) {
                    _uiState.update { it.copy(reviews = res.data) }
                }
            }
        }
        viewModelScope.launch {
            getPageProductsServicesUseCase.getProducts(pageId).collect { res ->
                if (res is Result.Success) {
                    _uiState.update { it.copy(products = res.data) }
                }
            }
        }
        viewModelScope.launch {
            getPageProductsServicesUseCase.getServices(pageId).collect { res ->
                if (res is Result.Success) {
                    _uiState.update { it.copy(services = res.data) }
                }
            }
        }
    }

    private fun toggleFollow() {
        val page = _uiState.value.page ?: return
        val current = _uiState.value.isFollowing
        _uiState.update { it.copy(isFollowing = !current) }
        viewModelScope.launch {
            followPageUseCase(page.id).collect {}
        }
    }

    private fun toggleLike() {
        val current = _uiState.value.isLiked
        _uiState.update { it.copy(isLiked = !current) }
    }

    private fun submitReview(rating: Int, reviewText: String) {
        val page = _uiState.value.page ?: return
        viewModelScope.launch {
            createPageReviewUseCase(page.id, CreatePageReviewRequest(rating, reviewText)).collect { res ->
                if (res is Result.Success) {
                    _uiState.update { state ->
                        state.copy(reviews = listOf(res.data) + state.reviews)
                    }
                }
            }
        }
    }
}
