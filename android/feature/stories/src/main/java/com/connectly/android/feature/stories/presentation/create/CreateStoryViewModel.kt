package com.connectly.android.feature.stories.presentation.create

import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.stories.domain.usecase.CreateStoryUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.io.File
import javax.inject.Inject

data class CreateStoryState(
    val storyType: String = "text", // "text", "photo", "video"
    val storyText: String = "",
    val selectedMediaFile: File? = null,
    val selectedMediaUri: Uri? = null,
    val isVideo: Boolean = false,
    val backgroundColor: String = "#1877F2",
    val fontStyle: String = "Modern",
    val privacy: String = "public",
    val isLoading: Boolean = false,
    val isSuccess: Boolean = false,
    val errorMessage: String? = null
)

@HiltViewModel
class CreateStoryViewModel @Inject constructor(
    private val createStoryUseCase: CreateStoryUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(CreateStoryState())
    val uiState: StateFlow<CreateStoryState> = _uiState.asStateFlow()

    fun setStoryType(type: String) {
        _uiState.update { it.copy(storyType = type) }
    }

    fun updateStoryText(text: String) {
        _uiState.update { it.copy(storyText = text) }
    }

    fun setMedia(file: File, uri: Uri, isVideo: Boolean) {
        _uiState.update {
            it.copy(
                storyType = if (isVideo) "video" else "photo",
                selectedMediaFile = file,
                selectedMediaUri = uri,
                isVideo = isVideo,
                errorMessage = null
            )
        }
    }

    fun clearMedia() {
        _uiState.update {
            it.copy(
                selectedMediaFile = null,
                selectedMediaUri = null,
                isVideo = false,
                storyType = "text"
            )
        }
    }

    fun updateBackgroundColor(colorHex: String) {
        _uiState.update { it.copy(backgroundColor = colorHex) }
    }

    fun updateFontStyle(font: String) {
        _uiState.update { it.copy(fontStyle = font) }
    }

    fun updatePrivacy(privacy: String) {
        _uiState.update { it.copy(privacy = privacy) }
    }

    fun clearError() {
        _uiState.update { it.copy(errorMessage = null) }
    }

    fun publishStory(onSuccess: () -> Unit) {
        val state = _uiState.value

        if (state.storyType == "text" && state.storyText.isBlank()) {
            _uiState.update { it.copy(errorMessage = "অনুগ্রহ করে আপনার স্টোরির জন্য কিছু লিখুন।") }
            return
        }

        if ((state.storyType == "photo" || state.storyType == "video") && state.selectedMediaFile == null) {
            _uiState.update { it.copy(errorMessage = "অনুগ্রহ করে একটি ছবি বা ভিডিও নির্বাচন করুন।") }
            return
        }

        viewModelScope.launch {
            val apiType = if (state.storyType == "text") "text" else "media"
            val content = state.storyText.trim().ifBlank { null }

            createStoryUseCase(
                type = apiType,
                content = content,
                privacy = state.privacy,
                mediaFile = state.selectedMediaFile,
                backgroundColor = state.backgroundColor,
                fontFamily = state.fontStyle
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
                        val msg = result.exception.message ?: "স্টোরি প্রকাশ করতে সমস্যা হয়েছে।"
                        _uiState.update { it.copy(isLoading = false, errorMessage = msg) }
                    }
                }
            }
        }
    }
}
