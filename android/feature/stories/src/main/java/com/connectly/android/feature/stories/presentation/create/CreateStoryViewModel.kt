package com.connectly.android.feature.stories.presentation.create

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.CreateStoryPayload
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class CreateStoryState(
    val storyText: String = "",
    val storyType: String = "text",
    val backgroundColor: String = "#1877F2",
    val fontStyle: String = "Modern",
    val privacy: String = "public",
    val isLoading: Boolean = false,
    val isSuccess: Boolean = false,
    val errorMessage: String? = null
)

@HiltViewModel
class CreateStoryViewModel @Inject constructor(
    private val api: ConnectlyApi
) : ViewModel() {

    private val _uiState = MutableStateFlow(CreateStoryState())
    val uiState: StateFlow<CreateStoryState> = _uiState.asStateFlow()

    fun updateStoryText(text: String) {
        _uiState.update { it.copy(storyText = text) }
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
        if (state.storyText.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter some text for your story.") }
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            try {
                val payload = CreateStoryPayload(
                    type = "text",
                    content = state.storyText.trim(),
                    privacy = state.privacy,
                    background_color = state.backgroundColor,
                    font_family = state.fontStyle
                )
                val response = api.createStory(payload)
                if (response.isSuccessful && response.body() != null) {
                    _uiState.update { it.copy(isLoading = false, isSuccess = true) }
                    onSuccess()
                } else {
                    val msg = response.body()?.message ?: "Failed to publish story."
                    _uiState.update { it.copy(isLoading = false, errorMessage = msg) }
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false, errorMessage = e.message ?: "Network error occurred") }
            }
        }
    }
}
