package com.connectly.android.feature.reels.presentation.create

import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.reels.domain.usecase.CreateReelUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.io.File
import javax.inject.Inject

data class CreateReelState(
    val selectedVideoFile: File? = null,
    val selectedVideoUri: Uri? = null,
    val caption: String = "",
    val audioTitle: String = "Original Audio",
    val privacy: String = "public", // "public", "friends", "only_me"
    val allowComments: Boolean = true,
    val isLoading: Boolean = false,
    val isSuccess: Boolean = false,
    val errorMessage: String? = null
)

@HiltViewModel
class CreateReelViewModel @Inject constructor(
    private val createReelUseCase: CreateReelUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(CreateReelState())
    val uiState: StateFlow<CreateReelState> = _uiState.asStateFlow()

    fun setVideo(file: File, uri: Uri) {
        _uiState.update {
            it.copy(
                selectedVideoFile = file,
                selectedVideoUri = uri,
                errorMessage = null
            )
        }
    }

    fun clearVideo() {
        _uiState.update {
            it.copy(
                selectedVideoFile = null,
                selectedVideoUri = null
            )
        }
    }

    fun updateCaption(caption: String) {
        _uiState.update { it.copy(caption = caption) }
    }

    fun appendHashtag(tag: String) {
        _uiState.update {
            val trimmed = it.caption.trim()
            val newCaption = if (trimmed.isEmpty()) tag else "$trimmed $tag"
            it.copy(caption = newCaption)
        }
    }

    fun updateAudioTitle(title: String) {
        _uiState.update { it.copy(audioTitle = title) }
    }

    fun updatePrivacy(privacy: String) {
        _uiState.update { it.copy(privacy = privacy) }
    }

    fun toggleAllowComments(allow: Boolean) {
        _uiState.update { it.copy(allowComments = allow) }
    }

    fun clearError() {
        _uiState.update { it.copy(errorMessage = null) }
    }

    fun publishReel(onSuccess: () -> Unit) {
        val state = _uiState.value

        if (state.selectedVideoFile == null || !state.selectedVideoFile.exists()) {
            _uiState.update { it.copy(errorMessage = "অনুগ্রহ করে আপনার রিলসের জন্য একটি ভিডিও নির্বাচন করুন।") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }

            createReelUseCase(
                videoFile = state.selectedVideoFile,
                caption = state.caption.trim().ifBlank { null },
                audioTitle = state.audioTitle.trim().ifBlank { "Original Audio" }
            ).collect { result ->
                when (result) {
                    is Result.Loading -> {
                        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
                    }
                    is Result.Success -> {
                        _uiState.update { it.copy(isLoading = false, isSuccess = true) }
                        onSuccess()
                    }
                    is Result.Error -> {
                        val msg = result.exception.message ?: "রিলস প্রকাশ করতে ব্যর্থ হয়েছে।"
                        _uiState.update { it.copy(isLoading = false, errorMessage = msg) }
                    }
                }
            }
        }
    }
}
