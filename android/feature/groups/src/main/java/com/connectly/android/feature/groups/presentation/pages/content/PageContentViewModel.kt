package com.connectly.android.feature.groups.presentation.pages.content

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.CreateGroupPostRequest
import com.connectly.android.core.network.api.PostDto
import com.connectly.android.feature.groups.domain.usecase.CreatePagePostUseCase
import com.connectly.android.feature.groups.domain.usecase.GetPagePostsUseCase
import com.connectly.android.feature.groups.presentation.util.getLongOrNull
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class PageContentUiState(
    val pageId: Long = 0L,
    val isLoading: Boolean = false,
    val posts: List<PostDto> = emptyList(),
    val errorMessage: String? = null,
    val isCreatingPost: Boolean = false,
    val postCreatedSuccess: Boolean = false
)

@HiltViewModel
class PageContentViewModel @Inject constructor(
    private val getPagePostsUseCase: GetPagePostsUseCase,
    private val createPagePostUseCase: CreatePagePostUseCase,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val _uiState = MutableStateFlow(PageContentUiState())
    val uiState: StateFlow<PageContentUiState> = _uiState.asStateFlow()

    init {
        val pageId = savedStateHandle.getLongOrNull("pageId") ?: 0L
        if (pageId > 0L) {
            _uiState.update { it.copy(pageId = pageId) }
            loadPosts(pageId)
        }
    }

    fun loadPosts(pageId: Long) {
        _uiState.update { it.copy(pageId = pageId, isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            getPagePostsUseCase(pageId).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, posts = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }
    }

    fun createPost(content: String) {
        val pageId = _uiState.value.pageId
        if (pageId <= 0L || content.isBlank()) return

        _uiState.update { it.copy(isCreatingPost = true, postCreatedSuccess = false) }
        viewModelScope.launch {
            val request = CreateGroupPostRequest(content = content)
            createPagePostUseCase(pageId, request).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isCreatingPost = true) }
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isCreatingPost = false,
                                postCreatedSuccess = true,
                                posts = listOf(result.data) + it.posts
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update { it.copy(isCreatingPost = false, errorMessage = result.exception.message) }
                    }
                }
            }
        }
    }
}

