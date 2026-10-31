package com.connectly.android.feature.feed.presentation.create

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.feed.domain.usecase.CreatePostUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class CreatePostViewModel @Inject constructor(
    private val createPostUseCase: CreatePostUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(CreatePostState())
    val uiState: StateFlow<CreatePostState> = _uiState.asStateFlow()

    fun handleIntent(intent: CreatePostIntent) {
        when (intent) {
            is CreatePostIntent.ContentChanged -> _uiState.update { it.copy(content = intent.content) }
            is CreatePostIntent.AudienceChanged -> _uiState.update { it.copy(audience = intent.audience) }
            is CreatePostIntent.MediaAdded -> _uiState.update { it.copy(mediaFiles = it.mediaFiles + intent.file) }
            is CreatePostIntent.MediaRemoved -> _uiState.update { it.copy(mediaFiles = it.mediaFiles.filter { f -> f != intent.file }) }
            CreatePostIntent.SubmitPost -> submitPost()
        }
    }

    private fun submitPost() {
        val s = _uiState.value
        if (s.content.isBlank() && s.mediaFiles.isEmpty()) return

        viewModelScope.launch {
            createPostUseCase(
                content = s.content,
                audience = s.audience,
                mediaFiles = s.mediaFiles
            ).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true, errorMessage = null) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, isPosted = true) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }
    }
}

